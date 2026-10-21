<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Modules\Admin\Enums\UserRole;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Creates the first Super Admin on a new server. The password is typed at a hidden prompt, so it never
 * lands in the shell history or in a seeder.
 */
class CreateSuperAdminCommand extends Command
{
    protected $signature = 'fleet:create-super-admin
        {--name= : Full name}
        {--email= : Sign-in email}
        {--generate : Make a strong password and show it once, instead of typing one}';

    protected $description = 'Create a Super Admin account (asks for the password at a hidden prompt)';

    public function handle(): int
    {
        $name = $this->option('name') ?: text('Full name', required: true);
        $email = $this->option('email') ?: text('Sign-in email', required: true);

        if ($this->option('generate')) {
            $password = $confirmation = Str::password(16, symbols: false);
        } else {
            $password = password('Password', required: true, hint: 'At least 12 characters with letters and numbers; nothing shows while you type');
            $confirmation = password('Type the password again', required: true);

            // Hidden typing shows nothing, and some terminals keep stray keystrokes: let the person check the count
            if ($password === $confirmation
                && ! confirm('The password you typed has '.mb_strlen($password).' characters. Is that right?', default: false)) {
                $this->warn('Nothing was created. Run the command again, or add --generate to get a password made for you.');

                return self::FAILURE;
            }
        }

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password, 'password_confirmation' => $confirmation],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'confirmed', Password::defaults()],
            ],
            ['email.unique' => 'An account with this email already exists; nothing was changed.'],
        );
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $user = User::forceCreate([
            'name' => $name,
            'email' => strtolower($email),
            'password' => Hash::make($password),
            'role' => UserRole::SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);

        if ($this->option('generate')) {
            $this->info("Super Admin {$user->email} created. Its password, shown only this once:");
            $this->line("  {$password}");
            $this->warn('Keep it somewhere safe, or change it from your profile after signing in.');
        } else {
            $this->info("Super Admin {$user->email} created. Sign in with this email and the password you typed.");
        }

        return self::SUCCESS;
    }
}
