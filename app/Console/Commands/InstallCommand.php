<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * The first admin from the environment, so a container comes up ready to log
 * into. Runs on every boot and does nothing once any account exists: a restart
 * never resets a password. No default password: without ADMIN_* the setup
 * screen asks instead.
 */
class InstallCommand extends Command
{
    protected $signature = 'fleche:install';

    protected $description = 'Create the first admin from ADMIN_EMAIL/ADMIN_PASSWORD, once';

    public function handle(): int
    {
        if (User::query()->exists()) {
            $this->info('An account already exists; leaving it alone.');

            return self::SUCCESS;
        }

        $data = [
            'name' => config('fleche.admin.name') ?: 'Admin',
            'email' => config('fleche.admin.email'),
            'password' => config('fleche.admin.password'),
            'timezone' => config('app.timezone'),
        ];

        if (blank($data['email']) || blank($data['password'])) {
            $this->info('No ADMIN_EMAIL/ADMIN_PASSWORD; the setup screen will ask instead.');

            return self::SUCCESS;
        }

        $validator = Validator::make($data, ['email' => ['email'], 'password' => [Password::defaults()]]);

        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }

        User::create($data)->forceFill(['is_admin' => true, 'email_verified_at' => now()])->save();
        $this->info("Created admin {$data['email']}.");

        return self::SUCCESS;
    }
}
