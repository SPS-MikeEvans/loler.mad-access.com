<x-mobile-layout title="Add Photo">
    <div
        class="min-h-screen flex flex-col px-5 py-8"
        x-data="photoCapture('{{ $uploadUrl }}')"
    >
        {{-- Header --}}
        <div class="mb-6">
            <p class="text-xs font-semibold uppercase tracking-wide text-brand-navy/60 mb-1">Inspection Photo</p>
            <h1 class="text-xl font-bold text-gray-900 leading-snug">{{ $checkText }}</h1>
        </div>

        {{-- Upload button --}}
        <input
            type="file"
            accept="image/*"
            capture="environment"
            multiple
            x-ref="fileInput"
            @change="handleFiles($event)"
            class="hidden"
        >
        <button
            @click="$refs.fileInput.click()"
            :disabled="uploading"
            class="w-full py-5 rounded-2xl bg-brand-navy text-white font-bold text-lg flex items-center justify-center gap-3 disabled:opacity-50 active:scale-95 transition"
        >
            <span x-show="!uploading">📷 Take / Add Photo</span>
            <span x-show="uploading" class="flex items-center gap-2">
                <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                </svg>
                Uploading…
            </span>
        </button>

        {{-- Uploaded photos --}}
        <div x-show="photos.length > 0" class="mt-6">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-3">
                Uploaded (<span x-text="photos.length"></span>)
            </p>
            <div class="grid grid-cols-3 gap-2">
                <template x-for="(photo, index) in photos" :key="index">
                    <div class="relative">
                        <img :src="photo.url" class="w-full aspect-square object-cover rounded-xl border border-gray-200">
                        <div class="absolute top-1 right-1 bg-green-500 rounded-full w-5 h-5 flex items-center justify-center">
                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Error --}}
        <div x-show="errorMsg" class="mt-4 px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700"
             x-text="errorMsg"></div>

        {{-- Done hint --}}
        <p x-show="photos.length > 0" class="mt-6 text-center text-sm text-gray-400">
            All done? You can close this tab.
        </p>
    </div>

    <script>
        function photoCapture(uploadUrl) {
            return {
                uploading: false,
                photos: [],
                errorMsg: '',

                async handleFiles(event) {
                    const files = Array.from(event.target.files)
                    if (!files.length) return
                    this.uploading = true
                    this.errorMsg = ''
                    try {
                        for (const file of files) {
                            const compressed = await this.compress(file)
                            const form = new FormData()
                            form.append('photo', compressed, 'photo.jpg')
                            const res = await axios.post(uploadUrl, form)
                            this.photos.push(res.data)
                        }
                    } catch (e) {
                        this.errorMsg = 'Upload failed: ' + (e.response?.data?.message ?? e.message ?? 'unknown error')
                    } finally {
                        this.uploading = false
                        event.target.value = ''
                    }
                },

                compress(file) {
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
            }
        }
    </script>
</x-mobile-layout>
