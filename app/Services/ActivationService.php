<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AccountActivation;
use App\Models\User;
use App\Notifications\AccountActivationCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Encapsulates the account activation lifecycle (RN-10):
 * single-use hashed codes, 24-hour validity, max attempts and invalidation of
 * previous pending codes on reissue.
 */
class ActivationService
{
    /**
     * Issue a new activation code for the user, invalidating previous pending ones.
     *
     * The plain code is generated here, hashed for storage and sent by email;
     * it is never returned or persisted in plain text.
     */
    public function issueFor(User $user): AccountActivation
    {
        return DB::transaction(function () use ($user): AccountActivation {
            // Invalidate any previous pending code for this user.
            AccountActivation::where('user_id', $user->id)
                ->where('status', 'PENDING')
                ->update(['status' => 'INVALIDATED', 'updated_at' => now()]);

            $plainCode = $this->generateCode();

            $activation = AccountActivation::create([
                'user_id' => $user->id,
                'code_hash' => Hash::make($plainCode),
                'sent_to_email' => $user->email,
                'expires_at' => now()->addHours(AccountActivation::VALIDITY_HOURS),
                'attempts' => 0,
                'status' => 'PENDING',
            ]);

            // Deliver the plain code by email; it lives only in the message.
            $user->notify(new AccountActivationCode($plainCode, AccountActivation::VALIDITY_HOURS));

            return $activation;
        });
    }

    /**
     * Attempt to activate an account with the given email and code.
     *
     * Returns the activated user on success. On failure, increments attempts,
     * invalidates the code when the limit is reached, and returns null.
     */
    public function activate(string $email, string $code, string $newPassword): ?User
    {
        return DB::transaction(function () use ($email, $code, $newPassword): ?User {
            $user = User::where('email', $email)->first();

            if ($user === null) {
                return null;
            }

            $activation = AccountActivation::where('user_id', $user->id)
                ->where('status', 'PENDING')
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if ($activation === null) {
                return null;
            }

            // Expired by time: mark and stop.
            if ($activation->expires_at->isPast()) {
                $activation->update(['status' => 'EXPIRED']);

                return null;
            }

            // Wrong code: count the attempt, invalidate if limit reached.
            if (! Hash::check($code, $activation->code_hash)) {
                $activation->increment('attempts');

                if ($activation->attempts >= AccountActivation::MAX_ATTEMPTS) {
                    $activation->update(['status' => 'INVALIDATED']);
                }

                return null;
            }

            // Success: consume the code and activate the account.
            $activation->update([
                'status' => 'USED',
                'used_at' => now(),
            ]);

            $user->forceFill([
                'password' => Hash::make($newPassword),
                'status' => 'ACTIVE',
                'email_verified_at' => now(),
            ])->save();

            return $user;
        });
    }

    /**
     * Generate a zero-padded six-digit numeric code.
     */
    private function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
