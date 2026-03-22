<?php

namespace App\Services;

use App\Models\KitType;
use App\Models\User;
use App\Notifications\KitTypeRefreshComplete;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KitTypeAiRefreshService
{
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

    /** @return array<string, mixed> */
    public function run(): array
    {
        set_time_limit(180);

        $added = 0;
        $skipped = 0;
        $errors = [];

        Cache::put('kit_types.refresh_total', [
            'dispatched' => count(self::BRANDS),
            'done' => 0,
            'added' => 0,
            'skipped' => 0,
            'errors' => [],
            'started_at' => now()->toIso8601String(),
        ], now()->addDay());

        $responses = Http::pool(function ($pool) {
            return collect(self::BRANDS)->map(
                fn (string $brand) => $pool->as($brand)
                    ->withToken(config('services.xai.api_key'))
                    ->timeout(120)
                    ->post(config('services.xai.base_url').'/chat/completions', [
                        'model' => config('services.xai.model'),
                        'messages' => [
                            ['role' => 'system', 'content' => $this->systemPrompt()],
                            ['role' => 'user', 'content' => "Search the {$brand} website and generate the equipment list for: {$brand}"],
                        ],
                        'response_format' => ['type' => 'json_object'],
                        'temperature' => 0.1,
                        'search_parameters' => ['mode' => 'on'],
                    ])
            )->all();
        });

        foreach (self::BRANDS as $brand) {
            $response = $responses[$brand];

            if ($response instanceof \Throwable) {
                $errors[] = "{$brand}: {$response->getMessage()}";
                Log::warning("KitType AI refresh failed for {$brand}", ['error' => $response->getMessage()]);

                continue;
            }

            if (! $response->successful()) {
                $errors[] = "{$brand}: HTTP {$response->status()} — {$response->body()}";
                Log::warning("KitType AI refresh HTTP error for {$brand}", ['status' => $response->status(), 'body' => $response->body()]);

                continue;
            }

            try {
                $data = json_decode($response->json('choices.0.message.content'), true);
                $items = $data['equipment'] ?? [];

                foreach ($items as $item) {
                    if (! $this->isValidItem($item)) {
                        continue;
                    }

                    $created = KitType::firstOrCreate(
                        ['name' => $item['name'], 'brand' => $brand],
                        $this->mapToKitType($item)
                    );

                    $created->wasRecentlyCreated ? $added++ : $skipped++;
                }
            } catch (\Throwable $e) {
                $errors[] = "{$brand}: {$e->getMessage()}";
                Log::warning("KitType AI refresh parse error for {$brand}", ['error' => $e->getMessage()]);
            }
        }

        $totals = [
            'dispatched' => count(self::BRANDS),
            'done' => count(self::BRANDS),
            'added' => $added,
            'skipped' => $skipped,
            'errors' => $errors,
            'ran_at' => now()->toIso8601String(),
        ];

        Cache::put('kit_types.refresh_total', $totals, now()->addDay());

        User::where('role', 'admin')->each(
            fn (User $u) => $u->notify(new KitTypeRefreshComplete($totals))
        );

        return $totals;
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
You are a LOLER (Lifting Operations and Lifting Equipment Regulations 1998) equipment expert for the United Kingdom working in 2026. You have live web search available — use it.

When given a brand name, you must:
1. Search the brand's official website to find their current professional/industrial product range.
2. For each product, search for its Documents, Product Specifications or similar section on the product page to find PDF links for user instructions and technical datasheets.
3. Return a comprehensive JSON object with a single key "equipment" containing an array of products subject to LOLER thorough examination.

Include: harnesses, lanyards, energy absorbers, connectors/carabiners, descenders, ascenders, pulleys, ropes (dynamic and static), rigging plates, rigging blocks, foot ascenders, mechanical hitches, anchors, and other PPE used in rope access, arboriculture, tree surgery, industrial rope access, and rescue.

Each item must have these exact fields:
- name (string): exact product name as marketed — be specific (e.g. "Anchor Ring 26mm" not just "Anchor Ring"). Do not duplicate items.
- category (string): one of [Harness, Lanyard, Connector, Descender, Ascender, Pulley, Rope, Rigging Plate, Rigging Block, Foot Ascender, Mechanical Hitch, Energy Absorber, Anchor, Helmet, Other]
- interval_months (integer): LOLER inspection interval — 6 for most dynamic load-bearing equipment, 12 for lower-risk static items
- lifts_people (boolean): true if designed to support a person's weight
- swl_description (string): safe working load or max user weight per EN standards, e.g. "Max user weight: 150 kg (EN 361)"
- inspection_price_gbp (number): realistic UK trade price in GBP for a single LOLER thorough examination — typically £25–£80
- technical_pdf_url (string|null): direct URL to the manufacturer's technical datasheet or declaration of conformity PDF found on their website — verify the link resolves before including it, otherwise null
- instructions_pdf_url (string|null): direct URL to the manufacturer's instructions for use PDF found on their website — verify the link resolves before including it, otherwise null

Rules:
- Use web search to find real PDF document URLs from the manufacturer's own website. Do not guess or construct URLs.
- Only include currently manufactured products. Include discontinued products only if very common in UK workplaces in 2026.
- Do not duplicate items (same product in different colours or sizes counts as one entry).
- Output must be valid JSON only. No markdown, no explanation, no extra text.
PROMPT;
    }
}
