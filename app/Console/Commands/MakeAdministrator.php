<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeAdministrator extends Command
{
    protected $signature = 'lunelle:make-admin {email : The email address of an existing user}';

    protected $description = 'Promote an existing Lunelle user to administrator';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('No user exists with that email address. Register the user first.');

            return self::FAILURE;
        }

        $user->update(['user_type' => 'administrator']);
        $this->info("{$user->email} is now an administrator.");

        return self::SUCCESS;
    }
}
