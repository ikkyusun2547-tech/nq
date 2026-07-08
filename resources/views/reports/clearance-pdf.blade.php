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
        h1 { font-size: 16px; margin-bottom: 2px; }
        p.sub { color: #555; margin-top: 0; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background: #f3f4f6; }
        td.num { text-align: right; }
    </style>
</head>
<body>
    <h1>รายชื่อนักศึกษาชั้นปีที่ {{ $year }} ที่ผ่านเกณฑ์กิจกรรมครบถ้วน (พร้อมยื่นจบ)</h1>
    <p class="sub">มหาวิทยาลัยราชภัฏสุรินทร์ — ออกรายงานเมื่อ {{ $generatedAt->format('d/m/Y H:i') }} น. · จำนวน {{ $students->count() }} คน</p>

    <table>
        <thead>
            <tr>
                <th>ลำดับ</th>
                <th>รหัสนักศึกษา</th>
                <th>ชื่อ-นามสกุล</th>
                <th>คณะ</th>
                <th>สาขา</th>
                <th class="num">กิจกรรมสะสม</th>
                <th class="num">ชั่วโมงสะสม</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($students as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $row['user']->student_id }}</td>
                    <td>{{ $row['user']->name_thai ?? $row['user']->name }}</td>
                    <td>{{ $row['user']->faculty?->name_th }}</td>
                    <td>{{ $row['user']->major?->name_th }}</td>
                    <td class="num">{{ $row['total_activities'] }}</td>
                    <td class="num">{{ $row['total_hours'] }}</td>
                </tr>
            @empty
                <tr><td colspan="7">ไม่มีนักศึกษาที่ผ่านเกณฑ์ครบถ้วนในชั้นปีนี้</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
