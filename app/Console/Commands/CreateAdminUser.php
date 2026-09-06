<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password as promptPassword;
use function Laravel\Prompts\text;

/**
 * Creates the first administrator without putting credentials in source control.
 */
class CreateAdminUser extends Command
{
    protected $signature = 'portlane:create-admin
                            {--name= : Full name of the administrator}
                            {--email= : Email address used to sign in}
                            {--password= : Password (you will be prompted if omitted)}
                            {--role=administrator : administrator, manager or agent}';

    protected $description = 'Create an administrator account for the admin panel';

    public function handle(AuditLogger $audit): int
    {
        $name = $this->option('name') ?: text('Full name', required: true);
        $email = $this->option('email') ?: text('Email address', required: true);
        $password = $this->option('password') ?: promptPassword('Password (at least 8 characters)', required: true);
        $role = $this->option('role');

        $validator = Validator::make(
            compact('name', 'email', 'password', 'role'),
            [
                'name' => ['required', 'string', 'max:160'],
                'email' => ['required', 'email:filter', 'max:180', 'unique:users,email'],
                'password' => ['required', Password::min(8)],
                'role' => ['required', 'in:'.implode(',', array_column(UserRole::cases(), 'value'))],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => $role,
            'is_active' => true,
        ]);

        $audit->record('user.created', $user, "Created admin user {$user->name} from the console", ['role' => $role], $user);

        $this->components->info("Administrator {$user->email} created. Sign in at ".route('admin.login'));

        return self::SUCCESS;
    }
}
