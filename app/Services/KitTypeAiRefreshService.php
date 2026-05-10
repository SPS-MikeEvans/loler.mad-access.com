<?php

namespace App\Services;

use App\Models\KitBrand;
use App\Models\KitType;
use App\Models\User;
use App\Notifications\KitTypeRefreshComplete;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KitTypeAiRefreshService
{
    /** @return array<string, mixed> */
    public function run(): array
    {
        set_time_limit(240);

        $brands = KitBrand::active()->orderBy('name')->pluck('name')->all();
        $brandCount = count($brands);

        $added = 0;
        $skipped = 0;
        $updated = 0;
        $errors = [];

        Cache::put('kit_types.refresh_total', [
            'dispatched' => $brandCount,
            'done' => 0,
            'added' => 0,
            'skipped' => 0,
            'updated' => 0,
            'errors' => [],
            'started_at' => now()->toIso8601String(),
        ], now()->addDay());

        if ($brandCount === 0) {
            $totals = [
                'dispatched' => 0,
                'done' => 0,
                'added' => 0,
                'skipped' => 0,
                'updated' => 0,
                'errors' => ['No active brands. Add brands at /kit-brands.'],
                'ran_at' => now()->toIso8601String(),
            ];
            Cache::put('kit_types.refresh_total', $totals, now()->addDay());

            return $totals;
        }

        // Pass 1 — broad sweep per brand.
        $pass1 = $this->dispatchPool($brands, fn (string $brand) => null);

        foreach ($brands as $brand) {
            $result = $this->processResponse($pass1[$brand], $brand);
            $added += $result['added'];
            $skipped += $result['skipped'];
            $updated += $result['updated'];
            $errors = array_merge($errors, $result['errors']);
        }

        // Pass 2 — gap fill per brand, telling the model what we already have.
        $pass2 = $this->dispatchPool(
            $brands,
            fn (string $brand) => KitType::where('brand', $brand)->orderBy('name')->pluck('name')->all()
        );

        foreach ($brands as $brand) {
            $result = $this->processResponse($pass2[$brand], $brand);
            $added += $result['added'];
            $skipped += $result['skipped'];
            $updated += $result['updated'];
            $errors = array_merge($errors, $result['errors']);
        }

        $totals = [
            'dispatched' => $brandCount,
            'done' => $brandCount,
            'added' => $added,
            'skipped' => $skipped,
            'updated' => $updated,
            'errors' => $errors,
            'ran_at' => now()->toIso8601String(),
        ];

        Cache::put('kit_types.refresh_total', $totals, now()->addDay());

        User::where('role', 'admin')->each(
            fn (User $u) => $u->notify(new KitTypeRefreshComplete($totals))
        );

        return $totals;
    }

    /**
     * Dispatch one Http pool for all brands, building each request via the closure.
     *
     * @param  string[]  $brands
     * @param  \Closure(string): ?array<int, string>  $existingNamesFor
     * @return array<string, mixed>
     */
    private function dispatchPool(array $brands, \Closure $existingNamesFor): array
    {
        return Http::pool(function ($pool) use ($brands, $existingNamesFor) {
            return collect($brands)->map(
                fn (string $brand) => $pool->as($brand)
                    ->withToken(config('services.xai.api_key'))
                    ->timeout(120)
                    ->post(config('services.xai.base_url').'/chat/completions', [
                        'model' => config('services.xai.model'),
                        'messages' => [
                            ['role' => 'system', 'content' => $this->systemPrompt()],
                            ['role' => 'user', 'content' => $this->userPrompt($brand, $existingNamesFor($brand))],
                        ],
                        'response_format' => ['type' => 'json_object'],
                        'temperature' => 0.1,
                    ])
            )->all();
        });
    }

    /**
     * @param  mixed  $response
     * @return array{added: int, skipped: int, updated: int, errors: array<int, string>}
     */
    private function processResponse($response, string $brand): array
    {
        $added = 0;
        $skipped = 0;
        $updated = 0;
        $errors = [];

        if ($response instanceof \Throwable) {
            $errors[] = "{$brand}: {$response->getMessage()}";
            Log::warning("KitType AI refresh failed for {$brand}", ['error' => $response->getMessage()]);

            return compact('added', 'skipped', 'updated', 'errors');
        }

        if (! $response->successful()) {
            $errors[] = "{$brand}: HTTP {$response->status()} — {$response->body()}";
            Log::warning("KitType AI refresh HTTP error for {$brand}", ['status' => $response->status(), 'body' => $response->body()]);

            return compact('added', 'skipped', 'updated', 'errors');
        }

        try {
            $data = json_decode($response->json('choices.0.message.content'), true);
            $items = $data['equipment'] ?? [];

            foreach ($items as $item) {
                if (! $this->isValidItem($item)) {
                    continue;
                }

                $outcome = $this->upsertItem($brand, $item);
                $added += $outcome['added'];
                $skipped += $outcome['skipped'];
                $updated += $outcome['updated'];
            }
        } catch (\Throwable $e) {
            $errors[] = "{$brand}: {$e->getMessage()}";
            Log::warning("KitType AI refresh parse error for {$brand}", ['error' => $e->getMessage()]);
        }

        return compact('added', 'skipped', 'updated', 'errors');
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array{added: int, skipped: int, updated: int}
     */
    private function upsertItem(string $brand, array $item): array
    {
        $existing = KitType::where('name', $item['name'])->where('brand', $brand)->first();
        $mapped = $this->mapToKitType($item);

        if (! $existing) {
            KitType::create(array_merge($mapped, ['name' => $item['name'], 'brand' => $brand]));

            return ['added' => 1, 'skipped' => 0, 'updated' => 0];
        }

        // Admin-edited rows are sacred.
        if (! $existing->ai_suggested) {
            return ['added' => 0, 'skipped' => 1, 'updated' => 0];
        }

        // Additive merge: only fill blanks, never overwrite existing data.
        $patch = collect($mapped)
            ->reject(fn ($value, $key) => filled($existing->getAttribute($key)) || $value === null)
            ->all();

        if (empty($patch)) {
            return ['added' => 0, 'skipped' => 1, 'updated' => 0];
        }

        $existing->update($patch);

        return ['added' => 0, 'skipped' => 0, 'updated' => 1];
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

    /** @param  array<int, string>|null  $existingNames */
    private function userPrompt(string $brand, ?array $existingNames): string
    {
        if ($existingNames === null || count($existingNames) === 0) {
            return "Generate the equipment list for: {$brand}";
        }

        $list = implode("\n", array_map(fn (string $n) => "- {$n}", $existingNames));

        return <<<MSG
Generate the equipment list for: {$brand}.

We already have these {$brand} products in our database. Return ONLY products NOT in this list. Focus on legacy and less-common models we may have missed:

{$list}
MSG;
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
You are a LOLER (Lifting Operations and Lifting Equipment Regulations 1998) equipment expert for the United Kingdom working in 2026.

When given a brand name, return a comprehensive JSON object with a single key "equipment" containing an array of working-at-height safety products from that brand that are subject to LOLER thorough examination.

Include: harnesses, lanyards, energy absorbers, connectors/carabiners, descenders, ascenders, pulleys, ropes (dynamic and static), rigging plates, rigging blocks, foot ascenders, mechanical hitches, anchors, and other PPE used in rope access, arboriculture, tree surgery, industrial rope access, and rescue.

Each item must have these exact fields:
- name (string): exact product name as marketed — be specific (e.g. "Anchor Ring 26mm" not just "Anchor Ring"). Do not duplicate items.
- category (string): one of [Harness, Lanyard, Connector, Descender, Ascender, Pulley, Rope, Rigging Plate, Rigging Block, Foot Ascender, Mechanical Hitch, Energy Absorber, Anchor, Helmet, Other]
- interval_months (integer): LOLER inspection interval — 6 for most dynamic load-bearing equipment, 12 for lower-risk static items
- lifts_people (boolean): true if designed to support a person's weight
- swl_description (string): safe working load or max user weight per EN standards, e.g. "Max user weight: 150 kg (EN 361)"
- inspection_price_gbp (number): realistic UK trade price in GBP for a single LOLER thorough examination — typically £25–£80
- technical_pdf_url (string|null): the exact, complete URL to this product's user instructions or instructions-for-use PDF on the manufacturer's official website, based on your training knowledge of their document library (e.g. https://www.petzl.com/files/pdf/...). Include it if you are confident the URL existed and was publicly accessible. Set to null if unsure.
- instructions_pdf_url (string|null): the exact, complete URL to this product's technical datasheet or declaration of conformity PDF on the manufacturer's official website. Include it if you are confident the URL existed and was publicly accessible. Set to null if unsure.

Rules:
- Prioritise finding real document URLs. Manufacturers like Petzl, DMM, Edelrid, and Singing Rock publish PDFs on their websites under consistent URL patterns — use your training knowledge of these patterns.
- Do NOT construct or guess URLs. Only include a URL if you have seen it or are highly confident it resolves to the correct document.
- If you are not at least 90% certain a URL is real and current as of 2026, return null for both PDF fields.
- Include every product that a UK rope-access, arboriculture, or working-at-height customer might plausibly still own and need a LOLER thorough examination on. This means BOTH current production AND legacy models still in service — if a product was sold in the UK at any point and could still be in use today, include it. There is no cut-off year; aim to be comprehensive across the brand's catalogue history, including discontinued items still encountered in field inspections.
- Do not duplicate items (same product in different colours or sizes counts as one entry).
- Output must be valid JSON only. No markdown, no explanation, no extra text.
PROMPT;
    }
}
