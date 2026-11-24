<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateOwner extends Command
{
    protected $signature = 'app:create-owner';
    protected $description = 'Create a real owner using interactive password prompts';

    public function handle(): int
    {
        $name = $this->ask('Owner name');
        $email = strtolower(trim((string) $this->ask('Owner email')));
        $password = $this->secret('Password (at least 12 characters)');
        $confirmation = $this->secret('Confirm password');
        $validator = Validator::make(['name' => $name, 'email' => $email, 'password' => $password, 'password_confirmation' => $confirmation], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email', 'not_regex:/@(demo\.(lunelle|shafers)\.test|booking\.local)$/i'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) { $this->error($message); }
            return self::FAILURE;
        }
        User::create(['name' => $name, 'email' => $email, 'password' => $password, 'user_type' => 'owner', 'is_active' => true]);
        $this->info('Owner account created. Sign in using the email and password you supplied.');
        return self::SUCCESS;
    }
}
