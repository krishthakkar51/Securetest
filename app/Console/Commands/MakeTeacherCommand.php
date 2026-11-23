<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeTeacherCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:make-teacher {email? : The email address of the existing user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely promote an existing user account to the teacher role.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');

        if (!$email) {
            $email = $this->ask('Enter the email address of the account you want to promote to Teacher');
        }

        $email = trim((string) $email);

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error("Invalid email address format: '{$email}'.");
            return self::FAILURE;
        }

        // Find existing user
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("No user found with the email: '{$email}'.");
            $this->line('');
            $this->warn('Safety Policy: Teacher accounts cannot be auto-generated with random or unknown passwords.');
            $this->info('Next steps:');
            $this->line(' 1. Have the user register normally via the web registration page (or an administrative onboarding flow).');
            $this->line(' 2. Run this command again with their registered email to promote their account to Teacher.');
            return self::FAILURE;
        }

        // Display targeted user information (never exposing password)
        $this->info('Matching user account located:');
        $this->table(
            ['ID', 'Name', 'Email', 'Current Role', 'Account Created'],
            [[
                $user->id,
                $user->name,
                $user->email,
                strtoupper($user->role),
                $user->created_at ? $user->created_at->toDateTimeString() : 'N/A',
            ]]
        );

        // Check if already a teacher
        if ($user->isTeacher()) {
            $this->info("User '{$user->name}' ({$user->email}) is already assigned the TEACHER role.");
            return self::SUCCESS;
        }

        // Explicit confirmation prompt
        if (!$this->confirm("Are you sure you want to promote '{$user->name}' ({$user->email}) to TEACHER?", false)) {
            $this->warn('Promotion canceled. No changes were made.');
            return self::SUCCESS;
        }

        // Promote role
        $user->role = User::ROLE_TEACHER;
        $user->save();

        $this->info("Success! User '{$user->name}' ({$user->email}) is now a TEACHER.");
        return self::SUCCESS;
    }
}
