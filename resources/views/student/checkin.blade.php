@extends('layouts.dashboard')

@section('content')
<div class="mx-auto max-w-md" x-data="checkinApp()" x-init="init()">
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-lg font-semibold text-gray-900 dark:text-slate-100">{{ __('เช็กชื่อเข้าร่วมกิจกรรม') }}</h1>
        <a href="{{ route('dashboard') }}" class="text-sm text-gray-400 hover:text-gray-600 dark:text-slate-500 dark:hover:text-slate-300">&larr; {{ __('กลับ') }}</a>
    </div>

    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200 dark:bg-slate-900 dark:ring-slate-700">
        <!-- Step indicator -->
        <div class="flex border-b border-gray-100 text-xs dark:border-slate-800">
            <div class="flex-1 py-2 text-center" :class="step === 'scan' ? 'font-semibold text-brand-green-700' : 'text-gray-400 dark:text-slate-500'">1. {{ __('สแกน QR') }}</div>
            <div class="flex-1 py-2 text-center" :class="step === 'selfie' ? 'font-semibold text-brand-green-700' : 'text-gray-400 dark:text-slate-500'">2. {{ __('ถ่ายเซลฟี') }}</div>
            <div class="flex-1 py-2 text-center" :class="['submitting','done','error'].includes(step) ? 'font-semibold text-brand-green-700' : 'text-gray-400 dark:text-slate-500'">3. {{ __('ยืนยัน') }}</div>
        </div>

        <div class="p-5">
            <!-- Step 1: Scan QR -->
            <template x-if="step === 'scan'">
                <div>
                    <p class="mb-3 text-sm text-gray-500 dark:text-slate-400">{{ __('เล็งกล้องไปที่ QR Code ที่แสดงหน้างาน') }}</p>
                    <div id="qr-reader" class="overflow-hidden rounded-xl"></div>
                    <p class="mt-3 text-xs text-red-500" x-show="scanError" x-text="scanError"></p>
                </div>
            </template>

            <!-- Step 2: Front-camera selfie only, no gallery upload. Capture is
                 handed off to the OS's own camera app via a file input rather
                 than an in-page getUserMedia stream — the latter is unreliable
                 on devices that refuse to hand the camera to a live preview
                 (NotReadableError) even when the hardware itself is free. -->
            <template x-if="step === 'selfie'">
                <div>
                    <p class="mb-3 text-sm text-gray-500 dark:text-slate-400">{{ __('ถ่ายภาพเซลฟีเพื่อยืนยันตัวตน (ใช้กล้องหน้าเท่านั้น)') }}</p>
                    <button
                        type="button" @click="$refs.fileInput.click()"
                        class="w-full rounded-xl bg-brand-green-600 px-4 py-3 text-sm font-semibold text-white hover:bg-brand-green-700"
                    >
                        {{ __('เปิดกล้องเพื่อถ่ายเซลฟี') }}
                    </button>
                    <input
                        type="file" x-ref="fileInput" accept="image/*" capture="user" class="hidden"
                        @change="handleFileCapture($event)"
                    >
                    <p class="mt-2 text-xs text-red-500" x-show="cameraError" x-text="cameraError"></p>
                </div>
            </template>

            <!-- Step 3: submitting / done / error -->
            <template x-if="step === 'submitting'">
                <div class="py-10 text-center text-sm text-gray-500 dark:text-slate-400">{{ __('กำลังตรวจสอบตำแหน่งและส่งข้อมูล...') }}</div>
            </template>

            <template x-if="step === 'done'">
                <div class="py-6 text-center">
                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full"
                        :class="resultStatus === 'auto_approved' ? 'bg-brand-green-100 text-brand-green-600 dark:bg-brand-green-500/10 dark:text-brand-green-400' : 'bg-amber-100 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400'">
                        <span x-text="resultStatus === 'auto_approved' ? '✓' : '!'" class="text-2xl"></span>
                    </div>
                    <p class="text-sm font-medium text-gray-900 dark:text-slate-100" x-text="resultMessage"></p>
                    <a href="{{ route('dashboard') }}" class="mt-4 inline-block text-sm text-brand-purple-600 hover:underline dark:text-brand-purple-400 dark:hover:text-brand-purple-300">{{ __('กลับหน้าแดชบอร์ด') }}</a>
                </div>
            </template>

            <template x-if="step === 'error'">
                <div class="py-6 text-center">
                    <p class="text-sm font-medium text-red-600" x-text="resultMessage"></p>
                    <button @click="resetToScan()" class="mt-4 text-sm text-brand-purple-600 hover:underline dark:text-brand-purple-400 dark:hover:text-brand-purple-300">{{ __('สแกนใหม่อีกครั้ง') }}</button>
                </div>
            </template>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
    function checkinApp() {
        return {
            step: 'scan',
            scanError: '',
            cameraError: '',
            resultStatus: '',
            resultMessage: '',
            qrToken: null,
            photoBlob: null,
            scanner: null,

            init() {
                this.$nextTick(() => this.startScanner());
            },

            deviceUuid() {
                let id = localStorage.getItem('srru_device_uuid');
                if (! id) {
                    id = crypto.randomUUID();
                    localStorage.setItem('srru_device_uuid', id);
                }
                return id;
            },

            startScanner() {
                this.scanner = new Html5Qrcode('qr-reader');
                this.scanner.start(
                    { facingMode: 'environment' },
                    { fps: 10, qrbox: 250 },
                    (decodedText) => this.onScanned(decodedText),
                    () => { /* per-frame scan failures are expected while aiming; ignore */ },
                ).catch((err) => {
                    this.scanError = '{{ __('ไม่สามารถเปิดกล้องสแกนได้: ') }}' + err;
                });
            },

            async onScanned(decodedText) {
                if (this.qrToken) return; // avoid double-trigger
                this.qrToken = decodedText;

                try {
                    await this.scanner.stop();
                    await this.scanner.clear();
                } catch (e) { /* already stopped */ }

                this.step = 'selfie';
            },

            async handleFileCapture(event) {
                const file = event.target.files[0];
                if (! file) return;

                // Native camera capture can produce very high-resolution
                // photos (12MP+ phones easily exceed the server's upload
                // limit) — downscale and re-compress client-side so any
                // phone's selfie comfortably fits, regardless of the
                // camera's native resolution.
                this.photoBlob = await this.compressImage(file);
                this.submitCheckIn();
            },

            compressImage(file, maxDimension = 1280, quality = 0.8) {
                return new Promise((resolve) => {
                    const img = new Image();
                    const url = URL.createObjectURL(file);

                    img.onload = () => {
                        URL.revokeObjectURL(url);
                        let { width, height } = img;

                        if (width > height && width > maxDimension) {
                            height = Math.round(height * (maxDimension / width));
                            width = maxDimension;
                        } else if (height > maxDimension) {
                            width = Math.round(width * (maxDimension / height));
                            height = maxDimension;
                        }

                        const canvas = document.createElement('canvas');
                        canvas.width = width;
                        canvas.height = height;
                        canvas.getContext('2d').drawImage(img, 0, 0, width, height);
                        canvas.toBlob((blob) => resolve(blob ?? file), 'image/jpeg', quality);
                    };
                    img.onerror = () => { URL.revokeObjectURL(url); resolve(file); };
                    img.src = url;
                });
            },

            submitCheckIn() {
                this.step = 'submitting';

                if (! navigator.geolocation) {
                    this.showError('{{ __('อุปกรณ์นี้ไม่รองรับการระบุตำแหน่ง GPS') }}');
                    return;
                }

                navigator.geolocation.getCurrentPosition(
                    (position) => this.sendPayload(position.coords.latitude, position.coords.longitude),
                    () => this.showError('{{ __('กรุณาอนุญาตการเข้าถึงตำแหน่ง GPS เพื่อเช็กชื่อ') }}'),
                    { enableHighAccuracy: true, timeout: 10000 },
                );
            },

            async sendPayload(lat, lng) {
                const formData = new FormData();
                formData.append('qr_token', this.qrToken);
                formData.append('location_lat', lat);
                formData.append('location_lng', lng);
                formData.append('device_uuid', this.deviceUuid());
                formData.append('photo', this.photoBlob, 'selfie.jpg');

                try {
                    const res = await fetch('{{ route('checkin.store') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: formData,
                    });
                    const data = await res.json();

                    if (! res.ok) {
                        this.showError(data.message ?? '{{ __('เกิดข้อผิดพลาด กรุณาลองใหม่') }}');
                        return;
                    }

                    this.resultStatus = data.status;
                    this.resultMessage = data.message;
                    this.step = 'done';
                } catch (e) {
                    this.showError('{{ __('การเชื่อมต่อล้มเหลว กรุณาลองใหม่อีกครั้ง') }}');
                }
            },

            showError(message) {
                this.resultMessage = message;
                this.step = 'error';
            },

            resetToScan() {
                this.qrToken = null;
                this.photoBlob = null;
                this.scanError = '';
                this.cameraError = '';
                this.step = 'scan';
                this.$nextTick(() => this.startScanner());
            },
        };
    }
</script>
@endpush
