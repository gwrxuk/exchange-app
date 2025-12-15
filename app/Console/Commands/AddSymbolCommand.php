<?php

namespace App\Console\Commands;

use App\Models\Symbol;
use Illuminate\Console\Command;

class AddSymbolCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:add-symbol 
                            {code : The symbol code (e.g., BTC, ETH)}
                            {name : The full name of the symbol (e.g., Bitcoin, Ethereum)}
                            {--inactive : Create the symbol as inactive}
                            {--dry-run : Preview the changes without applying them}
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a new trading symbol to the exchange';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $code = strtoupper($this->argument('code'));
        $name = $this->argument('name');
        $isActive = ! $this->option('inactive');
        $dryRun = $this->option('dry-run');

        // Validate code length
        if (strlen($code) > 10) {
            $this->error('❌ Symbol code must be 10 characters or less.');

            return Command::FAILURE;
        }

        // Check if symbol already exists
        $existingSymbol = Symbol::where('code', $code)->first();

        if ($existingSymbol) {
            $this->error("❌ Symbol '{$code}' already exists.");
            $this->newLine();
            $this->table(
                ['Field', 'Value'],
                [
                    ['Code', $existingSymbol->code],
                    ['Name', $existingSymbol->name],
                    ['Status', $existingSymbol->is_active ? 'Active' : 'Inactive'],
                    ['Created At', $existingSymbol->created_at->format('Y-m-d H:i:s')],
                ]
            );
            $this->newLine();
            $this->info('💡 Use app:update-symbol to modify an existing symbol.');

            return Command::FAILURE;
        }

        // Display summary
        $this->newLine();
        $this->info('📋 New Symbol Summary');
        $this->table(
            ['Field', 'Value'],
            [
                ['Code', $code],
                ['Name', $name],
                ['Status', $isActive ? 'Active' : 'Inactive'],
            ]
        );

        if ($dryRun) {
            $this->newLine();
            $this->warn('🔍 DRY RUN MODE - No changes have been made.');
            $this->info('Remove --dry-run flag to apply changes.');

            return Command::SUCCESS;
        }

        // Confirm action (skip if --force)
        if (! $this->option('force') && ! $this->confirm('Do you want to create this symbol?')) {
            $this->info('Operation cancelled.');

            return Command::SUCCESS;
        }

        // Create the symbol
        $symbol = Symbol::create([
            'code' => $code,
            'name' => $name,
            'is_active' => $isActive,
        ]);

        $this->newLine();
        $this->info("✅ Successfully created symbol '{$symbol->code}' ({$symbol->name})");

        if (! $isActive) {
            $this->warn('   Note: Symbol is inactive and cannot be traded until activated.');
        }

        // Show all symbols
        $this->newLine();
        $this->info('📊 All Trading Symbols:');
        $allSymbols = Symbol::orderBy('code')->get();
        $this->table(
            ['Code', 'Name', 'Status'],
            $allSymbols->map(fn ($s) => [
                $s->code,
                $s->name,
                $s->is_active ? '✅ Active' : '❌ Inactive',
            ])->toArray()
        );

        return Command::SUCCESS;
    }
}

