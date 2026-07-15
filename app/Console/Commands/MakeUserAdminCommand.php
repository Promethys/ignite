<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Make a user an admin')]
class MakeUserAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:make-admin 
                            {user : The ID or email of the user}
                            {--force}';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $user = $this->resolveUser();
        $option = $this->argument('user');

        if(!$user) {
            $this->error("User not found with ID or email matching '$option'.");

            return 1;
        }

        if (
            $this->option('force') 
            || $this->confirm("Admin role will be assigned to '{$user->name}'.Do you wish to continue?", false)
        ) {
            if (! $user->hasRole('admin')) {
                $user->assignRole('admin');

                $this->info('This user now has admin role.');
            } else {
                $this->info('This user already has admin role. Nothing has changed.');
            }

            return 0;
        }

        $this->warn('Aborted.');
        return 1;
    }

    protected function resolveUser(): ?User
    {
        $option = $this->argument('user');

        $user = is_numeric($option) 
            ? User::find($option)
            : User::where('email', $option)->first();

        return $user;
    }
}
