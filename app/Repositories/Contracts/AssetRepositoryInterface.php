<?php

namespace App\Repositories\Contracts;

use App\Models\Asset;

interface AssetRepositoryInterface
{
    public function findByUserAndSymbol(int $userId, string $symbol): ?Asset;
    public function lockForUpdate(int $userId, string $symbol): ?Asset;
    public function createOrUpdate(int $userId, string $symbol, array $data): Asset;
    public function update(Asset $asset, array $data): bool;
}

