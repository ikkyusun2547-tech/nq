@extends('layouts.dashboard')

@section('content')
<div class="mx-auto max-w-md" x-data="checkinApp()" x-init="init()">
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-lg font-semibold text-gray-900">{{ __('เช็กชื่อเข้าร่วมกิจกรรม') }}</h1>
        <a href="{{ route('dashboard') }}" class="text-sm text-gray-400 hover:text-gray-600">&larr; {{ __('กลับ') }}</a>
    </div>

    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
        <!-- Step indicator -->
        <div class="flex border-b border-gray-100 text-xs">
            <div class="flex-1 py-2 text-center" :class="step === 'scan' ? 'font-semibold text-brand-green-700' : 'text-gray-400'">1. {{ __('สแกน QR') }}</div>
            <div class="flex-1 py-2 text-center" :class="step === 'selfie' ? 'font-semibold text-brand-green-700' : 'text-gray-400'">2. {{ __('ถ่ายเซลฟี') }}</div>
            <div class="flex-1 py-2 text-center" :class="['submitting','done','error'].includes(step) ? 'font-semibold text-brand-green-700' : 'text-gray-400'">3. {{ __('ยืนยัน') }}</div>
        </div>

        <div class="p-5">
            <!-- Step 1: Scan QR -->
            <template x-if="step === 'scan'">
                <div>
                    <p class="mb-3 text-sm text-gray-500">{{ __('เล็งกล้องไปที่ QR Code ที่แสดงหน้างาน') }}</p>
                    <div id="qr-reader" class="overflow-hidden rounded-xl"></div>
                    <p class="mt-3 text-xs text-red-500" x-show="scanError" x-text="scanError"></p>
                </div>
            </template>

            <!-- Step 2: Front-camera selfie only, no gallery upload -->
            <template x-if="step === 'selfie'">
                <div>
                    <p class="mb-3 text-sm text-gray-500">{{ __('ถ่ายภาพเซลฟีเพื่อยืนยันตัวตน (ใช้กล้องหน้าเท่านั้น)') }}</p>
                    <div class="relative overflow-hidden rounded-xl bg-black">
                        <video x-ref="video" autoplay playsinline muted class="w-full -scale-x-100"></video>
                    </div>
                    <button
                        type="button" @click="capturePhoto()"
                        class="mt-3 w-full rounded-xl bg-brand-green-600 px-4 py-3 text-sm font-semibold text-white hover:bg-brand-green-700"
                    >
                        {{ __('ถ่ายภาพ') }}
                    </button>
                    <p class="mt-2 text-xs text-red-500" x-show="cameraError" x-text="cameraError"></p>
                </div>
            </template>

            <!-- Step 3: submitting / done / error -->
            <template x-if="step === 'submitting'">
                <div class="py-10 text-center text-sm text-gray-500">{{ __('กำลังตรวจสอบตำแหน่งและส่งข้อมูล...') }}</div>
            </template>

            <template x-if="step === 'done'">
                <div class="py-6 text-center">
                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full"
                        :class="resultStatus === 'auto_approved' ? 'bg-brand-green-100 text-brand-green-600' : 'bg-amber-100 text-amber-600'">
                        <span x-text="resultStatus === 'auto_approved' ? '✓' : '!'" class="text-2xl"></span>
                    </div>
                    <p class="text-sm font-medium text-gray-900" x-text="resultMessage"></p>
                    <a href="{{ route('dashboard') }}" class="mt-4 inline-block text-sm text-brand-purple-600 hover:underline">{{ __('กลับหน้าแดชบอร์ด') }}</a>
                </div>
            </template>

            <template x-if="step === 'error'">
                <div class="py-6 text-center">
                    <p class="text-sm font-medium text-red-600" x-text="resultMessage"></p>
                    <button @click="resetToScan()" class="mt-4 text-sm text-brand-purple-600 hover:underline">{{ __('สแกนใหม่อีกครั้ง') }}</button>
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
            stream: null,

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
                } catch (e) { /* already stopped */ }

                this.step = 'selfie';
                await this.startFrontCamera();
            },

            async startFrontCamera() {
                try {
                    this.stream = await navigator.mediaDevices.getUserMedia({
                        video: { facingMode: 'user' },
                        audio: false,
                    });
                    this.$refs.video.srcObject = this.stream;
                } catch (err) {
                    this.cameraError = '{{ __('ไม่สามารถเปิดกล้องหน้าได้: กรุณาอนุญาตการใช้กล้อง') }}';
                }
            },

            capturePhoto() {
                const video = this.$refs.video;
                const canvas = document.createElement('canvas');

                // Resize to a max width of 480px before upload to cut server load.
                const maxWidth = 480;
                const scale = Math.min(1, maxWidth / video.videoWidth);
                canvas.width = video.videoWidth * scale;
                canvas.height = video.videoHeight * scale;

                const ctx = canvas.getContext('2d');
                // Mirror horizontally so the saved photo matches what the student saw.
                ctx.translate(canvas.width, 0);
                ctx.scale(-1, 1);
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                canvas.toBlob((blob) => {
                    this.photoBlob = blob;
                    this.stopCamera();
                    this.submitCheckIn();
                }, 'image/jpeg', 0.7);
            },

            stopCamera() {
                this.stream?.getTracks().forEach((track) => track.stop());
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
