<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            LOLER Inspection — {{ $kitItem->kitType->name }}
            @if ($kitItem->asset_tag)
                <span class="text-gray-500 font-normal text-lg">({{ $kitItem->asset_tag }})</span>
            @endif
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if ($errors->any())
                <div class="px-4 py-3 bg-red-100 text-red-800 rounded-lg">
                    <strong>Please correct the errors below.</strong>
                    <ul class="mt-1 list-disc list-inside text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($usingDefault)
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-lg">
                    <p class="text-sm text-yellow-800">
                        <strong>Note:</strong> No specific checklist has been defined for this equipment type.
                        A generic LOLER thorough examination checklist is being used.
                        You can add a specific checklist under <a href="{{ route('kit-types.edit', $kitItem->kitType) }}" class="underline font-medium">Kit Types</a>.
                    </p>
                </div>
            @endif

            {{-- Instructions panel --}}
            @php
                $kitType = $kitItem->kitType;
                $hasPanel = $instructions || !empty($links) || $kitType->spec_pdf_path || $kitType->inspection_pdf_path;
            @endphp
            @if ($hasPanel)
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-5">
                    <h3 class="font-semibold text-blue-900 mb-3">Inspection Instructions</h3>

                    @if ($instructions)
                        <div class="text-sm text-blue-800 whitespace-pre-line mb-3">{{ $instructions }}</div>
                    @endif

                    @if ($kitType->spec_pdf_path || $kitType->inspection_pdf_path || !empty($links))
                        <div class="flex flex-wrap gap-3">
                            @if ($kitType->spec_pdf_path)
                                <a href="{{ Storage::url($kitType->spec_pdf_path) }}" target="_blank" rel="noopener"
                                   class="inline-flex items-center gap-1.5 text-sm font-medium text-red-700 bg-red-50 border border-red-200 rounded px-3 py-1.5 hover:bg-red-100">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/></svg>
                                    Product Spec PDF ↗
                                </a>
                            @endif
                            @if ($kitType->inspection_pdf_path)
                                <a href="{{ Storage::url($kitType->inspection_pdf_path) }}" target="_blank" rel="noopener"
                                   class="inline-flex items-center gap-1.5 text-sm font-medium text-red-700 bg-red-50 border border-red-200 rounded px-3 py-1.5 hover:bg-red-100">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/></svg>
                                    Inspection Procedure PDF ↗
                                </a>
                            @endif
                            @foreach ($links as $link)
                                <a href="{{ $link['url'] }}" target="_blank" rel="noopener"
                                   class="text-sm text-blue-700 underline hover:text-blue-900 self-center">
                                    {{ $link['name'] }} ↗
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            {{-- Kit item summary --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-4">
                <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Client</dt>
                        <dd class="mt-1 text-gray-900">{{ $kitItem->client->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Manufacturer</dt>
                        <dd class="mt-1 text-gray-900">{{ $kitItem->manufacturer ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Serial No.</dt>
                        <dd class="mt-1 text-gray-900">{{ $kitItem->serial_no ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">SWL</dt>
                        <dd class="mt-1 text-gray-900">{{ $kitItem->swl_kg ? $kitItem->swl_kg . ' kg' : '—' }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Inspection form --}}
            <form method="POST"
                  action="{{ route('clients.kit-items.inspections.store', [$client, $kitItem]) }}"
                  enctype="multipart/form-data"
                  class="space-y-6">
                @csrf

                {{-- Dates --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="inspection_date" :value="__('Inspection Date')" />
                            <x-text-input id="inspection_date" name="inspection_date" type="date"
                                class="mt-1 block w-full"
                                :value="old('inspection_date', now()->format('Y-m-d'))" required />
                            <x-input-error :messages="$errors->get('inspection_date')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="next_due_date" :value="__('Next Inspection Due')" />
                            <x-text-input id="next_due_date" name="next_due_date" type="date"
                                class="mt-1 block w-full"
                                :value="old('next_due_date', now()->addMonths($kitItem->kitType->interval_months)->format('Y-m-d'))"
                                required />
                            <x-input-error :messages="$errors->get('next_due_date')" class="mt-2" />
                        </div>
                    </div>
                </div>

                {{-- Checklist --}}
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="font-semibold text-gray-900">Checklist</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-2/5">Check</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-28">Result</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-40">Photo</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($checklist as $i => $check)
                                    <tr x-data="{ status: '{{ old("checks.{$i}.status", 'pass') }}' }"
                                        x-bind:class="status === 'fail' ? 'bg-red-50' : (status === 'n/a' ? 'bg-gray-50' : '')">
                                        <td class="px-4 py-3">
                                            <input type="hidden" name="checks[{{ $i }}][check_category]" value="{{ $check['category'] }}">
                                            <input type="hidden" name="checks[{{ $i }}][check_text]" value="{{ $check['text'] }}">
                                            <span class="inline-block text-xs font-medium text-brand-navy bg-brand-blue/20 rounded px-1.5 py-0.5 mr-1">{{ $check['category'] }}</span>
                                            <span class="text-sm text-gray-900">{{ $check['text'] }}</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <select name="checks[{{ $i }}][status]"
                                                x-model="status"
                                                class="block w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-brand-red focus:ring-brand-red">
                                                <option value="pass" {{ old("checks.{$i}.status", 'pass') === 'pass' ? 'selected' : '' }}>Pass</option>
                                                <option value="fail" {{ old("checks.{$i}.status") === 'fail' ? 'selected' : '' }}>Fail</option>
                                                <option value="n/a" {{ old("checks.{$i}.status") === 'n/a' ? 'selected' : '' }}>N/A</option>
                                            </select>
                                        </td>
                                        <td class="px-4 py-3">
                                            <textarea name="checks[{{ $i }}][notes]" rows="1"
                                                class="block w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-brand-red focus:ring-brand-red"
                                                placeholder="Optional notes…">{{ old("checks.{$i}.notes") }}</textarea>
                                        </td>
                                        <td class="px-4 py-3 align-top" x-data="phoneScan('{{ $uploadTokens[$i] }}')">
                                            <input type="hidden" name="checks[{{ $i }}][upload_token]" value="{{ $uploadTokens[$i] }}">

                                            {{-- Direct file upload --}}
                                            <input type="file" name="checks[{{ $i }}][photo]"
                                                accept="image/*"
                                                class="block w-full text-xs text-gray-500 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:bg-brand-navy/10 file:text-brand-navy hover:file:bg-brand-navy/20 mb-2">

                                            {{-- Phone scan button --}}
                                            <button type="button" @click="open = true"
                                                class="inline-flex items-center gap-1 px-2 py-1 text-xs bg-brand-blue/20 text-brand-navy border border-brand-navy/20 rounded hover:bg-brand-navy/10 transition">
                                                📷 Scan to Upload
                                                <span x-show="photos.length > 0" x-text="photos.length"
                                                      class="bg-brand-navy text-white rounded-full w-4 h-4 flex items-center justify-center text-xs leading-none"></span>
                                            </button>

                                            {{-- Phone-uploaded thumbnails --}}
                                            <div x-show="photos.length > 0" class="flex gap-1 mt-1.5 flex-wrap">
                                                <template x-for="p in photos" :key="p.url">
                                                    <img :src="p.url" class="h-10 w-10 object-cover rounded border border-gray-200">
                                                </template>
                                            </div>

                                            {{-- QR Modal (teleported outside table to avoid overflow clipping) --}}
                                            <template x-teleport="body">
                                                <div x-show="open" x-transition
                                                     class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
                                                     @keydown.escape.window="open = false"
                                                     @click.self="open = false">
                                                    <div class="bg-white rounded-2xl shadow-xl p-6 w-80 max-w-full text-center">
                                                        <h3 class="font-semibold text-gray-900 mb-1">Scan with your phone</h3>
                                                        <p class="text-xs text-gray-500 mb-3 leading-snug">
                                                            <span class="font-medium text-brand-navy">{{ $check['category'] }}</span> — {{ $check['text'] }}
                                                        </p>
                                                        <div class="flex justify-center mb-3">
                                                            {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(180)->errorCorrection('M')->generate(route('photo-capture.show', $uploadTokens[$i])) !!}
                                                        </div>
                                                        <p class="text-xs text-gray-400 mb-4 break-all">{{ route('photo-capture.show', $uploadTokens[$i]) }}</p>
                                                        <div x-show="photos.length > 0" class="flex flex-wrap gap-2 justify-center mb-3">
                                                            <template x-for="p in photos" :key="p.url">
                                                                <img :src="p.url" class="h-16 w-16 object-cover rounded-lg border border-gray-200">
                                                            </template>
                                                        </div>
                                                        <p x-show="photos.length === 0" class="text-xs text-gray-400 mb-3 flex items-center justify-center gap-1.5">
                                                            <svg class="animate-pulse w-2 h-2 text-brand-red fill-current" viewBox="0 0 8 8"><circle cx="4" cy="4" r="4"/></svg>
                                                            Waiting for photos…
                                                        </p>
                                                        <button type="button" @click="open = false"
                                                            class="px-4 py-2 bg-gray-100 rounded-lg text-sm text-gray-700 font-medium hover:bg-gray-200">
                                                            Done
                                                        </button>
                                                    </div>
                                                </div>
                                            </template>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Overall result + notes + signature --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-5">
                    <div>
                        <x-input-label for="overall_status" :value="__('Overall Result')" />
                        <select id="overall_status" name="overall_status" required
                            class="mt-1 block w-full sm:w-64 border-gray-300 rounded-md shadow-sm focus:border-brand-red focus:ring-brand-red">
                            <option value="pass" {{ old('overall_status', 'pass') === 'pass' ? 'selected' : '' }}>Pass</option>
                            <option value="conditional" {{ old('overall_status') === 'conditional' ? 'selected' : '' }}>Conditional (pass with reservations)</option>
                            <option value="fail" {{ old('overall_status') === 'fail' ? 'selected' : '' }}>Fail — remove from service</option>
                        </select>
                        <x-input-error :messages="$errors->get('overall_status')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="report_notes" :value="__('Report Notes')" />
                        <textarea id="report_notes" name="report_notes" rows="4"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-brand-red focus:ring-brand-red"
                            placeholder="Overall observations, defects found, actions recommended…">{{ old('report_notes') }}</textarea>
                        <x-input-error :messages="$errors->get('report_notes')" class="mt-2" />
                    </div>

                    @if ($kitItem->kitType->inspection_price)
                        <div class="text-sm text-gray-600">
                            Inspection fee: <strong class="text-gray-900">£{{ number_format($kitItem->kitType->inspection_price, 2) }}</strong>
                            <span class="text-gray-400">(set on kit type — adjustable after saving)</span>
                        </div>
                    @endif

                    <div>
                        <x-input-label for="digital_sig_path" :value="__('Inspector Signature (optional image upload)')" />
                        <input type="file" id="digital_sig_path" name="digital_sig_path" accept="image/*"
                            class="mt-1 block text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-sm file:bg-brand-navy/10 file:text-brand-navy hover:file:bg-brand-navy/20">
                        <x-input-error :messages="$errors->get('digital_sig_path')" class="mt-2" />
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <x-primary-button>Save Inspection</x-primary-button>
                    <a href="{{ route('clients.kit-items.show', [$client, $kitItem]) }}">
                        <x-secondary-button type="button">Cancel</x-secondary-button>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function phoneScan(token) {
            return {
                open: false,
                photos: [],
                pollTimer: null,

                init() {
                    this.$watch('open', (val) => {
                        if (val) {
                            this.poll()
                            this.pollTimer = setInterval(() => this.poll(), 2000)
                        } else {
                            clearInterval(this.pollTimer)
                            this.pollTimer = null
                        }
                    })
                },

                async poll() {
                    try {
                        const res = await axios.get('/photo-capture/' + token + '/status')
                        this.photos = res.data.photos
                    } catch {}
                },
            }
        }
    </script>
</x-app-layout>
