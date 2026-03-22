<?php

namespace App\Jobs;

use App\Models\KitType;
use App\Models\User;
use App\Notifications\KitTypeRefreshComplete;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RefreshSingleBrandJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    public int $tries = 3;

    /** @var int[] */
    public array $backoff = [30, 60, 120];

    public function __construct(public string $brand) {}

    public function handle(): void
    {
        $added = 0;
        $skipped = 0;
        $error = null;

        try {
            $items = $this->fetchBrandEquipment();

            foreach ($items as $item) {
                if (! $this->isValidItem($item)) {
                    continue;
                }

                $created = KitType::firstOrCreate(
                    ['name' => $item['name'], 'brand' => $this->brand],
                    $this->mapToKitType($item)
                );

                $created->wasRecentlyCreated ? $added++ : $skipped++;
            }
        } catch (\Throwable $e) {
            $error = "{$this->brand}: {$e->getMessage()}";
            Log::warning("KitType AI refresh failed for {$this->brand}", ['error' => $e->getMessage()]);
        }

        $totals = Cache::get('kit_types.refresh_total', []);
        $totals['done'] = ($totals['done'] ?? 0) + 1;
        $totals['added'] = ($totals['added'] ?? 0) + $added;
        $totals['skipped'] = ($totals['skipped'] ?? 0) + $skipped;
        $totals['ran_at'] = now()->toIso8601String();

        if ($error) {
            $totals['errors'][] = $error;
        }

        Cache::put('kit_types.refresh_total', $totals, now()->addDay());

        if (($totals['done'] ?? 0) >= ($totals['dispatched'] ?? PHP_INT_MAX)) {
            User::where('role', 'admin')->each(
                fn (User $u) => $u->notify(new KitTypeRefreshComplete($totals))
            );
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function fetchBrandEquipment(): array
    {
        $response = Http::withToken(config('services.xai.api_key'))
            ->retry(3, 1000, fn ($e) => $e instanceof RequestException && $e->response->status() === 429)
            ->timeout(90)
            ->post(config('services.xai.base_url').'/chat/completions', [
                'model' => config('services.xai.model'),
                'messages' => [
                    ['role' => 'system', 'content' => $this->systemPrompt()],
                    ['role' => 'user', 'content' => "Generate the equipment list for: {$this->brand}"],
                ],
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0.2,
            ]);

        $response->throw();

        $data = json_decode($response->json('choices.0.message.content'), true);

        return $data['equipment'] ?? [];
    }

    /** @param array<string, mixed> $item */
    private function isValidItem(array $item): bool
    {
        $name = trim($item['name'] ?? '');

        return strlen($name) >= 5 && preg_match('/[a-zA-Z]/', $name) === 1;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function mapToKitType(array $item): array
    {
        $resourceLinks = [];

        if (! empty($item['technical_pdf_url'])) {
            $resourceLinks[] = ['name' => 'Technical Datasheet', 'url' => $item['technical_pdf_url']];
        }

        if (! empty($item['instructions_pdf_url'])) {
            $resourceLinks[] = ['name' => 'Instructions for Use', 'url' => $item['instructions_pdf_url']];
        }

        return [
            'category' => $item['category'] ?? null,
            'interval_months' => max(1, (int) ($item['interval_months'] ?? 6)),
            'lifts_people' => (bool) ($item['lifts_people'] ?? false),
            'swl_description' => $item['swl_description'] ?? null,
            'inspection_price' => max(0, (float) ($item['inspection_price_gbp'] ?? 0)),
            'resources_links' => $resourceLinks ?: null,
            'checklist_json' => null,
            'instructions' => null,
            'ai_suggested' => true,
        ];
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
You are a LOLER (Lifting Operations and Lifting Equipment Regulations 1998) equipment expert for the United Kingdom working in 2026.

When given a brand name, return a comprehensive JSON object with a single key "equipment" containing an array of working-at-height safety products from that brand that are subject to LOLER thorough examination.

Include: harnesses, lanyards, energy absorbers, connectors/carabiners, descenders, ascenders, pulleys, ropes (dynamic and static), rigging plates, rigging blocks, foot ascenders, mechanical hitches, anchors, and other PPE used in rope access, arboriculture, tree surgery, industrial rope access, and rescue.

Each item must have these exact fields:
- name (string): exact product name as marketed — be specific (e.g. "Avao Bod Fast" not just "Harness"). Do not duplicate items.
- category (string): one of [Harness, Lanyard, Connector, Descender, Ascender, Pulley, Rope, Rigging Plate, Rigging Block, Foot Ascender, Mechanical Hitch, Energy Absorber, Anchor, Helmet, Other]
- interval_months (integer): LOLER inspection interval — 6 for most dynamic load-bearing equipment, 12 for lower-risk static items
- lifts_people (boolean): true if designed to support a person's weight
- swl_description (string): safe working load or max user weight per EN standards, e.g. "Max user weight: 150 kg (EN 361)"
- inspection_price_gbp (number): realistic UK trade price in GBP for a single LOLER thorough examination — typically £25–£80
- technical_pdf_url (string|null): full URL to official manufacturer technical datasheet PDF — only if you are highly confident it is real and publicly accessible in 2026, otherwise null
- instructions_pdf_url (string|null): full URL to official instructions for use PDF — same confidence rule, otherwise null

Rules:
- Only include currently manufactured products. Include discontinued products only if very common in UK workplaces in 2026.
- Do not duplicate items (same product in different colours counts as one).
- Do not invent PDF URLs. Only include URLs you are highly confident are real.
- Output must be valid JSON only. No markdown, no explanation, no extra text.
PROMPT;
    }
}
