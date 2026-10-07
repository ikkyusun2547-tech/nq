<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    {{-- Rendered by dompdf: no flex/grid, so layout is tables + inline-block, and colours are solid. --}}
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
        @page { margin: 0; }
        body { margin: 0; font-family: 'Sarabun', sans-serif; color: #0f172a; background: #ffffff; }
        table { border-collapse: collapse; }

        .header { background: #6d28d9; padding: 26px 44px; }
        .header td { vertical-align: middle; }
        .logo { width: 40px; }
        .brand { color: #ffffff; font-size: 17px; font-weight: bold; line-height: 1.1; }
        .brand-sub { color: #ddd6fe; font-size: 11px; line-height: 1.2; }
        .badge { display: inline-block; background: #ffffff; color: #6d28d9; font-size: 11px; font-weight: bold; padding: 5px 14px; border-radius: 20px; }

        .content { padding: 26px 56px 0; text-align: center; }
        .eyebrow { display: inline-block; font-size: 13px; font-weight: bold; color: #6d28d9; background: #f5f3ff; padding: 3px 14px; border-radius: 20px; margin: 0; }
        h1 { font-size: 30px; line-height: 1.2; margin: 4px 0 0; }
        .location { font-size: 14px; color: #64748b; margin: 4px 0 0; }

        .qr-frame { display: inline-block; margin-top: 18px; padding: 10px; background: #6d28d9; border-radius: 36px; }
        .qr-card { background: #ffffff; border-radius: 28px; padding: 18px 18px 10px; }
        .qr-code { margin-top: 6px; font-size: 12px; color: #6d28d9; letter-spacing: 4px; font-weight: bold; }

        .info { width: 100%; margin-top: 22px; }
        .info td { width: 33.33%; padding: 0 5px; }
        .info-box { background: #f5f3ff; border-radius: 14px; padding: 9px 12px; text-align: left; }
        .info-label { font-size: 11px; color: #7c3aed; font-weight: bold; }
        .info-value { font-size: 14px; font-weight: bold; color: #1e1b4b; }

        .steps { width: 100%; margin-top: 18px; }
        .steps td { width: 33.33%; padding: 0 8px; text-align: left; vertical-align: top; }
        .step-num { width: 26px; height: 26px; line-height: 17px; border-radius: 13px; background: #6d28d9; color: #ffffff; font-size: 12px; font-weight: bold; text-align: center; }
        .step-title { font-size: 13px; font-weight: bold; margin-top: 5px; }
        .step-text { font-size: 11px; color: #64748b; line-height: 1.35; }

        .warning { margin-top: 18px; padding: 10px 16px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 14px; font-size: 11px; line-height: 1.45; color: #92400e; text-align: left; }

        .footer { position: absolute; bottom: 0; left: 0; right: 0; padding: 14px 44px; border-top: 1px solid #ede9fe; font-size: 10px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="header">
        <table width="100%">
            <tr>
                <td style="width: 52px;"><img class="logo" src="{{ $logoDataUri }}"></td>
                <td>
                    <div class="brand">SRRU Check</div>
                    <div class="brand-sub">มหาวิทยาลัยราชภัฏสุรินทร์</div>
                </td>
                <td style="text-align: right;"><span class="badge">QR สำรองสำหรับพิมพ์</span></td>
            </tr>
        </table>
    </div>

    <div class="content">
        <p class="eyebrow">สแกนเพื่อเช็คชื่อ</p>
        {{-- Long titles shrink so the page never spills onto a second sheet. --}}
        <h1 @if (mb_strlen($activity->title) > 40) style="font-size: 24px;" @endif>{{ $activity->title }}</h1>
        @if ($activity->location_name)
            <p class="location">{{ $activity->location_name }}</p>
        @endif

        <div class="qr-frame">
            <div class="qr-card">
                <img src="{{ $qrDataUri }}" width="420" height="420">
                @if ($activity->activity_code)
                    <div class="qr-code">{{ $activity->activity_code }}</div>
                @endif
            </div>
        </div>

        @if ($activity->start_at)
            @php
                $start = $activity->start_at->copy()->locale('th');
                $end = $activity->end_at?->copy()->locale('th');
            @endphp
            <table class="info">
                <tr>
                    <td><div class="info-box"><div class="info-label">วันที่</div><div class="info-value">{{ $start->translatedFormat('j M') }} {{ $start->year + 543 }}</div></div></td>
                    <td><div class="info-box"><div class="info-label">เวลา</div><div class="info-value">{{ $start->format('H:i') }}@if ($end) – {{ $end->format('H:i') }}@endif น.</div></div></td>
                    <td><div class="info-box"><div class="info-label">ชั่วโมงกิจกรรม</div><div class="info-value">{{ $activity->credit_hours ? rtrim(rtrim(number_format((float) $activity->credit_hours, 1), '0'), '.').' ชม.' : '—' }}</div></div></td>
                </tr>
            </table>
        @endif

        <table class="steps">
            <tr>
                <td>
                    <div class="step-num">1</div>
                    <div class="step-title">เปิด SRRU Check</div>
                    <div class="step-text">ผ่านแอปหรือเว็บ แล้วเข้าสู่ระบบ</div>
                </td>
                <td>
                    <div class="step-num">2</div>
                    <div class="step-title">สแกน QR นี้</div>
                    <div class="step-text">กดปุ่มสแกนและหันกล้องมาที่ QR</div>
                </td>
                <td>
                    <div class="step-num">3</div>
                    <div class="step-title">ถ่ายเซลฟียืนยัน</div>
                    <div class="step-text">{{ $activity->requires_gps ? 'เปิดตำแหน่ง GPS และอยู่ในพื้นที่กิจกรรม' : 'ถ่ายรูปตัวเองเพื่อยืนยันการเข้าร่วม' }}</div>
                </td>
            </tr>
        </table>

        <div class="warning">
            <strong>ใช้เฉพาะกรณีจำเป็นเท่านั้น</strong> — QR นี้ไม่หมุนรหัสเหมือนหน้าจอเช็คชื่อสด การเช็คชื่อทุกครั้งที่ใช้ QR นี้จะ<strong>รอเจ้าหน้าที่ตรวจสอบ</strong>เสมอ (ไม่อนุมัติอัตโนมัติ) แม้ตำแหน่ง GPS และเซลฟีจะถูกต้องก็ตาม
        </div>
    </div>

    <div class="footer">
        <table width="100%">
            <tr>
                <td>ออกเมื่อ {{ $generatedAt->format('d/m/') }}{{ $generatedAt->year + 543 }} {{ $generatedAt->format('H:i') }} น.</td>
                <td style="text-align: right;">{{ preg_replace('#^https?://#', '', config('app.url')) }}</td>
            </tr>
        </table>
    </div>
</body>
</html>
