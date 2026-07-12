<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <style>
        @font-face {
            font-family: 'Sarabun';
            font-weight: normal;
            src: url('{{ resource_path('fonts/Sarabun-Regular.ttf') }}') format('truetype');
        }
        @font-face {
            font-family: 'Sarabun';
            font-weight: bold;
            src: url('{{ resource_path('fonts/Sarabun-Bold.ttf') }}') format('truetype');
        }
        body { font-family: 'Sarabun', sans-serif; color: #111; text-align: center; }
        .eyebrow { font-size: 12px; color: #666; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 4px; }
        h1 { font-size: 22px; margin: 0 0 6px; }
        p.location { font-size: 13px; color: #444; margin: 0 0 24px; }
        .qr-box { display: inline-block; padding: 16px; border: 2px solid #333; border-radius: 12px; }
        .warning { margin: 22px auto 0; max-width: 420px; padding: 12px 16px; border: 1px solid #fcd34d; background: #fffbeb; border-radius: 8px; font-size: 11px; color: #92400e; text-align: left; }
        .footer { margin-top: 26px; font-size: 10px; color: #999; }
    </style>
</head>
<body>
    <p class="eyebrow">มหาวิทยาลัยราชภัฏสุรินทร์ · QR สำรองสำหรับพิมพ์</p>
    <h1>{{ $activity->title }}</h1>
    @if ($activity->location_name)
        <p class="location">{{ $activity->location_name }}</p>
    @endif

    <div class="qr-box">
        <img src="{{ $qrDataUri }}" width="300" height="300">
    </div>

    <div class="warning">
        <strong>ใช้เฉพาะกรณีจำเป็นเท่านั้น</strong> — QR นี้ไม่หมุนรหัสเหมือนหน้าจอเช็คชื่อสด เพื่อให้พิมพ์ติดหน้างานได้ ดังนั้นการเช็คชื่อทุกครั้งที่ใช้ QR นี้จะถูก<strong>ติดธงรอเจ้าหน้าที่ตรวจสอบ</strong>เสมอ (ไม่อนุมัติอัตโนมัติ) แม้ตำแหน่ง GPS และเซลฟีจะถูกต้องก็ตาม
    </div>

    <p class="footer">ออกเมื่อ {{ $generatedAt->format('d/m/Y H:i') }} น. @if ($activity->activity_code) · รหัสกิจกรรม {{ $activity->activity_code }} @endif</p>
</body>
</html>
