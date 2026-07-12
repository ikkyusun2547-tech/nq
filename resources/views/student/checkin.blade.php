@extends('layouts.dashboard')

@section('content')
<div class="mx-auto max-w-md" x-data="checkinApp()" x-init="init()">
    <x-brand-header :title="__('เช็คชื่อเข้าร่วมกิจกรรม')" :back="route('dashboard')" />

    <div class="overflow-hidden rounded-3xl glass-card shadow-soft-lg">
        <!-- Step indicator -->
        <div class="px-5 py-4">
            <x-step-indicator :steps="[__('สแกน QR'), __('ถ่ายเซลฟี'), __('ยืนยัน')]" current="stepIndex" />
        </div>

        <div class="border-t border-brand-purple-100 p-5 dark:border-slate-700/60">
            <!-- Step 1: Scan QR -->
            <template x-if="step === 'scan'">
                <div>
                    <div class="mb-3 flex items-center gap-2 rounded-xl bg-brand-purple-50 px-3.5 py-2.5 text-sm text-brand-purple-700 dark:bg-brand-purple-500/10 dark:text-brand-purple-400">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.5a.75.75 0 01.75-.75h3a.75.75 0 010 1.5H5.25v2.25a.75.75 0 01-1.5 0v-3zm12 0a.75.75 0 01.75-.75h3a.75.75 0 01.75.75v3a.75.75 0 01-1.5 0V5.25h-2.25a.75.75 0 01-.75-.75zM4.5 15.75a.75.75 0 01.75.75v2.25h2.25a.75.75 0 010 1.5h-3a.75.75 0 01-.75-.75v-3a.75.75 0 01.75-.75zm15 0a.75.75 0 01.75.75v3a.75.75 0 01-.75.75h-3a.75.75 0 010-1.5h2.25v-2.25a.75.75 0 01.75-.75z"/></svg>
                        {{ __('เล็งกล้องไปที่ QR Code ที่แสดงหน้างาน') }}
                    </div>
                    <div id="qr-reader" class="overflow-hidden rounded-2xl ring-2 ring-brand-purple-100 dark:ring-brand-purple-500/20"></div>
                    <p class="mt-3 text-xs text-red-500" x-show="scanError" x-text="scanError"></p>
                </div>
            </template>

            <!-- Step 2: Front-camera selfie only, no gallery upload. Capture is
                 handed off to the OS's own camera app via a file input rather
                 than an in-page getUserMedia stream — the latter is unreliable
                 on devices that refuse to hand the camera to a live preview
                 (NotReadableError) even when the hardware itself is free. -->
            <template x-if="step === 'selfie'">
                <div class="flex flex-col items-center py-2 text-center">
                    <div class="mb-5 flex h-18 w-18 items-center justify-center rounded-full bg-brand-purple-50 text-brand-purple-600 dark:bg-brand-purple-500/10 dark:text-brand-purple-400">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z"/></svg>
                    </div>
                    <p class="mb-5 text-sm text-gray-500 dark:text-slate-400">{{ __('ถ่ายภาพเซลฟีเพื่อยืนยันตัวตน (ใช้กล้องหน้าเท่านั้น)') }}</p>
                    <button
                        type="button" @click="$refs.fileInput.click()"
                        class="w-full rounded-xl bg-brand-green-500 px-4 py-3 text-sm font-semibold text-brand-purple-950 shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:bg-brand-green-400 hover:shadow-lg"
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
                <div class="flex flex-col items-center py-10 text-center">
                    <svg class="h-9 w-9 animate-spin text-brand-purple-500" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                        <path class="opacity-90" fill="currentColor" d="M12 2a10 10 0 0110 10h-3a7 7 0 00-7-7V2z"></path>
                    </svg>
                    <p class="mt-4 text-sm text-gray-500 dark:text-slate-400">{{ __('กำลังตรวจสอบตำแหน่งและส่งข้อมูล...') }}</p>
                </div>
            </template>

            <template x-if="step === 'done'">
                <div class="py-6 text-center">
                    <div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full"
                        :class="resultStatus === 'auto_approved' ? 'bg-brand-green-100 text-brand-green-600 dark:bg-brand-green-500/10 dark:text-brand-green-400' : 'bg-amber-100 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400'">
                        <svg x-show="resultStatus === 'auto_approved'" class="h-9 w-9" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <svg x-show="resultStatus !== 'auto_approved'" class="h-9 w-9" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                    </div>
                    <p class="text-base font-semibold text-gray-900 dark:text-slate-100" x-text="resultStatus === 'auto_approved' ? '{{ __('เช็คชื่อสำเร็จ') }}' : '{{ __('ส่งคำขอสำเร็จ') }}'"></p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-slate-400" x-text="resultMessage"></p>
                    <a href="{{ route('dashboard') }}" class="mt-5 block w-full rounded-xl bg-brand-purple-700 px-4 py-3 text-sm font-semibold text-white shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg">{{ __('กลับหน้าแดชบอร์ด') }}</a>
                </div>
            </template>

            <template x-if="step === 'error'">
                <div class="py-6 text-center">
                    <div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-red-100 text-red-600 dark:bg-red-500/10 dark:text-red-400">
                        <svg class="h-9 w-9" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </div>
                    <p class="text-base font-semibold text-red-600 dark:text-red-400">{{ __('เกิดข้อผิดพลาด') }}</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-slate-400" x-text="resultMessage"></p>
                    <button @click="resetToScan()" class="mt-5 block w-full rounded-xl bg-brand-purple-700 px-4 py-3 text-sm font-semibold text-white shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg">{{ __('สแกนใหม่อีกครั้ง') }}</button>
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

            get stepIndex() {
                if (this.step === 'scan') return 0;
                if (this.step === 'selfie') return 1;
                return 2;
            },

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

                // Chrome on iOS has a known bug where getCurrentPosition's own
                // `timeout` option is silently ignored — if the permission
                // prompt or location fix never resolves, neither callback ever
                // fires and the UI hangs on "submitting" forever. This manual
                // backstop guarantees we always leave that state.
                let settled = false;
                const giveUp = setTimeout(() => {
                    if (settled) return;
                    settled = true;
                    this.showError('{{ __('หาตำแหน่ง GPS ไม่สำเร็จ (หมดเวลารอ) กรุณาลองใหม่ หรือตรวจสอบว่าอนุญาตสิทธิ์เข้าถึงตำแหน่งให้เบราว์เซอร์นี้แล้วในตั้งค่าเครื่อง') }}');
                }, 12000);

                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        if (settled) return;
                        settled = true;
                        clearTimeout(giveUp);
                        this.sendPayload(position.coords.latitude, position.coords.longitude);
                    },
                    () => {
                        if (settled) return;
                        settled = true;
                        clearTimeout(giveUp);
                        this.showError('{{ __('กรุณาอนุญาตการเข้าถึงตำแหน่ง GPS เพื่อเช็คชื่อ') }}');
                    },
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
