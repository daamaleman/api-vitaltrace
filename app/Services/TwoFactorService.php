<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TwoFactorCode;
use App\Models\User;
use App\Notifications\TwoFactorCodeNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TwoFactorService
{
    /**
     * Issue a six-digit 2FA code, email it, and return the public challenge id.
     */
    public function issueFor(User $user): string
    {
        return DB::transaction(function () use ($user): string {
            // Invalidate previous pending challenges.
            TwoFactorCode::query()
                ->where('user_id', $user->id)
                ->where('status', 'PENDING')
                ->update(['status' => 'INVALIDATED', 'updated_at' => now()]);

            $plainCode = (string) random_int(100000, 999999);
            $challengeId = (string) Str::uuid();

            TwoFactorCode::create([
                'user_id' => $user->id,
                'challenge_id' => $challengeId,
                'code_hash' => Hash::make($plainCode),
                'sent_to_email' => $user->email,
                'expires_at' => now()->addMinutes(TwoFactorCode::VALIDITY_MINUTES),
                'attempts' => 0,
                'status' => 'PENDING',
            ]);

            DB::afterCommit(function () use ($user, $plainCode): void {
                try {
                    $user->notify(new TwoFactorCodeNotification(
                        $plainCode,
                        TwoFactorCode::VALIDITY_MINUTES,
                    ));
                } catch (\Throwable $e) {
                    // El fallo de correo no debe romper el login.
                    // El codigo ya esta emitido; se puede reenviar.
                    report($e);
                }
            });

            return $challengeId;
        });
    }

    /**
     * Verify a challenge code. Returns the User on success, or null on failure.
     */
    public function verify(string $challengeId, string $code): ?User
    {
        return DB::transaction(function () use ($challengeId, $code): ?User {
            $record = TwoFactorCode::query()
                ->where('challenge_id', $challengeId)
                ->where('status', 'PENDING')
                ->lockForUpdate()
                ->first();

            if ($record === null) {
                return null;
            }

            if ($record->expires_at->isPast()) {
                $record->update(['status' => 'EXPIRED']);
                return null;
            }

            if (! Hash::check($code, $record->code_hash)) {
                $record->increment('attempts');
                $record->refresh();
                if ($record->attempts >= TwoFactorCode::MAX_ATTEMPTS) {
                    $record->update(['status' => 'INVALIDATED']);
                }
                return null;
            }

            $record->update(['status' => 'USED', 'used_at' => now()]);

            return $record->user;
        });
    }

    /**
     * Re-issue a code for an existing pending challenge (resend).
     * Returns the new challenge id, or null if the challenge is unknown.
     */
    public function resend(string $challengeId): ?string
    {
        $record = TwoFactorCode::query()
            ->where('challenge_id', $challengeId)
            ->whereIn('status', ['PENDING', 'EXPIRED'])
            ->latest('id')
            ->first();

        if ($record === null) {
            return null;
        }

        return $this->issueFor($record->user);
    }
}