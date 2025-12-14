<?php

namespace Database\Seeders;

use App\Models\Symbol;
use Illuminate\Database\Seeder;

class SymbolSeeder extends Seeder
{
    public function run(): void
    {
        $symbols = [
            ['code' => 'BTC', 'name' => 'Bitcoin', 'is_active' => true],
            ['code' => 'ETH', 'name' => 'Ethereum', 'is_active' => true],
        ];

        foreach ($symbols as $symbol) {
            Symbol::updateOrCreate(
                ['code' => $symbol['code']],
                $symbol
            );
        }
    }
}

