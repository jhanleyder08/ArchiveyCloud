<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\TwoFactorAuthenticationService;
use Illuminate\Console\Command;

class ManageTwoFactorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'two-factor:manage 
                            {action : Action to perform: disable, status, stats}
                            {--user= : User email or ID}
                            {--all : Apply to all users}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage Two-Factor Authentication settings';

    protected TwoFactorAuthenticationService $twoFactorService;

    public function __construct(TwoFactorAuthenticationService $twoFactorService)
    {
        parent::__construct();
        $this->twoFactorService = $twoFactorService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        return match ($action) {
            'disable' => $this->disableTwoFactor(),
            'status' => $this->showStatus(),
            'stats' => $this->showStatistics(),
            default => $this->error("Invalid action. Use: disable, status, or stats"),
        };
    }

    /**
     * Disable 2FA for a user
     */
    protected function disableTwoFactor()
    {
        $userIdentifier = $this->option('user');

        if (!$userIdentifier) {
            $this->error('Please specify a user with --user option');
            return 1;
        }

        $user = $this->findUser($userIdentifier);

        if (!$user) {
            $this->error("User not found: {$userIdentifier}");
            return 1;
        }

        if (!$user->hasTwoFactorEnabled()) {
            $this->info("User {$user->email} doesn't have 2FA enabled.");
            return 0;
        }

        if ($this->confirm("Are you sure you want to disable 2FA for {$user->email}?")) {
            $this->twoFactorService->disable($user);
            $this->info("✓ 2FA disabled for {$user->email}");
        } else {
            $this->info('Operation cancelled.');
        }

        return 0;
    }

    /**
     * Show 2FA status for a user
     */
    protected function showStatus()
    {
        $userIdentifier = $this->option('user');

        if (!$userIdentifier) {
            $this->error('Please specify a user with --user option');
            return 1;
        }

        $user = $this->findUser($userIdentifier);

        if (!$user) {
            $this->error("User not found: {$userIdentifier}");
            return 1;
        }

        $twoFactor = $user->twoFactorAuthentication;

        $this->info("2FA Status for {$user->email}:");
        $this->line("─────────────────────────────────────");

        if (!$twoFactor || !$twoFactor->enabled) {
            $this->line("Status: <fg=red>Disabled</>");
        } else {
            $this->line("Status: <fg=green>Enabled</>");
            $this->line("Method: <fg=cyan>{$twoFactor->method}</>");
            $this->line("Confirmed: " . ($twoFactor->isConfirmed() ? '<fg=green>Yes</>' : '<fg=red>No</>'));
            $this->line("Confirmed at: " . ($twoFactor->confirmed_at?->format('Y-m-d H:i:s') ?? 'N/A'));
            
            $recoveryCodes = $twoFactor->recovery_codes ?? [];
            $this->line("Recovery codes: " . count($recoveryCodes) . " remaining");
        }

        return 0;
    }

    /**
     * Show 2FA statistics
     */
    protected function showStatistics()
    {
        $totalUsers = User::count();
        $usersWithTwoFactor = User::whereHas('twoFactorAuthentication', function ($query) {
            $query->where('enabled', true);
        })->count();

        $percentage = $totalUsers > 0 ? round(($usersWithTwoFactor / $totalUsers) * 100, 2) : 0;

        // Get method distribution
        $methodStats = User::whereHas('twoFactorAuthentication', function ($query) {
            $query->where('enabled', true);
        })
            ->with('twoFactorAuthentication')
            ->get()
            ->groupBy('twoFactorAuthentication.method')
            ->map->count();

        $this->info("2FA Statistics:");
        $this->line("═══════════════════════════════════════");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Users', $totalUsers],
                ['Users with 2FA', $usersWithTwoFactor],
                ['Percentage', "{$percentage}%"],
            ]
        );

        if ($methodStats->isNotEmpty()) {
            $this->line("\n2FA Methods Distribution:");
            $this->line("─────────────────────────────────────");
            $this->table(
                ['Method', 'Count'],
                $methodStats->map(fn($count, $method) => [$method, $count])->toArray()
            );
        }

        // Recent activity (last 7 days)
        $recentEnabled = User::whereHas('twoFactorAuthentication', function ($query) {
            $query->where('enabled', true)
                ->where('confirmed_at', '>=', now()->subDays(7));
        })->count();

        $this->line("\nRecent Activity (Last 7 days):");
        $this->line("─────────────────────────────────────");
        $this->line("New 2FA Activations: <fg=cyan>{$recentEnabled}</>");

        return 0;
    }

    /**
     * Find user by email or ID
     */
    protected function findUser(string $identifier): ?User
    {
        if (is_numeric($identifier)) {
            return User::find($identifier);
        }

        return User::where('email', $identifier)->first();
    }
}
