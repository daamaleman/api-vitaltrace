<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AccountActivation;
use App\Models\User;
use App\Notifications\AccountActivationCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ActivationService
{
    public function issueFor(User $user): AccountActivation
    {
        return DB::transaction(function () use ($user): AccountActivation {
            
            // 1. Invalidar activaciones previas seg¨²n tu nueva l¨®gica
            AccountActivation::query()
                ->where('user_id', $user->id)
                ->where(function ($query): void {
                    $query->where('status', 'PENDING')
                        ->orWhere(function ($tokenQuery): void {
                            $tokenQuery->whereNotNull('code_hash')
                                ->whereNull('used_at');
                        });
                })
                ->update([
                    'status' => 'INVALIDATED',
                    'updated_at' => now(),
                ]);

            // 2. Generar un nuevo c¨®digo y crear el nuevo registro
            $plainCode = $this->generateCode();
            $activation = AccountActivation::create([
                'user_id' => $user->id,
                'code_hash' => Hash::make($plainCode),
                'sent_to_email' => $user->email,
                'expires_at' => now()->addHours(AccountActivation::VALIDITY_HOURS),
                'attempts' => 0,
                'status' => 'PENDING',
            ]);

            // 3. Notificar al usuario con el c¨®digo en texto plano
            DB::afterCommit(function () use ($user, $plainCode): void {
                $user->notify(new AccountActivationCode(
                    $plainCode,
                    AccountActivation::VALIDITY_HOURS,
                ));
            });

            return $activation;
        });
    }

    /** @return array{activation_token: string, expires_in: int}|null */
    public function verifyPatientCode(string $email, string $code): ?array
    {
        return DB::transaction(function () use ($email, $code): ?array {
            $user = User::query()
                ->where('email', $email)
                ->where('status', 'PENDING')
                ->whereNull('password_set_at')
                ->whereHas('patient')
                ->first();

            if ($user === null) {
                return null;
            }

            $activation = AccountActivation::query()
                ->where('user_id', $user->id)
                ->where('status', 'PENDING')
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if ($activation === null) {
                return null;
            }

            if ($activation->expires_at->isPast()) {
                $activation->update(['status' => 'EXPIRED']);

                return null;
            }

            if (! Hash::check($code, $activation->code_hash)) {
                $activation->increment('attempts');
                $activation->refresh();

                if ($activation->attempts >= AccountActivation::MAX_ATTEMPTS) {
                    $activation->update(['status' => 'INVALIDATED']);
                }

                return null;
            }

            $plainToken = bin2hex(random_bytes(32));
            $activation->update([
                'status' => 'USED',
                'used_at' => now(),
                'activation_token_hash' => hash('sha256', $plainToken),
                'activation_token_expires_at' => now()->addMinutes(AccountActivation::TOKEN_VALIDITY_MINUTES),
                'activation_token_used_at' => null,
            ]);

            return [
                'activation_token' => $plainToken,
                'expires_in' => AccountActivation::TOKEN_VALIDITY_MINUTES * 60,
            ];
        });
    }

    public function setPatientInitialPassword(string $plainToken, string $password): bool
    {
        return DB::transaction(function () use ($plainToken, $password): bool {
            $activation = AccountActivation::query()
                ->where('activation_token_hash', hash('sha256', $plainToken))
                ->where('status', 'USED')
                ->whereNull('activation_token_used_at')
                ->lockForUpdate()
                ->first();

            if ($activation === null
                || $activation->activation_token_expires_at === null
                || $activation->activation_token_expires_at->isPast()) {
                return false;
            }

            $user = User::query()
                ->whereKey($activation->user_id)
                ->where('status', 'PENDING')
                ->whereNull('password_set_at')
                ->whereHas('patient')
                ->lockForUpdate()
                ->first();

            if ($user === null) {
                return false;
            }

            $completedAt = now();
            $user->forceFill([
                'password' => Hash::make($password),
                'password_set_at' => $completedAt,
                'status' => 'ACTIVE',
                'email_verified_at' => $completedAt,
                'failed_attempts' => 0,
                'blocked_until' => null,
            ])->save();

            $activation->update([
                'activation_token_used_at' => $completedAt,
                'activation_token_hash' => null,
            ]);

            return true;
        });
    }

    /**
     * Preserve the previous one-step flow only for non-patient accounts.
     */
    public function activateLegacy(string $email, string $code, string $newPassword): ?User
    {
        return DB::transaction(function () use ($email, $code, $newPassword): ?User {
            $user = User::query()->where('email', $email)->first();

            if ($user === null || $user->patient()->exists()) {
                return null;
            }

            $activation = AccountActivation::query()
                ->where('user_id', $user->id)
                ->where('status', 'PENDING')
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if ($activation === null || $activation->expires_at->isPast()) {
                return null;
            }

            if (! Hash::check($code, $activation->code_hash)) {
                $activation->increment('attempts');

                return null;
            }

            $completedAt = now();
            $activation->update(['status' => 'USED', 'used_at' => $completedAt]);
            $user->forceFill([
                'password' => Hash::make($newPassword),
                'password_set_at' => $completedAt,
                'status' => 'ACTIVE',
                'email_verified_at' => $completedAt,
            ])->save();

            return $user;
        });
    }

    private function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}