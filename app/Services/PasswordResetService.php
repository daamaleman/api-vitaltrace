<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PasswordResetCode;
use App\Models\User;
use App\Notifications\PasswordResetCodeNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PasswordResetService
{
    /**
     * Issue a six-digit reset code for the user and email it.
     */
    public function issueFor(User $user): void
    {
        DB::transaction(function () use ($user): void {
            // Invalidate previous pending codes.
            PasswordResetCode::query()
                ->where('user_id', $user->id)
                ->where('status', 'PENDING')
                ->update(['status' => 'INVALIDATED', 'updated_at' => now()]);

            $plainCode = (string) random_int(100000, 999999);

            PasswordResetCode::create([
                'user_id' => $user->id,
                'code_hash' => Hash::make($plainCode),
                'sent_to_email' => $user->email,
                'expires_at' => now()->addHours(PasswordResetCode::VALIDITY_HOURS),
                'attempts' => 0,
                'status' => 'PENDING',
            ]);

            DB::afterCommit(function () use ($user, $plainCode): void {
                $user->notify(new PasswordResetCodeNotification(
                    $plainCode,
                    PasswordResetCode::VALIDITY_HOURS,
                ));
            });
        });
    }

    /**
     * Verify the code and set the new password. Returns true on success.
     */
    public function resetWithCode(string $email, string $code, string $password): bool
    {
        return DB::transaction(function () use ($email, $code, $password): bool {
            $user = User::query()->where('email', $email)->first();
            if ($user === null) {
                return false;
            }

            $record = PasswordResetCode::query()
                ->where('user_id', $user->id)
                ->where('status', 'PENDING')
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if ($record === null) {
                return false;
            }

            if ($record->expires_at->isPast()) {
                $record->update(['status' => 'EXPIRED']);
                return false;
            }

            if (! Hash::check($code, $record->code_hash)) {
                $record->increment('attempts');
                $record->refresh();
                if ($record->attempts >= PasswordResetCode::MAX_ATTEMPTS) {
                    $record->update(['status' => 'INVALIDATED']);
                }
                return false;
            }

            // Valid code: set new password, mark used.
            $user->forceFill([
                'password' => Hash::make($password),
                'failed_attempts' => 0,
                'blocked_until' => null,
            ])->save();

            $record->update(['status' => 'USED', 'used_at' => now()]);

            return true;
        });
    }
}
