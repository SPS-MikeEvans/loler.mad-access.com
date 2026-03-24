<x-mobile-layout title="Sign Drop-Off — {{ $job->job_number }}">
    <div class="min-h-screen flex flex-col bg-slate-100" x-data="signaturePad()">

        <header class="bg-white border-b border-gray-100 px-4 py-3 flex items-center gap-3">
            <a href="{{ route('jobs.show', $job) }}" class="text-gray-500 p-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h1 class="font-semibold text-gray-900">Sign Drop-Off</h1>
        </header>

        <div class="flex-1 px-4 py-5 space-y-5 mobile-bottom-safe">

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 rounded-2xl p-4 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- Job summary --}}
            <div class="rounded-3xl border border-slate-200 bg-white shadow-sm p-5">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Job</p>
                <p class="text-lg font-bold text-gray-900">{{ $job->job_number }}</p>
                <p class="text-sm text-gray-600 mt-0.5">{{ $job->client->name }}</p>

                <div class="mt-4 space-y-1">
                    <p class="text-sm font-medium text-gray-700">Items being left for inspection:</p>
                    @foreach ($job->kitItems as $item)
                        <p class="text-sm text-gray-600 pl-2">• {{ $item->typeName() }}{{ $item->asset_tag ? ' ('.$item->asset_tag.')' : '' }}</p>
                    @endforeach
                    @if ($job->kitItems->isEmpty())
                        <p class="text-sm text-gray-400 italic">No items on this job yet.</p>
                    @endif
                </div>
            </div>

            <form method="POST" action="{{ route('jobs.capture-drop-off', $job) }}" id="sig-form">
                @csrf

                {{-- Signer name --}}
                <div class="rounded-3xl border border-slate-200 bg-white shadow-sm p-4 space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Full name of person signing</label>
                    <input
                        type="text"
                        name="signed_by"
                        value="{{ old('signed_by') }}"
                        placeholder="e.g. John Smith"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-brand-red focus:border-brand-red"
                        autocomplete="name"
                    >
                    @error('signed_by')
                        <p class="text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Signature pad --}}
                <div class="rounded-3xl border border-slate-200 bg-white shadow-sm p-4 mt-4 space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="block text-sm font-medium text-gray-700">Signature</label>
                        <button type="button" @click="clear()" class="text-xs text-red-500">Clear</button>
                    </div>
                    <canvas
                        x-ref="canvas"
                        @mousedown="startDrawing($event)"
                        @mousemove="draw($event)"
                        @mouseup="stopDrawing()"
                        @touchstart.prevent="startDrawing($event.touches[0])"
                        @touchmove.prevent="draw($event.touches[0])"
                        @touchend="stopDrawing()"
                        class="w-full border-2 border-dashed border-gray-300 rounded-lg bg-gray-50 touch-none"
                        style="height: 160px;"
                    ></canvas>
                    <input type="hidden" name="digital_signature" x-ref="sigInput">
                    @error('digital_signature')
                        <p class="text-xs text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-gray-400">Sign above with your finger or stylus</p>
                </div>

                {{-- GDPR consent --}}
                <div class="rounded-3xl border border-amber-100 bg-amber-50 p-4 mt-4 text-xs text-amber-800">
                    By signing, you confirm receipt of the listed items and consent to this signature being retained for legal and audit purposes for a period of 2 years.
                </div>

                <button
                    type="submit"
                    @click="captureSignature()"
                    class="w-full mt-5 py-4 rounded-2xl font-bold text-base text-white bg-brand-navy hover:bg-brand-navy/90 transition"
                >
                    Sign Drop-Off
                </button>

            </form>

            <div class="h-6"></div>
        </div>
    </div>

    <script>
        function signaturePad() {
            return {
                drawing: false,
                hasSignature: false,
                ctx: null,
                lastX: 0,
                lastY: 0,

                init() {
                    this.$nextTick(() => {
                        const canvas = this.$refs.canvas
                        canvas.width = canvas.offsetWidth
                        canvas.height = 160
                        this.ctx = canvas.getContext('2d')
                        this.ctx.strokeStyle = '#1e293b'
                        this.ctx.lineWidth = 2.5
                        this.ctx.lineCap = 'round'
                        this.ctx.lineJoin = 'round'
                    })
                },

                getPos(event, canvas) {
                    const rect = canvas.getBoundingClientRect()
                    return {
                        x: (event.clientX - rect.left) * (canvas.width / rect.width),
                        y: (event.clientY - rect.top) * (canvas.height / rect.height),
                    }
                },

                startDrawing(event) {
                    this.drawing = true
                    const pos = this.getPos(event, this.$refs.canvas)
                    this.lastX = pos.x
                    this.lastY = pos.y
                },

                draw(event) {
                    if (!this.drawing) return
                    const pos = this.getPos(event, this.$refs.canvas)
                    this.ctx.beginPath()
                    this.ctx.moveTo(this.lastX, this.lastY)
                    this.ctx.lineTo(pos.x, pos.y)
                    this.ctx.stroke()
                    this.lastX = pos.x
                    this.lastY = pos.y
                    this.hasSignature = true
                },

                stopDrawing() {
                    this.drawing = false
                },

                clear() {
                    this.ctx.clearRect(0, 0, this.$refs.canvas.width, this.$refs.canvas.height)
                    this.hasSignature = false
                    this.$refs.sigInput.value = ''
                },

                captureSignature() {
                    if (this.hasSignature) {
                        this.$refs.sigInput.value = this.$refs.canvas.toDataURL('image/png')
                    }
                },
            }
        }
    </script>
</x-mobile-layout>
