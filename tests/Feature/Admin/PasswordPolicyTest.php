<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * A staff account can read every customer record on the system, so the password
 * that protects it has to be worth something. Laravel's own default is eight
 * characters with no other requirement, which accepts "password".
 */
class PasswordPolicyTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{0: string}> */
    public static function weakPasswords(): array
    {
        return [
            'a dictionary word' => ['password'],
            'the bare minimum Laravel allows' => ['abc12345'],
            'eleven characters' => ['elevenchars'],
        ];
    }

    #[DataProvider('weakPasswords')]
    public function test_weak_passwords_are_refused(string $password): void
    {
        $this->assertTrue(
            Validator::make(['password' => $password], ['password' => Password::defaults()])->fails(),
            "[{$password}] should not be accepted for a staff account.",
        );
    }

    public function test_a_long_passphrase_is_accepted(): void
    {
        $this->assertTrue(
            Validator::make(['password' => 'quayside lantern rope'], ['password' => Password::defaults()])->passes(),
        );
    }

    public function test_anything_bcrypt_would_silently_truncate_is_refused(): void
    {
        // bcrypt ignores everything past 72 bytes; accepting a longer password
        // would promise strength the hash does not deliver.
        $this->assertTrue(
            Validator::make(['password' => str_repeat('a', 80)], ['password' => Password::defaults()])->fails(),
        );
    }

    public function test_the_policy_is_enforced_when_an_account_is_created(): void
    {
        $this->seedCoreData();

        $this->actingAs($this->administrator())
            ->post(route('admin.users.store'), [
                'name' => 'New Representative',
                'email' => 'new-rep@example.test',
                'role' => UserRole::Representative->value,
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'new-rep@example.test']);
    }

    public function test_the_policy_is_enforced_when_staff_change_their_own_password(): void
    {
        $this->seedCoreData();

        $this->actingAs($this->representative())
            ->put(route('admin.profile.password'), [
                'current_password' => 'password',
                'password' => 'short123',
                'password_confirmation' => 'short123',
            ])
            ->assertSessionHasErrors('password');
    }
}
