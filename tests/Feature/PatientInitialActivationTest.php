<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AccountActivation;
use App\Models\Patient;
use App\Models\Person;
use App\Models\User;
use App\Notifications\AccountActivationCode;
use App\Notifications\PasswordResetToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PatientInitialActivationTest extends TestCase
{
    use RefreshDatabase;

    private const CODE = '123456';

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
        $app['config']->set('cache.default', 'array');
    }

    public function test_valid_code_returns_single_use_temporary_token_without_sanctum_session(): void
    {
        $user = $this->createPatientUser();
        $activation = $this->createActivation($user);

        $response = $this->postJson('/api/v1/auth/activation/verify-code', [
            'email' => $user->email,
            'code' => self::CODE,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.expires_in', 900)
            ->assertJsonPath('message', 'Activation code verified.')
            ->assertJsonPath('errors', null);

        $token = $response->json('data.activation_token');
        $this->assertIsString($token);
        $this->assertNotSame('', $token);
        $this->assertDatabaseHas('account_activations', [
            'id' => $activation->id,
            'status' => 'USED',
            'activation_token_hash' => hash('sha256', $token),
        ]);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_incorrect_code_is_rejected_and_increments_attempts(): void
    {
        $user = $this->createPatientUser();
        $activation = $this->createActivation($user);

        $this->postJson('/api/v1/auth/activation/verify-code', [
            'email' => $user->email,
            'code' => '999999',
        ])->assertUnprocessable();

        $this->assertDatabaseHas('account_activations', [
            'id' => $activation->id,
            'attempts' => 1,
            'status' => 'PENDING',
        ]);
    }

    public function test_expired_code_is_rejected(): void
    {
        $user = $this->createPatientUser();
        $activation = $this->createActivation($user, ['expires_at' => now()->subMinute()]);

        $this->verifyCode($user)->assertUnprocessable();
        $this->assertDatabaseHas('account_activations', ['id' => $activation->id, 'status' => 'EXPIRED']);
    }

    public function test_used_code_is_rejected(): void
    {
        $user = $this->createPatientUser();
        $this->createActivation($user, ['status' => 'USED', 'used_at' => now()]);

        $this->verifyCode($user)->assertUnprocessable();
    }

    public function test_already_activated_patient_is_rejected(): void
    {
        $user = $this->createPatientUser([
            'status' => 'ACTIVE',
            'password' => Hash::make('SecurePassword123!'),
            'password_set_at' => now(),
        ]);
        $this->createActivation($user);

        $this->verifyCode($user)->assertUnprocessable();
    }

    public function test_valid_token_creates_password_and_activates_account(): void
    {
        $user = $this->createPatientUser();
        $this->createActivation($user);
        $token = $this->verifyCode($user)->json('data.activation_token');

        $this->setPassword($token)->assertOk()
            ->assertExactJson([
                'data' => ['activation_completed' => true],
                'message' => 'Password created successfully.',
                'errors' => null,
            ]);

        $user->refresh();
        $this->assertSame('ACTIVE', $user->status);
        $this->assertNotNull($user->password_set_at);
        $this->assertTrue(Hash::check('SecurePassword123!', $user->password));
    }

    public function test_expired_token_is_rejected(): void
    {
        $user = $this->createPatientUser();
        $this->createActivation($user);
        $token = $this->verifyCode($user)->json('data.activation_token');
        AccountActivation::query()->where('user_id', $user->id)
            ->update(['activation_token_expires_at' => now()->subMinute()]);

        $this->setPassword($token)->assertUnprocessable();
    }

    public function test_used_token_cannot_be_reused(): void
    {
        $user = $this->createPatientUser();
        $this->createActivation($user);
        $token = $this->verifyCode($user)->json('data.activation_token');

        $this->setPassword($token)->assertOk();
        $this->setPassword($token)->assertUnprocessable();
    }

    public function test_password_confirmation_must_match(): void
    {
        $this->postJson('/api/v1/auth/activation/set-password', [
            'activation_token' => str_repeat('a', 64),
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'DifferentPassword123!',
        ])->assertUnprocessable()->assertJsonValidationErrors('password');
    }

    public function test_password_must_meet_security_rules(): void
    {
        $this->postJson('/api/v1/auth/activation/set-password', [
            'activation_token' => str_repeat('a', 64),
            'password' => 'invalid',
            'password_confirmation' => 'invalid',
        ])->assertUnprocessable()->assertJsonValidationErrors('password');
    }

    public function test_resend_invalidates_previous_code_and_sends_a_new_email(): void
    {
        Notification::fake();
        $user = $this->createPatientUser();
        $previous = $this->createActivation($user);

        $this->postJson('/api/v1/auth/activation/resend-code', ['email' => $user->email])
            ->assertOk()
            ->assertJsonPath('message', 'If the account exists and is pending, a new code has been sent.');

        $this->assertDatabaseHas('account_activations', [
            'id' => $previous->id,
            'status' => 'INVALIDATED',
        ]);
        $this->assertSame(2, AccountActivation::query()->where('user_id', $user->id)->count());
        Notification::assertSentTo($user, AccountActivationCode::class);
    }

    public function test_login_is_blocked_before_initial_password_is_set(): void
    {
        $user = $this->createPatientUser(['password' => Hash::make('Temporary123!')]);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'Temporary123!',
        ])->assertForbidden()
            ->assertJsonPath('errors.code', 'ACCOUNT_ACTIVATION_REQUIRED');
    }

    public function test_login_works_after_activation(): void
    {
        $user = $this->createPatientUser();
        $this->createActivation($user);
        $token = $this->verifyCode($user)->json('data.activation_token');
        $this->setPassword($token)->assertOk();

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'SecurePassword123!',
        ])->assertOk()
            ->assertJsonPath('data.token_type', 'Bearer');
    }

    public function test_forgot_password_remains_available_for_active_patient(): void
    {
        Notification::fake();
        $user = $this->createPatientUser([
            'status' => 'ACTIVE',
            'password' => Hash::make('ExistingPassword123!'),
            'password_set_at' => now(),
        ]);

        $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])
            ->assertOk();

        Notification::assertSentTo($user, PasswordResetToken::class);
    }

    public function test_non_patient_account_cannot_use_patient_activation_flow(): void
    {
        $user = $this->createUser();
        $this->createActivation($user);

        $this->verifyCode($user)->assertUnprocessable();
    }

    public function test_activation_routes_are_rate_limited(): void
    {
        $user = $this->createPatientUser();
        $this->createActivation($user);

        for ($attempt = 1; $attempt <= 6; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.80'])
                ->postJson('/api/v1/auth/activation/verify-code', [
                    'email' => $user->email,
                    'code' => '999999',
                ]);
        }

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.80'])
            ->postJson('/api/v1/auth/activation/verify-code', [
                'email' => $user->email,
                'code' => '999999',
            ])->assertTooManyRequests();
    }

    private function verifyCode(User $user)
    {
        return $this->postJson('/api/v1/auth/activation/verify-code', [
            'email' => $user->email,
            'code' => self::CODE,
        ]);
    }

    private function setPassword(string $token)
    {
        return $this->postJson('/api/v1/auth/activation/set-password', [
            'activation_token' => $token,
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
        ]);
    }

    private function createPatientUser(array $overrides = []): User
    {
        $user = $this->createUser($overrides);
        Patient::create([
            'person_id' => $user->person_id,
            'record_number' => 'VT-'.str_pad((string) $user->id, 6, '0', STR_PAD_LEFT),
            'admission_date' => now()->toDateString(),
            'administrative_status' => 'ACTIVE',
        ]);

        return $user;
    }

    private function createUser(array $overrides = []): User
    {
        $person = Person::create([
            'first_name' => 'Test',
            'first_last_name' => 'Patient',
            'date_of_birth' => '1990-01-01',
            'gender' => 'FEMALE',
        ]);

        return User::create(array_merge([
            'person_id' => $person->id,
            'email' => 'patient'.$person->id.'@example.com',
            'password' => null,
            'password_set_at' => null,
            'status' => 'PENDING',
        ], $overrides));
    }

    private function createActivation(User $user, array $overrides = []): AccountActivation
    {
        return AccountActivation::create(array_merge([
            'user_id' => $user->id,
            'code_hash' => Hash::make(self::CODE),
            'sent_to_email' => $user->email,
            'expires_at' => now()->addHours(24),
            'attempts' => 0,
            'status' => 'PENDING',
        ], $overrides));
    }
}
