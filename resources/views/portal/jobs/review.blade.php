<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            Request Inspection — Step 3 of 3
        </h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-3xl mx-auto mobile-shell">
            <div class="mobile-card sm:rounded-lg">
                <div class="mobile-card-body">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Review &amp; Submit</h3>
                    <p class="text-sm text-gray-600 mb-4">Please review your inspection request before submitting.</p>

                    @if ($errors->any())
                        <div class="mb-4 px-4 py-3 bg-red-100 text-red-800 rounded-lg text-sm">
                            @foreach ($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif

                    <div class="mb-5 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                        <div class="bg-gray-50 rounded-lg p-3">
                            <p class="text-xs uppercase tracking-wide text-gray-500">Drop-off Date</p>
                            <p class="mt-1 font-semibold text-gray-900">{{ \Illuminate\Support\Carbon::parse($dropOffAt)->format('l, d M Y') }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3">
                            <p class="text-xs uppercase tracking-wide text-gray-500">Estimated Cost</p>
                            <p class="mt-1 font-semibold text-gray-900">£{{ number_format($costPence / 100, 2) }}</p>
                            @if ($missingPriceCount > 0)
                                <p class="mt-1 text-xs text-amber-700">{{ $missingPriceCount }} {{ Str::plural('item', $missingPriceCount) }} missing a listed price — confirmation by staff.</p>
                            @endif
                        </div>
                    </div>

                    <div class="mb-5">
                        <p class="text-xs uppercase tracking-wide text-gray-500 mb-2">Items ({{ $items->count() }})</p>
                        <div class="overflow-x-auto border border-gray-200 rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Asset / Serial</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Group</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Price</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($items as $item)
                                        <tr>
                                            <td class="px-4 py-2 text-gray-900">{{ $item->typeName() }}</td>
                                            <td class="px-4 py-2 text-gray-600">{{ $item->asset_tag ?? $item->serial_no ?? '—' }}</td>
                                            <td class="px-4 py-2 text-gray-600">{{ $item->kitGroup?->name ?? '—' }}</td>
                                            <td class="px-4 py-2 text-right text-gray-700">
                                                @if ($item->kitType?->inspection_price !== null)
                                                    £{{ number_format($item->kitType->inspection_price, 2) }}
                                                @else
                                                    £—
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if ($notes)
                        <div class="mb-5">
                            <p class="text-xs uppercase tracking-wide text-gray-500 mb-1">Your Notes</p>
                            <p class="text-sm text-gray-800 bg-gray-50 rounded-lg p-3 whitespace-pre-line">{{ $notes }}</p>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('portal.jobs.store') }}">
                        @csrf
                        @foreach ($items as $item)
                            <input type="hidden" name="kit_item_ids[]" value="{{ $item->id }}" />
                        @endforeach
                        <input type="hidden" name="drop_off_at" value="{{ $dropOffAt }}" />
                        <input type="hidden" name="notes" value="{{ $notes }}" />

                        <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3 pt-2">
                            <a href="{{ route('portal.jobs.date') }}"
                               onclick="event.preventDefault(); document.getElementById('back-form').submit();"
                               class="text-sm text-brand-navy hover:text-brand-red text-center">← Back</a>
                            <x-primary-button>Submit Request</x-primary-button>
                        </div>
                    </form>

                    <form id="back-form" method="POST" action="{{ route('portal.jobs.date') }}" class="hidden">
                        @csrf
                        @foreach ($items as $item)
                            <input type="hidden" name="kit_item_ids[]" value="{{ $item->id }}" />
                        @endforeach
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
