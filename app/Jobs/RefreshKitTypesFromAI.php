<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class RefreshKitTypesFromAI implements ShouldQueue
{
    use Queueable;

    public int $timeout = 30;

    public int $tries = 1;

    /** @var string[] */
    public const BRANDS = [
        'Petzl',
        'DMM Professional',
        'ISC (International Safety Components)',
        'Edelrid',
        'Teufelberger',
        'CAMP Safety',
        'Skylotec',
        'Singing Rock',
        'Kong',
        'Rock Exotica',
        'Courant',
        'Notch Equipment',
        'Marlow Ropes',
        'Yale Cordage',
    ];

    public function handle(): void
    {
        Cache::put('kit_types.refresh_total', [
            'dispatched' => count(self::BRANDS),
            'done' => 0,
            'added' => 0,
            'skipped' => 0,
            'errors' => [],
            'started_at' => now()->toIso8601String(),
        ], now()->addDay());

        foreach (self::BRANDS as $brand) {
            RefreshSingleBrandJob::dispatch($brand);
        }
    }
}
