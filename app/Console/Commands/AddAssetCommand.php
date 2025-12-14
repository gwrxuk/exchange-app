<?php

namespace App\Console\Commands;

use App\Models\Asset;
use App\Models\Symbol;
use App\Models\User;
use Illuminate\Console\Command;

class AddAssetCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:add-asset 
                            {email : The email of the user}
                            {symbol : The asset symbol (e.g., BTC, ETH)}
                            {amount : The amount to add}
                            {--dry-run : Preview the changes without applying them}
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add assets to a user by email';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');
        $symbol = strtoupper($this->argument('symbol'));
        $amount = (float) $this->argument('amount');
        $dryRun = $this->option('dry-run');

        // Validate amount
        if ($amount <= 0) {
            $this->error('❌ Amount must be greater than 0.');

            return Command::FAILURE;
        }

        // Find user by email
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("❌ User with email '{$email}' not found.");

            return Command::FAILURE;
        }

        // Check if symbol exists in symbols table
        $symbolRecord = Symbol::where('code', $symbol)->where('is_active', true)->first();

        if (! $symbolRecord) {
            $this->error("❌ Symbol '{$symbol}' not found or is not active.");
            $this->newLine();

            // Show available symbols
            $availableSymbols = Symbol::where('is_active', true)->pluck('code')->toArray();
            if (count($availableSymbols) > 0) {
                $this->info('Available symbols: ' . implode(', ', $availableSymbols));
            } else {
                $this->warn('No active symbols found in the database.');
            }

            return Command::FAILURE;
        }

        // Get or prepare asset
        $asset = Asset::where('user_id', $user->id)
            ->where('symbol', $symbol)
            ->first();

        $currentAmount = $asset ? (float) $asset->amount : 0;
        $newAmount = $currentAmount + $amount;

        // Display summary
        $this->newLine();
        $this->info('📋 Asset Addition Summary');
        $this->table(
            ['Field', 'Value'],
            [
                ['User', $user->name . ' (' . $user->email . ')'],
                ['User ID', $user->id],
                ['Symbol', $symbol . ' (' . $symbolRecord->name . ')'],
                ['Current Balance', number_format($currentAmount, 8)],
                ['Amount to Add', '+' . number_format($amount, 8)],
                ['New Balance', number_format($newAmount, 8)],
            ]
        );

        if ($dryRun) {
            $this->newLine();
            $this->warn('🔍 DRY RUN MODE - No changes have been made.');
            $this->info('Remove --dry-run flag to apply changes.');

            return Command::SUCCESS;
        }

        // Confirm action (skip if --force)
        if (! $this->option('force') && ! $this->confirm('Do you want to proceed with this asset addition?')) {
            $this->info('Operation cancelled.');

            return Command::SUCCESS;
        }

        // Apply changes
        if ($asset) {
            $asset->update(['amount' => $newAmount]);
        } else {
            Asset::create([
                'user_id' => $user->id,
                'symbol' => $symbol,
                'amount' => $amount,
                'locked_amount' => 0,
            ]);
        }

        $this->newLine();
        $this->info("✅ Successfully added {$amount} {$symbol} to {$user->email}");
        $this->info("   New balance: {$newAmount} {$symbol}");

        return Command::SUCCESS;
    }
}

