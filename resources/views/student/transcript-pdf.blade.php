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

        /* Same palette as the web app's design C: purple-tinted neutrals,
           purple-700 as the accent, green only for "passed". dompdf has no
           flexbox or SVG, so layouts are tables and the ring is a PNG. */
        @page { margin: 28px 34px 34px; }
        * { box-sizing: border-box; }
        body { font-family: 'Sarabun', sans-serif; font-size: 10.5px; color: #1d1a29; margin: 0; }
        table { border-collapse: collapse; width: 100%; }
        td, th { vertical-align: top; padding: 0; }
        p { margin: 0; }
        .muted { color: #6f6a85; }

        /* Top line */
        .top td { vertical-align: middle; }
        .top .uni { font-size: 9.5px; font-weight: bold; color: #1d1a29; }
        .top .sub { font-size: 8.5px; color: #6f6a85; }
        .top .doc { text-align: right; font-size: 8.5px; color: #6f6a85; line-height: 1.5; }
        .top .doc b { font-size: 10px; color: #1d1a29; }

        /* Hero */
        .hero { margin-top: 14px; background: #6d28d9; border-radius: 16px; padding: 20px 22px; color: #fff; }
        .hero td { vertical-align: middle; }
        .eyebrow { font-size: 8px; letter-spacing: 2px; color: #ddd0fb; }
        .name { font-size: 21px; font-weight: bold; margin-top: 2px; }
        .facts { font-size: 9.5px; color: #ece4fd; margin-top: 3px; }
        .minis { margin-top: 14px; }
        .minis td { padding-right: 8px; }
        .mini { background: #7f43e0; border-radius: 10px; padding: 8px 12px; }
        .mini .k { font-size: 8px; color: #ddd0fb; }
        .mini .v { font-size: 15px; font-weight: bold; margin-top: 1px; }
        .mini .v span { font-size: 9px; font-weight: normal; color: #ddd0fb; }
        .ring-cap { text-align: center; font-size: 8.5px; color: #ddd0fb; margin-top: 3px; }

        /* Status strip */
        .status { margin-top: 12px; border-radius: 10px; padding: 9px 14px; font-size: 10px; }
        .status b { font-size: 11px; }
        .status.pass { background: #ecfdf5; color: #065f46; }
        .status.fail { background: #fffbeb; color: #92400e; }

        h2 { font-size: 12.5px; margin: 22px 0 10px; color: #1d1a29; }
        h2 span { font-size: 10px; font-weight: normal; color: #6f6a85; }

        /* Category: one stacked bar + legend */
        .stack { border-radius: 6px; overflow: hidden; height: 10px; background: #efedf5; }
        .stack td { height: 10px; }
        .legend { margin-top: 10px; }
        .legend td { width: 20%; padding-right: 8px; }
        .legend .dot { display: inline-block; width: 7px; height: 7px; border-radius: 4px; margin-right: 4px; }
        .legend .k { font-size: 8.5px; color: #4b4763; }
        .legend .v { font-size: 14px; font-weight: bold; margin-top: 1px; }
        .legend .v span { font-size: 8.5px; font-weight: normal; color: #6f6a85; }

        /* Activity list */
        .list { border: 1px solid #ebe8f3; border-radius: 12px; padding: 4px 14px; }
        table.items th { font-size: 8.5px; font-weight: bold; color: #8b86a0; text-align: left; padding: 8px 6px; border-bottom: 1px solid #ebe8f3; }
        table.items td { font-size: 10px; padding: 8px 6px; border-bottom: 1px solid #f2f0f7; vertical-align: middle; }
        table.items tr.last td { border-bottom: none; }
        table.items .num { text-align: right; }
        table.items .title { font-weight: bold; }
        table.items .src { font-size: 8.5px; color: #8b86a0; }
        table.items .chip { display: inline-block; border-radius: 9px; padding: 2px 8px; font-size: 8.5px; }
        table.items .hours { font-size: 12px; font-weight: bold; }
        table.items .hours span { font-size: 8.5px; font-weight: normal; color: #8b86a0; }
        table.items td.empty { text-align: center; color: #8b86a0; padding: 22px; }
        .total { text-align: right; margin-top: 8px; font-size: 10px; color: #6f6a85; }
        .total b { font-size: 13px; color: #1d1a29; }

        .footer { margin-top: 18px; padding-top: 8px; border-top: 1px solid #ebe8f3; font-size: 7.5px; color: #9a96b0; line-height: 1.5; }
    </style>
</head>
<body>
    @php
        $hoursPct = min(100, $summary['required_hours'] > 0 ? round($summary['total_hours'] / $summary['required_hours'] * 100) : 0);
        $missingHours = max(0, $summary['required_hours'] - $summary['total_hours']);
        $missingActivities = max(0, $summary['required_activities'] - $summary['total_activities']);
        $buddhistDate = fn ($date) => $date->format('d/m/').($date->year + 543);
        $ring = \App\Support\PdfDonut::dataUri($hoursPct, $hoursPct.'%', [109, 40, 217], [141, 87, 225], [255, 255, 255]);
        // Soft chip colours per category (light background + dark text).
        $chip = [
            'culture' => 'background: #e0f2fe; color: #075985;',
            'academic' => 'background: #d1fae5; color: #065f46;',
            'sports' => 'background: #fef3c7; color: #92400e;',
            'volunteer' => 'background: #ede9fe; color: #5b21b6;',
            'ethics' => 'background: #fae8ff; color: #86198f;',
        ];
        $categoryTotal = array_sum(array_map(fn ($k) => $summary['category_hours'][$k] ?? 0, array_keys($categoryLabels)));
    @endphp

    <table class="top">
        <tr>
            <td style="width: 46px;"><img src="{{ public_path('images/logo.png') }}" width="38" height="38"></td>
            <td>
                <p class="uni">มหาวิทยาลัยราชภัฏสุรินทร์</p>
                <p class="sub">ระบบเช็คชื่อกิจกรรมนักศึกษา · SRRU Check</p>
            </td>
            <td class="doc" style="width: 200px;">
                <b>ใบสรุปชั่วโมงกิจกรรม</b><br>
                ออกเมื่อ {{ $buddhistDate($generatedAt) }} เวลา {{ $generatedAt->format('H:i') }} น.
            </td>
        </tr>
    </table>

    <div class="hero">
        <table>
            <tr>
                <td>
                    <p class="eyebrow">ACTIVITY PASSPORT</p>
                    <p class="name">{{ $user->name_thai ?? $user->name }}</p>
                    <p class="facts">
                        รหัส {{ $user->student_id ?? '-' }} &nbsp;·&nbsp; ชั้นปีที่ {{ $user->year_level ?? '-' }} &nbsp;·&nbsp; {{ $user->program_type === 'special' ? 'กศ.บป.' : 'ภาคปกติ' }}<br>
                        {{ $user->faculty?->name_th ?? '-' }} &nbsp;·&nbsp; {{ $user->major?->name_th ?? '-' }}
                    </p>
                    <table class="minis" style="width: auto;">
                        <tr>
                            <td><div class="mini"><p class="k">ชั่วโมงสะสม</p><p class="v">{{ $summary['total_hours'] }} <span>/ {{ $summary['required_hours'] }} ชม.</span></p></div></td>
                            <td><div class="mini"><p class="k">กิจกรรมสะสม</p><p class="v">{{ $summary['total_activities'] }} <span>/ {{ $summary['required_activities'] }} กิจกรรม</span></p></div></td>
                            <td><div class="mini"><p class="k">รายการที่ได้รับชั่วโมง</p><p class="v">{{ $items->count() }} <span>รายการ</span></p></div></td>
                        </tr>
                    </table>
                </td>
                <td style="width: 120px; text-align: center;">
                    <img src="{{ $ring }}" width="104" height="104">
                    <p class="ring-cap">ของชั่วโมงตามเกณฑ์</p>
                </td>
            </tr>
        </table>
    </div>

    @if ($summary['is_cleared'])
        <div class="status pass"><b>&#10003; ผ่านเกณฑ์กิจกรรมแล้ว</b> &nbsp;— สะสมครบตามเกณฑ์ของมหาวิทยาลัย</div>
    @else
        <div class="status fail"><b>ยังไม่ผ่านเกณฑ์</b> &nbsp;— ขาดอีก {{ $missingActivities }} กิจกรรม และ {{ $missingHours }} ชั่วโมง</div>
    @endif

    <h2>ชั่วโมงแยกตามหมวดหมู่ <span>· 5 ด้าน</span></h2>
    <div class="stack">
        @if ($categoryTotal > 0)
            <table>
                <tr>
                    @foreach ($categoryLabels as $key => $label)
                        @php $h = $summary['category_hours'][$key] ?? 0; @endphp
                        @if ($h > 0)
                            <td style="width: {{ round($h / $categoryTotal * 100, 2) }}%; background: {{ $categoryColors[$key] }};"></td>
                        @endif
                    @endforeach
                </tr>
            </table>
        @endif
    </div>
    <table class="legend">
        <tr>
            @foreach ($categoryLabels as $key => $label)
                @php $h = $summary['category_hours'][$key] ?? 0; @endphp
                <td>
                    <p class="k"><span class="dot" style="background: {{ $categoryColors[$key] }};"></span>{{ $label }}</p>
                    <p class="v" style="{{ $h > 0 ? '' : 'color: #b5b1c6;' }}">{{ $h }} <span>ชม.</span></p>
                </td>
            @endforeach
        </tr>
    </table>

    <h2>รายการที่ได้รับชั่วโมง <span>· {{ $items->count() }} รายการ</span></h2>
    <div class="list">
        <table class="items">
            <thead>
                <tr>
                    <th style="width: 22px;">#</th>
                    <th style="width: 64px;">วันที่</th>
                    <th>รายการ</th>
                    <th style="width: 140px;">หมวดหมู่</th>
                    <th class="num" style="width: 50px;">ชั่วโมง</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $i => $item)
                    <tr class="{{ $loop->last ? 'last' : '' }}">
                        <td class="muted">{{ $i + 1 }}</td>
                        <td>{{ $buddhistDate($item->date) }}</td>
                        <td><span class="title">{{ $item->title }}</span><br><span class="src">{{ $item->source }}</span></td>
                        <td><span class="chip" style="{{ $chip[$item->category] ?? 'background: #efedf5; color: #4b4763;' }}">{{ $item->category ? ($categoryLabels[$item->category] ?? $item->category) : '—' }}</span></td>
                        <td class="num hours">{{ $item->hours }} <span>ชม.</span></td>
                    </tr>
                @empty
                    <tr class="last"><td colspan="5" class="empty">ยังไม่มีกิจกรรมที่ได้รับชั่วโมง</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($items->isNotEmpty())
        <p class="total">รวมทั้งหมด &nbsp;<b>{{ $items->sum('hours') }}</b> ชั่วโมง</p>
    @endif

    <p class="footer">เอกสารนี้สรุปข้อมูลจากระบบเช็คชื่อกิจกรรมนักศึกษา (SRRU Check) ณ วันที่ออกเอกสารข้างต้น เพื่อใช้ติดตามความคืบหน้าของตนเองเท่านั้น ไม่ใช่เอกสารรับรองผลอย่างเป็นทางการจากมหาวิทยาลัย</p>
</body>
</html>
