<?php

namespace App\Repositories\Eloquent;

use App\Models\Asset;
use App\Repositories\Contracts\AssetRepositoryInterface;

class EloquentAssetRepository implements AssetRepositoryInterface
{
    public function findByUserAndSymbol(int $userId, string $symbol): ?Asset
    {
        return Asset::where('user_id', $userId)->where('symbol', $symbol)->first();
    }

    public function lockForUpdate(int $userId, string $symbol): ?Asset
    {
        return Asset::where('user_id', $userId)->where('symbol', $symbol)->lockForUpdate()->first();
    }

    public function createOrUpdate(int $userId, string $symbol, array $data): Asset
    {
        return Asset::updateOrCreate(
            ['user_id' => $userId, 'symbol' => $symbol],
            $data
        );
    }

    public function update(Asset $asset, array $data): bool
    {
        return $asset->update($data);
    }
}

