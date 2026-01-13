<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CheckUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:user {email?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check if user exists and can authenticate';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email') ?? 'nickshontz@gmail.com';
        
        $user = User::where('email', $email)->first();
        
        if ($user) {
            $this->info("✓ User found:");
            $this->info("  ID: {$user->id}");
            $this->info("  Name: {$user->name}");
            $this->info("  Email: {$user->email}");
            $this->info("  Created: {$user->created_at}");
        } else {
            $this->error("✗ User with email '{$email}' not found!");
        }
        
        $this->info("\nTotal users: " . User::count());
        
        return $user ? 0 : 1;
    }
}
