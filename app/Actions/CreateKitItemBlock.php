<?php

namespace App\Actions;

use App\Models\Client;
use App\Models\KitItem;
use App\Models\KitType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateKitItemBlock
{
    /** @return Collection<int, KitItem> */
    public function execute(Client $client, array $data, bool $pendingReview = false): Collection
    {
        $quantity = (int) ($data['quantity'] ?? 1);
        $prefix = trim((string) ($data['asset_tag_prefix'] ?? ''));
        $start = (int) ($data['asset_tag_start'] ?? 1);
        $padding = (int) ($data['asset_tag_padding'] ?? 3);
        $isBlock = $quantity > 1 || $prefix !== '';
        $assetTags = $this->assetTags($quantity, $prefix, $start, $padding, $data['asset_tag'] ?? null, $isBlock);

        $this->ensureAssetTagsAreAvailable($assetTags, $isBlock ? 'asset_tag_prefix' : 'asset_tag');

        $baseData = collect($data)
            ->except(['quantity', 'asset_tag_prefix', 'asset_tag_start', 'asset_tag_padding', 'asset_tag'])
            ->all();

        if ($isBlock) {
            unset($baseData['serial_no'], $baseData['kit_group_id'], $baseData['new_group_name']);
        }

        $baseData['client_id'] = $client->id;
        $baseData['lifting_people'] = (bool) ($baseData['lifting_people'] ?? false);

        if ($pendingReview) {
            $baseData['pending_review'] = true;
        }

        if (! empty($baseData['kit_type_id'])) {
            $kitType = KitType::find($baseData['kit_type_id']);
            $startDate = $baseData['first_use_date'] ?? $baseData['purchase_date'] ?? null;

            if ($kitType && $startDate) {
                $intervalMonths = $baseData['lifting_people']
                    ? min(6, $kitType->interval_months)
                    : $kitType->interval_months;
                $baseData['next_inspection_due'] = Carbon::parse($startDate)->addMonths($intervalMonths);
            }
        }

        return DB::transaction(function () use ($client, $quantity, $baseData, $assetTags) {
            $items = new Collection();

            for ($index = 0; $index < $quantity; $index++) {
                $items->push($client->kitItems()->create(array_merge($baseData, [
                    'asset_tag' => $assetTags[$index],
                ])));
            }

            return $items;
        });
    }

    /** @return array<int, string|null> */
    private function assetTags(int $quantity, string $prefix, int $start, int $padding, ?string $singleAssetTag, bool $isBlock): array
    {
        if (! $isBlock) {
            return [$singleAssetTag ?: null];
        }

        if ($prefix === '') {
            return array_fill(0, $quantity, null);
        }

        return collect(range(0, $quantity - 1))
            ->map(fn (int $offset) => $prefix.str_pad((string) ($start + $offset), $padding, '0', STR_PAD_LEFT))
            ->all();
    }

    /** @param array<int, string|null> $assetTags */
    private function ensureAssetTagsAreAvailable(array $assetTags, string $field): void
    {
        $tags = collect($assetTags)->filter()->values();

        if ($tags->isEmpty()) {
            return;
        }

        $existing = KitItem::withTrashed()
            ->whereIn('asset_tag', $tags)
            ->pluck('asset_tag')
            ->all();

        if (! empty($existing)) {
            throw ValidationException::withMessages([
                $field => 'One or more asset tags already exist: '.implode(', ', $existing),
            ]);
        }
    }
}
