<x-mobile-layout title="Inspection – Step {{ $checkIndex + 1 }} of {{ $total }}">
    <div
        class="min-h-screen flex flex-col"
        x-data="inspectionWizard({{ $currentCheck->id }}, {{ json_encode($currentCheck->status) }}, {{ json_encode($currentCheck->notes) }})"
    >
        {{-- Progress bar --}}
        <div class="bg-white border-b border-gray-100 px-4 pt-3 pb-2">
            <div class="flex items-center justify-between text-xs text-gray-500 mb-1.5">
                <span>{{ $inspection->kitItem->kitType->name }}</span>
                <span>{{ $checkIndex + 1 }} / {{ $total }}</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-brand-navy h-2 rounded-full transition-all"
                     style="width: {{ round((($checkIndex + 1) / $total) * 100) }}%"></div>
            </div>
        </div>

        {{-- Main content --}}
        <div class="flex-1 overflow-y-auto px-4 py-5 space-y-5">

            {{-- Category badge --}}
            <span class="inline-block bg-brand-blue/20 text-brand-navy text-xs font-semibold px-3 py-1 rounded-full uppercase tracking-wide">
                {{ $currentCheck->check_category }}
            </span>

            {{-- Check text --}}
            <h2 class="text-xl font-bold text-gray-900 leading-snug">
                {{ $currentCheck->check_text }}
            </h2>

            {{-- Pass / Fail / N/A buttons --}}
            <div class="grid grid-cols-3 gap-3">
                <button
                    @click="setStatus('pass')"
                    :class="status === 'pass'
                        ? 'bg-green-500 text-white ring-2 ring-green-600'
                        : 'bg-white border-2 border-gray-200 text-gray-700'"
                    class="py-4 rounded-xl font-bold text-base transition focus:outline-none"
                >
                    ✓ Pass
                </button>
                <button
                    @click="setStatus('fail')"
                    :class="status === 'fail'
                        ? 'bg-red-500 text-white ring-2 ring-red-600'
                        : 'bg-white border-2 border-gray-200 text-gray-700'"
                    class="py-4 rounded-xl font-bold text-base transition focus:outline-none"
                >
                    ✗ Fail
                </button>
                <button
                    @click="setStatus('n/a')"
                    :class="status === 'n/a'
                        ? 'bg-gray-500 text-white ring-2 ring-gray-600'
                        : 'bg-white border-2 border-gray-200 text-gray-700'"
                    class="py-4 rounded-xl font-bold text-base transition focus:outline-none"
                >
                    N/A
                </button>
            </div>

            {{-- Save indicator --}}
            <div class="flex items-center gap-1.5 text-xs text-gray-400 h-4">
                <span x-show="saving" class="flex items-center gap-1">
                    <svg class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                    </svg>
                    Saving…
                </span>
                <span x-show="saved && !saving" class="text-green-500">Saved</span>
                <span x-show="saveError" class="text-red-500">Save failed — check connection</span>
            </div>

            {{-- Notes --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Notes / defects</label>
                <textarea
                    x-model="notes"
                    @input.debounce.600ms="saveCheck()"
                    rows="3"
                    placeholder="Optional — describe any issues found…"
                    class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-brand-red focus:border-brand-red resize-none"
                ></textarea>
            </div>

            {{-- Photos --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Photos</label>

                {{-- Existing photos --}}
                <div class="grid grid-cols-3 gap-2 mb-3" x-show="photos.length > 0">
                    <template x-for="photo in photos" :key="photo.id">
                        <div class="relative">
                            <img :src="photo.url" class="w-full aspect-square object-cover rounded-lg">
                            <button
                                @click="deletePhoto(photo.id)"
                                class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs font-bold"
                            >✕</button>
                        </div>
                    </template>
                </div>

                {{-- Upload progress --}}
                <div x-show="uploading" class="text-xs text-brand-navy mb-2 flex items-center gap-1">
                    <svg class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                    </svg>
                    Uploading photo…
                </div>

                <input
                    type="file"
                    accept="image/*"
                    capture="environment"
                    x-ref="photoInput"
                    @change="uploadPhoto($event)"
                    class="hidden"
                    multiple
                >
                <button
                    @click="$refs.photoInput.click()"
                    :disabled="uploading"
                    class="w-full border-2 border-dashed border-gray-300 rounded-xl py-3 text-sm text-gray-500 font-medium hover:border-brand-red hover:text-brand-navy transition disabled:opacity-50"
                >
                    📷 Take / Add Photo
                </button>
            </div>

            {{-- Spacer for sticky nav --}}
            <div class="h-20"></div>

        </div>

        {{-- Navigation --}}
        <div class="sticky bottom-0 bg-white border-t border-gray-100 px-4 py-3 flex gap-3">
            @if($checkIndex > 0)
                <a href="{{ route('mobile.inspect.wizard', [$inspection, $checkIndex - 1]) }}"
                   class="flex-1 text-center py-3.5 rounded-xl bg-gray-100 text-gray-700 font-semibold text-sm">
                    ← Previous
                </a>
            @else
                <div class="flex-1"></div>
            @endif

            @if($checkIndex < $total - 1)
                <a href="{{ route('mobile.inspect.wizard', [$inspection, $checkIndex + 1]) }}"
                   class="flex-1 text-center py-3.5 rounded-xl bg-brand-navy text-white font-semibold text-sm">
                    Next →
                </a>
            @else
                <a href="{{ route('mobile.inspect.complete-screen', $inspection) }}"
                   class="flex-1 text-center py-3.5 rounded-xl bg-green-600 text-white font-semibold text-sm">
                    Review & Finish
                </a>
            @endif
        </div>

    </div>

    <script>
        function inspectionWizard(checkId, initialStatus, initialNotes) {
            return {
                checkId,
                status: initialStatus,
                notes: initialNotes ?? '',
                photos: @json($currentCheck->photos->map(fn($p) => ['id' => $p->id, 'url' => Storage::disk('public')->url($p->path)])),
                saving: false,
                saved: false,
                saveError: false,
                uploading: false,

                setStatus(value) {
                    this.status = value
                    this.saveCheck()
                },

                async saveCheck() {
                    this.saving = true
                    this.saved = false
                    this.saveError = false
                    try {
                        await axios.post('{{ route('mobile.inspect.save-check', $inspection) }}', {
                            check_id: this.checkId,
                            status: this.status,
                            notes: this.notes,
                        })
                        this.saved = true
                    } catch {
                        this.saveError = true
                    } finally {
                        this.saving = false
                    }
                },

                async compressImage(file) {
                    return new Promise((resolve) => {
                        const img = new Image()
                        img.onload = () => {
                            const maxWidth = 1200
                            let { width, height } = img
                            if (width > maxWidth) {
                                height = Math.round(height * maxWidth / width)
                                width = maxWidth
                            }
                            const canvas = document.createElement('canvas')
                            canvas.width = width
                            canvas.height = height
                            canvas.getContext('2d').drawImage(img, 0, 0, width, height)
                            canvas.toBlob((blob) => resolve(blob), 'image/jpeg', 0.8)
                        }
                        img.src = URL.createObjectURL(file)
                    })
                },

                async uploadPhoto(event) {
                    const files = event.target.files
                    if (!files.length) return
                    this.uploading = true
                    try {
                        for (const file of files) {
                            const compressed = await this.compressImage(file)
                            const form = new FormData()
                            form.append('photo', compressed, 'photo.jpg')
                            const res = await axios.post(
                                '{{ route('mobile.inspect.upload-photo', [$inspection, '__CHECK__']) }}'.replace('__CHECK__', this.checkId),
                                form
                            )
                            this.photos.push(res.data)
                        }
                    } catch (e) {
                        alert('Photo upload failed: ' + (e.response?.data?.message ?? e.message ?? 'unknown error'))
                    } finally {
                        this.uploading = false
                        event.target.value = ''
                    }
                },

                async deletePhoto(photoId) {
                    if (!confirm('Remove this photo?')) return
                    try {
                        await axios.delete(
                            '{{ route('mobile.inspect.delete-photo', [$inspection, '__PHOTO__']) }}'.replace('__PHOTO__', photoId)
                        )
                        this.photos = this.photos.filter(p => p.id !== photoId)
                    } catch {
                        alert('Could not delete photo.')
                    }
                },
            }
        }
    </script>
</x-mobile-layout>
