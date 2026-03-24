<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ __('New Job') }}
        </h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-3xl mx-auto mobile-shell">
            <div class="mobile-card sm:rounded-lg">
                <div class="mobile-card-body">

                    <h3 class="text-lg font-medium text-gray-900 mb-6">Create Job / Manifest</h3>

                    <form method="POST" action="{{ route('jobs.store') }}" id="job-form">
                        @csrf

                        {{-- Client --}}
                        <div class="mb-5">
                            <x-input-label for="client_id" value="Client" />
                            <select
                                name="client_id"
                                id="client_id"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-brand-red focus:border-brand-red"
                                x-data
                                x-on:change="$dispatch('client-selected', { clientId: $event.target.value })"
                            >
                                <option value="">— Select a client —</option>
                                @foreach ($clients as $client)
                                    <option value="{{ $client->id }}" @selected(old('client_id') == $client->id)>{{ $client->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('client_id')" class="mt-2" />
                        </div>

                        {{-- Notes --}}
                        <div class="mb-5">
                            <x-input-label for="notes" value="Notes (optional)" />
                            <textarea
                                name="notes"
                                id="notes"
                                rows="3"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-brand-red focus:border-brand-red"
                                placeholder="Any notes about this job…"
                            >{{ old('notes') }}</textarea>
                            <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                        </div>

                        {{-- Kit items (loaded via AJAX or submitted directly) --}}
                        <div
                            id="kit-items-section"
                            x-data="kitItemSelector()"
                            x-on:client-selected.window="loadItems($event.detail.clientId)"
                            class="mb-6"
                        >
                            <div class="flex items-center justify-between mb-2">
                                <x-input-label value="Kit Items (optional — can be added later)" />
                                <span x-show="loading" class="text-xs text-gray-400">Loading…</span>
                            </div>

                            <div x-show="!clientId" class="text-sm text-gray-400 italic">Select a client to load their kit items.</div>

                            <div x-show="clientId && items.length === 0 && !loading" class="text-sm text-gray-400 italic">No active kit items for this client.</div>

                            <div x-show="items.length > 0" class="space-y-2 max-h-64 overflow-y-auto border border-gray-200 rounded-lg p-3">
                                <template x-for="(item, i) in items" :key="item.id">
                                    <label class="flex items-start gap-3 p-2 rounded hover:bg-gray-50 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            :name="'kit_item_ids[]'"
                                            :value="item.id"
                                            x-model="selected"
                                            class="mt-0.5 rounded border-gray-300 text-brand-red focus:ring-brand-red"
                                        >
                                        <span class="text-sm text-gray-800" x-text="item.label"></span>
                                    </label>
                                </template>
                            </div>

                            <p class="mt-2 text-xs text-gray-500">You can also add new items after creating the job.</p>
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>Create Job</x-primary-button>
                            <a href="{{ route('jobs.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Cancel</a>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function kitItemSelector() {
            return {
                clientId: null,
                items: [],
                selected: [],
                loading: false,

                async loadItems(clientId) {
                    this.clientId = clientId
                    this.items = []
                    this.selected = []
                    if (!clientId) return

                    this.loading = true
                    try {
                        const resp = await fetch(`/api/clients/${clientId}/kit-items`)
                        this.items = await resp.json()
                    } catch (e) {
                        this.items = []
                    }
                    this.loading = false
                }
            }
        }
    </script>
</x-app-layout>
