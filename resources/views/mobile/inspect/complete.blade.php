<x-mobile-layout title="Complete Inspection">
    <div class="min-h-screen flex flex-col bg-slate-100" x-data="signaturePad()">

        {{-- Header --}}
        <header class="bg-white border-b border-gray-100 px-4 py-3 flex items-center gap-3">
            <a href="{{ route('mobile.inspect.wizard', [$inspection, $inspection->checks->count() - 1]) }}"
               class="text-gray-500 p-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h1 class="font-semibold text-gray-900">Review & Complete</h1>
        </header>

        <div class="flex-1 px-4 py-5 space-y-5 mobile-bottom-safe">

            {{-- Summary counts --}}
            <div class="rounded-3xl border border-slate-200 bg-white shadow-sm p-5">
                <h2 class="font-semibold text-gray-700 text-sm mb-3">Inspection Summary</h2>
                <div class="grid grid-cols-3 gap-3 text-center">
                    <div class="bg-green-50 rounded-lg py-3">
                        <p class="text-2xl font-bold text-green-600">{{ $passCount }}</p>
                        <p class="text-xs text-green-700 font-medium mt-0.5">Pass</p>
                    </div>
                    <div class="bg-red-50 rounded-lg py-3">
                        <p class="text-2xl font-bold text-red-600">{{ $failCount }}</p>
                        <p class="text-xs text-red-700 font-medium mt-0.5">Fail</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg py-3">
                        <p class="text-2xl font-bold text-gray-500">{{ $naCount }}</p>
                        <p class="text-xs text-gray-500 font-medium mt-0.5">N/A</p>
                    </div>
                </div>

                @if($failCount > 0)
                    <div class="mt-3 bg-red-50 border border-red-200 rounded-lg px-3 py-2 text-sm text-red-700 font-medium">
                        {{ $failCount }} failed check{{ $failCount > 1 ? 's' : '' }} - overall result will be <strong>FAIL</strong>
                    </div>
                @endif

                @if($unanswered > 0)
                    <div class="mt-3 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 text-sm text-amber-700">
                        {{ $unanswered }} check{{ $unanswered > 1 ? 's' : '' }} not yet answered.
                        <a href="{{ route('mobile.inspect.wizard', [$inspection, 0]) }}" class="underline font-medium">Go back to complete them.</a>
                    </div>
                @endif
            </div>

            {{-- Errors --}}
            @if($errors->any())
                <div class="bg-red-50 border border-red-200 rounded-2xl p-4 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('mobile.inspect.complete', $inspection) }}">
                @csrf

                {{-- Notes --}}
                <div class="rounded-3xl border border-slate-200 bg-white shadow-sm p-4 space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Report notes <span class="text-gray-400">(optional)</span></label>
                    <textarea
                        name="report_notes"
                        rows="4"
                        placeholder="General observations or overall remarks…"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-brand-red focus:border-brand-red resize-none"
                    >{{ old('report_notes') }}</textarea>
                </div>

                {{-- Signature --}}
                <div class="rounded-3xl border border-slate-200 bg-white shadow-sm p-4 mt-4 space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="block text-sm font-medium text-gray-700">Inspector signature</label>
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
                        style="height: 140px;"
                    ></canvas>
                    <input type="hidden" name="digital_signature" x-ref="sigInput">
                    <p class="text-xs text-gray-400">Sign above with your finger or stylus</p>
                </div>

                {{-- Submit --}}
                <button
                    type="submit"
                    @click="captureSignature()"
                    {{ $unanswered > 0 ? 'disabled' : '' }}
                    class="w-full mt-5 py-4 rounded-2xl font-bold text-base text-white transition
                           {{ $unanswered > 0 ? 'bg-gray-300 cursor-not-allowed' : 'bg-green-600 hover:bg-green-700' }}"
                >
                    Submit Inspection
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
                        canvas.height = 140
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
