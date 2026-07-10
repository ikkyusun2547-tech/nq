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
        body { font-family: 'Sarabun', sans-serif; font-size: 12px; color: #111; }
        h1 { font-size: 17px; margin-bottom: 2px; }
        h2 { font-size: 13px; margin: 18px 0 6px; }
        p.sub { color: #555; margin-top: 0; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 5px 8px; text-align: left; }
        th { background: #f3f4f6; }
        td.num, th.num { text-align: right; }
        table.info td { border: none; padding: 2px 8px 2px 0; }
        table.info td.label { color: #666; width: 130px; }
        .status-box { margin: 10px 0; padding: 10px 12px; border-radius: 6px; }
        .status-pass { background: #ecfdf5; border: 1px solid #a7f3d0; }
        .status-fail { background: #fffbeb; border: 1px solid #fde68a; }
        .disclaimer { margin-top: 20px; font-size: 10px; color: #777; }
    </style>
</head>
<body>
    <h1>ใบสรุปชั่วโมงกิจกรรมนักศึกษา</h1>
    <p class="sub">มหาวิทยาลัยราชภัฏสุรินทร์ — ออกเมื่อ {{ $generatedAt->format('d/m/Y H:i') }} น.</p>

    <table class="info">
        <tr>
            <td class="label">ชื่อ-นามสกุล</td>
            <td>{{ $user->name_thai ?? $user->name }}</td>
            <td class="label">รหัสนักศึกษา</td>
            <td>{{ $user->student_id }}</td>
        </tr>
        <tr>
            <td class="label">คณะ</td>
            <td>{{ $user->faculty?->name_th }}</td>
            <td class="label">สาขา</td>
            <td>{{ $user->major?->name_th }}</td>
        </tr>
        <tr>
            <td class="label">ชั้นปีที่</td>
            <td>{{ $user->year_level }}</td>
            <td class="label">ประเภทหลักสูตร</td>
            <td>{{ $user->program_type === 'special' ? 'กศ.บป.' : 'ภาคปกติ' }}</td>
        </tr>
    </table>

    <div class="status-box {{ $summary['is_cleared'] ? 'status-pass' : 'status-fail' }}">
        @if ($summary['is_cleared'])
            <strong>ผ่านเกณฑ์กิจกรรมแล้ว</strong> — สะสมครบ {{ $summary['total_activities'] }} กิจกรรม / {{ $summary['total_hours'] }} ชั่วโมง (เกณฑ์ {{ $summary['required_activities'] }} กิจกรรม / {{ $summary['required_hours'] }} ชั่วโมง)
        @else
            <strong>ยังไม่ผ่านเกณฑ์</strong> — สะสม {{ $summary['total_activities'] }}/{{ $summary['required_activities'] }} กิจกรรม, {{ $summary['total_hours'] }}/{{ $summary['required_hours'] }} ชั่วโมง
        @endif
    </div>

    <h2>ชั่วโมงสะสมแยกตามหมวดหมู่</h2>
    <table>
        <thead>
            <tr><th>หมวดหมู่</th><th class="num">ชั่วโมง</th></tr>
        </thead>
        <tbody>
            @foreach ($categoryLabels as $key => $label)
                <tr>
                    <td>{{ $label }}</td>
                    <td class="num">{{ $summary['category_hours'][$key] ?? 0 }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>รายการกิจกรรมที่ได้รับชั่วโมง ({{ $items->count() }} รายการ)</h2>
    <table>
        <thead>
            <tr>
                <th>ลำดับ</th>
                <th>วันที่</th>
                <th>รายการ</th>
                <th>หมวดหมู่</th>
                <th>ประเภท</th>
                <th class="num">ชั่วโมง</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item->date->format('d/m/Y') }}</td>
                    <td>{{ $item->title }}</td>
                    <td>{{ $categoryLabels[$item->category] ?? $item->category }}</td>
                    <td>{{ $item->source }}</td>
                    <td class="num">{{ $item->hours }}</td>
                </tr>
            @empty
                <tr><td colspan="6">ยังไม่มีกิจกรรมที่ได้รับชั่วโมง</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="disclaimer">เอกสารนี้สรุปข้อมูลจากระบบเช็กชื่อกิจกรรมนักศึกษา ณ วันที่ออกเอกสารข้างต้น เพื่อใช้ติดตามความคืบหน้าของตนเองเท่านั้น ไม่ใช่เอกสารรับรองผลอย่างเป็นทางการจากมหาวิทยาลัย</p>
</body>
</html>
