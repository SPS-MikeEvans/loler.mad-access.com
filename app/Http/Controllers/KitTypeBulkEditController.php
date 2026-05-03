<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkEditKitTypeRequest;
use App\Models\AuditLog;
use App\Models\KitType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class KitTypeBulkEditController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:admin');
    }

    public function form(Request $request): View|RedirectResponse
    {
        $ids = $this->normaliseIds($request->input('kit_type_ids'));

        if (empty($ids)) {
            return redirect()->route('kit-types.index')
                ->with('error', 'Select one or more kit types before bulk editing.');
        }

        $kitTypes = KitType::whereIn('id', $ids)->orderBy('name')->get();

        $existingUrls = $this->collectExistingResourceUrls($kitTypes);

        return view('kit-types.bulk-edit.form', compact('kitTypes', 'existingUrls'));
    }

    public function preview(BulkEditKitTypeRequest $request): View
    {
        $kitTypes = KitType::whereIn('id', $request->validated()['kit_type_ids'])
            ->orderBy('name')
            ->get();

        $diffs = $kitTypes->map(fn (KitType $type) => [
            'type' => $type,
            'changes' => $this->computeChange($type, $request),
        ]);

        return view('kit-types.bulk-edit.preview', [
            'kitTypes' => $kitTypes,
            'diffs' => $diffs,
            'request' => $request,
        ]);
    }

    public function apply(BulkEditKitTypeRequest $request): RedirectResponse
    {
        $kitTypes = KitType::whereIn('id', $request->validated()['kit_type_ids'])->get();
        $action = $request->input('action');
        $changedCount = 0;
        $skippedCount = 0;

        DB::transaction(function () use ($kitTypes, $request, $action, &$changedCount, &$skippedCount) {
            foreach ($kitTypes as $type) {
                $change = $this->computeChange($type, $request);

                if (! $change['will_change']) {
                    $skippedCount++;

                    continue;
                }

                $type->update($change['attributes']);
                $changedCount++;

                AuditLog::record(
                    'updated',
                    'KitType',
                    $type->id,
                    "Bulk edit ({$action}) applied to {$type->name}",
                    [
                        'bulk_action' => $action,
                        'field' => $change['field'],
                        'old' => $change['old'],
                        'new' => $change['new'],
                    ]
                );
            }
        });

        $message = "Bulk edit applied to {$changedCount} ".str('type')->plural($changedCount).'.';
        if ($skippedCount > 0) {
            $message .= " {$skippedCount} skipped (no change required).";
        }

        return redirect()->route('kit-types.index')->with('success', $message);
    }

    /**
     * @return array{field: string, old: mixed, new: mixed, attributes: array<string, mixed>, will_change: bool, summary: string}
     */
    private function computeChange(KitType $type, Request $request): array
    {
        $action = $request->input('action');

        return match ($action) {
            'set_price' => $this->setPrice($type, (float) $request->input('value')),
            'adjust_price_amount' => $this->adjustPrice($type, (float) $request->input('value'), absolute: true),
            'adjust_price_percent' => $this->adjustPrice($type, (float) $request->input('value'), absolute: false),
            'set_interval_months' => $this->setIntervalMonths($type, (int) $request->input('value')),
            'set_lifts_people' => $this->setLiftsPeople($type, $request->boolean('value')),
            'add_resource_link' => $this->addResourceLink($type, (string) $request->input('link_name'), (string) $request->input('link_url')),
            'remove_resource_link' => $this->removeResourceLink($type, (string) $request->input('link_url')),
            default => $this->noop($type),
        };
    }

    /** @return array{field: string, old: mixed, new: mixed, attributes: array<string, mixed>, will_change: bool, summary: string} */
    private function setPrice(KitType $type, float $value): array
    {
        $new = round($value, 2);

        return $this->priceChange($type, $new, "Set to £{$this->formatPrice($new)}");
    }

    /** @return array{field: string, old: mixed, new: mixed, attributes: array<string, mixed>, will_change: bool, summary: string} */
    private function adjustPrice(KitType $type, float $delta, bool $absolute): array
    {
        $current = (float) ($type->inspection_price ?? 0);
        $new = $absolute ? $current + $delta : $current * (1 + ($delta / 100));
        $new = max(0, round($new, 2));
        $summary = $absolute
            ? sprintf('%s £%s', $delta >= 0 ? '+' : '−', $this->formatPrice(abs($delta)))
            : sprintf('%s%s%%', $delta >= 0 ? '+' : '', $delta);

        return $this->priceChange($type, $new, $summary);
    }

    /** @return array{field: string, old: mixed, new: mixed, attributes: array<string, mixed>, will_change: bool, summary: string} */
    private function priceChange(KitType $type, float $new, string $summary): array
    {
        $old = $type->inspection_price !== null ? (float) $type->inspection_price : null;
        $oldRounded = $old !== null ? round($old, 2) : null;
        $willChange = $oldRounded === null || abs($oldRounded - $new) > 0.001;

        return [
            'field' => 'inspection_price',
            'old' => $oldRounded,
            'new' => $new,
            'attributes' => ['inspection_price' => $new],
            'will_change' => $willChange,
            'summary' => $summary,
        ];
    }

    /** @return array{field: string, old: mixed, new: mixed, attributes: array<string, mixed>, will_change: bool, summary: string} */
    private function setIntervalMonths(KitType $type, int $value): array
    {
        $old = (int) $type->interval_months;

        return [
            'field' => 'interval_months',
            'old' => $old,
            'new' => $value,
            'attributes' => ['interval_months' => $value],
            'will_change' => $old !== $value,
            'summary' => "Set interval to {$value} months",
        ];
    }

    /** @return array{field: string, old: mixed, new: mixed, attributes: array<string, mixed>, will_change: bool, summary: string} */
    private function setLiftsPeople(KitType $type, bool $value): array
    {
        $old = (bool) $type->lifts_people;

        return [
            'field' => 'lifts_people',
            'old' => $old,
            'new' => $value,
            'attributes' => ['lifts_people' => $value],
            'will_change' => $old !== $value,
            'summary' => $value ? 'Set to lifts people = Yes' : 'Set to lifts people = No',
        ];
    }

    /** @return array{field: string, old: mixed, new: mixed, attributes: array<string, mixed>, will_change: bool, summary: string} */
    private function addResourceLink(KitType $type, string $name, string $url): array
    {
        $links = $this->normaliseLinks($type->resources_links);
        $alreadyPresent = collect($links)->contains(fn ($l) => ($l['url'] ?? '') === $url);

        if ($alreadyPresent) {
            return [
                'field' => 'resources_links',
                'old' => $links,
                'new' => $links,
                'attributes' => [],
                'will_change' => false,
                'summary' => "Already has link {$url} — skipped",
            ];
        }

        $newLinks = array_merge($links, [['name' => $name, 'url' => $url]]);

        return [
            'field' => 'resources_links',
            'old' => $links,
            'new' => $newLinks,
            'attributes' => ['resources_links' => $newLinks],
            'will_change' => true,
            'summary' => "Append link: {$name} ({$url})",
        ];
    }

    /** @return array{field: string, old: mixed, new: mixed, attributes: array<string, mixed>, will_change: bool, summary: string} */
    private function removeResourceLink(KitType $type, string $url): array
    {
        $links = $this->normaliseLinks($type->resources_links);
        $newLinks = array_values(array_filter($links, fn ($l) => ($l['url'] ?? '') !== $url));

        if (count($newLinks) === count($links)) {
            return [
                'field' => 'resources_links',
                'old' => $links,
                'new' => $links,
                'attributes' => [],
                'will_change' => false,
                'summary' => "Link {$url} not present — skipped",
            ];
        }

        return [
            'field' => 'resources_links',
            'old' => $links,
            'new' => $newLinks,
            'attributes' => ['resources_links' => $newLinks ?: null],
            'will_change' => true,
            'summary' => "Remove link {$url}",
        ];
    }

    /** @return array{field: string, old: mixed, new: mixed, attributes: array<string, mixed>, will_change: bool, summary: string} */
    private function noop(KitType $type): array
    {
        return [
            'field' => '',
            'old' => null,
            'new' => null,
            'attributes' => [],
            'will_change' => false,
            'summary' => 'No-op',
        ];
    }

    /**
     * @param  array<int, array<string, string>>|null  $links
     * @return array<int, array<string, string>>
     */
    private function normaliseLinks(?array $links): array
    {
        return collect($links ?? [])
            ->filter(fn ($l) => is_array($l) && ! empty($l['url'] ?? ''))
            ->values()
            ->all();
    }

    /** @return Collection<int, array{name: string, url: string}> */
    private function collectExistingResourceUrls(Collection $kitTypes): Collection
    {
        return $kitTypes
            ->flatMap(fn (KitType $t) => $this->normaliseLinks($t->resources_links))
            ->unique('url')
            ->sortBy('url')
            ->values();
    }

    /**
     * @param  mixed  $raw
     * @return array<int, int>
     */
    private function normaliseIds($raw): array
    {
        $values = is_array($raw) ? $raw : (is_string($raw) ? explode(',', $raw) : []);

        return collect($values)
            ->map(fn ($v) => (int) $v)
            ->filter(fn ($v) => $v > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function formatPrice(float $value): string
    {
        return number_format($value, 2);
    }
}
