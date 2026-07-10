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

        * { box-sizing: border-box; }
        body { font-family: 'Sarabun', sans-serif; font-size: 11px; color: #1e1b2e; margin: 0; }

        /* Header band */
        .header { background-image: url('{{ $headerGradient }}'); background-size: 100% 100%; padding: 22px 28px; color: #fff; }
        .header table { width: 100%; border-collapse: collapse; }
        .header td { border: none; padding: 0; vertical-align: middle; }
        .header .eyebrow { font-size: 9px; letter-spacing: 2px; text-transform: uppercase; color: #d8d3f5; margin: 0 0 3px; }
        .header h1 { font-size: 19px; margin: 0; color: #fff; }
        .header .meta { font-size: 9.5px; color: #d8d3f5; margin: 3px 0 0; }
        .logo-badge { width: 46px; height: 46px; border-radius: 10px; background: rgba(255,255,255,0.14); text-align: center; }

        .body { padding: 22px 28px 10px; }

        /* Student info card */
        .info-card { border: 1px solid #e4e0f5; border-radius: 10px; padding: 14px 18px; background: #faf9ff; }
        .info-card table { width: 100%; border-collapse: collapse; }
        .info-card td { border: none; padding: 3px 6px 3px 0; font-size: 11px; }
        .info-card td.label { color: #7a7592; width: 105px; }
        .info-card td.value { font-weight: bold; color: #1e1b2e; }

        /* Status pill */
        .status-row { margin: 14px 0; }
        .status-pill { display: inline-block; border-radius: 8px; padding: 10px 16px; font-size: 11.5px; }
        .status-pass { background: #e3fbef; color: #05603e; }
        .status-fail { background: #fff6e0; color: #8a5a00; }
        .status-pill strong { font-size: 13px; }

        h2.section { font-size: 12.5px; color: #2e1065; margin: 20px 0 8px; padding-bottom: 5px; border-bottom: 2px solid #ede9fe; }

        /* Progress bars */
        .meter-row { margin-bottom: 10px; }
        .meter-label { font-size: 10.5px; color: #4b4763; margin-bottom: 3px; }
        .meter-label .value { float: right; color: #8a86a3; }
        .meter-track { background: #ede9fe; border-radius: 5px; height: 8px; }
        .meter-fill { background: #10b981; border-radius: 5px; height: 8px; }

        /* Category breakdown */
        .cat-row { margin-bottom: 9px; }
        .cat-label { font-size: 10px; color: #4b4763; margin-bottom: 3px; }
        .cat-dot { display: inline-block; width: 7px; height: 7px; border-radius: 4px; margin-right: 5px; }
        .cat-hours { float: right; color: #8a86a3; }
        .cat-track { background: #f1effa; border-radius: 4px; height: 5px; }
        .cat-fill { border-radius: 4px; height: 5px; }

        /* Activity table */
        table.items { width: 100%; border-collapse: collapse; margin-top: 4px; }
        table.items th { background: #2e1065; color: #fff; font-weight: normal; font-size: 10px; padding: 7px 8px; text-align: left; }
        table.items th.num { text-align: right; }
        table.items td { padding: 6px 8px; font-size: 10px; border-bottom: 1px solid #efedf7; }
        table.items td.num { text-align: right; }
        table.items tr.odd td { background: #faf9ff; }
        table.items td.empty { text-align: center; color: #8a86a3; padding: 16px; }

        .footer { margin-top: 22px; padding-top: 10px; border-top: 1px solid #efedf7; font-size: 8.5px; color: #9a96b0; }
    </style>
</head>
<body>
    <div class="header">
        <table>
            <tr>
                <td style="width: 58px;">
                    <div class="logo-badge">
                        <img src="{{ public_path('images/icons/icon-192.png') }}" width="46" height="46" style="border-radius: 10px;">
                    </div>
                </td>
                <td>
                    <p class="eyebrow">มหาวิทยาลัยราชภัฏสุรินทร์</p>
                    <h1>ใบสรุปชั่วโมงกิจกรรมนักศึกษา</h1>
                    <p class="meta">ออกเมื่อ {{ $generatedAt->format('d/m/Y H:i') }} น.</p>
                </td>
            </tr>
        </table>
    </div>

    <div class="body">
        <div class="info-card">
            <table>
                <tr>
                    <td class="label">ชื่อ-นามสกุล</td>
                    <td class="value">{{ $user->name_thai ?? $user->name }}</td>
                    <td class="label">รหัสนักศึกษา</td>
                    <td class="value">{{ $user->student_id }}</td>
                </tr>
                <tr>
                    <td class="label">คณะ</td>
                    <td class="value">{{ $user->faculty?->name_th }}</td>
                    <td class="label">สาขา</td>
                    <td class="value">{{ $user->major?->name_th }}</td>
                </tr>
                <tr>
                    <td class="label">ชั้นปีที่</td>
                    <td class="value">{{ $user->year_level }}</td>
                    <td class="label">ประเภทหลักสูตร</td>
                    <td class="value">{{ $user->program_type === 'special' ? 'กศ.บป.' : 'ภาคปกติ' }}</td>
                </tr>
            </table>
        </div>

        <div class="status-row">
            <div class="status-pill {{ $summary['is_cleared'] ? 'status-pass' : 'status-fail' }}">
                @if ($summary['is_cleared'])
                    <strong>&#10003; ผ่านเกณฑ์กิจกรรมแล้ว</strong> — สะสมครบ {{ $summary['total_activities'] }} กิจกรรม / {{ $summary['total_hours'] }} ชั่วโมง (เกณฑ์ {{ $summary['required_activities'] }} กิจกรรม / {{ $summary['required_hours'] }} ชั่วโมง)
                @else
                    <strong>ยังไม่ผ่านเกณฑ์</strong> — สะสม {{ $summary['total_activities'] }}/{{ $summary['required_activities'] }} กิจกรรม, {{ $summary['total_hours'] }}/{{ $summary['required_hours'] }} ชั่วโมง
                @endif
            </div>
        </div>

        @php
            $hoursPct = min(100, $summary['required_hours'] > 0 ? round($summary['total_hours'] / $summary['required_hours'] * 100) : 0);
            $activitiesPct = min(100, $summary['required_activities'] > 0 ? round($summary['total_activities'] / $summary['required_activities'] * 100) : 0);
        @endphp

        <h2 class="section">ความคืบหน้าโดยรวม</h2>
        <div class="meter-row">
            <div class="meter-label">ชั่วโมงสะสมรวม <span class="value">{{ $summary['total_hours'] }} / {{ $summary['required_hours'] }} ชม.</span></div>
            <div class="meter-track"><div class="meter-fill" style="width: {{ max(3, $hoursPct) }}%;"></div></div>
        </div>
        <div class="meter-row">
            <div class="meter-label">จำนวนกิจกรรมสะสม <span class="value">{{ $summary['total_activities'] }} / {{ $summary['required_activities'] }} งาน</span></div>
            <div class="meter-track"><div class="meter-fill" style="width: {{ max(3, $activitiesPct) }}%;"></div></div>
        </div>

        <h2 class="section">ชั่วโมงสะสมแยกตามหมวดหมู่</h2>
        @foreach ($categoryLabels as $key => $label)
            @php
                $hours = $summary['category_hours'][$key] ?? 0;
                $pct = min(100, $summary['required_hours'] > 0 ? round($hours / $summary['required_hours'] * 100) : 0);
            @endphp
            <div class="cat-row">
                <div class="cat-label">
                    <span class="cat-dot" style="background: {{ $categoryColors[$key] }};"></span>{{ $label }}
                    <span class="cat-hours">{{ $hours }} ชม.</span>
                </div>
                <div class="cat-track"><div class="cat-fill" style="width: {{ max(2, $pct) }}%; background: {{ $categoryColors[$key] }};"></div></div>
            </div>
        @endforeach

        <h2 class="section">รายการกิจกรรมที่ได้รับชั่วโมง ({{ $items->count() }} รายการ)</h2>
        <table class="items">
            <thead>
                <tr>
                    <th style="width: 26px;">#</th>
                    <th style="width: 62px;">วันที่</th>
                    <th>รายการ</th>
                    <th style="width: 110px;">หมวดหมู่</th>
                    <th style="width: 80px;">ประเภท</th>
                    <th class="num" style="width: 40px;">ชม.</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $i => $item)
                    <tr class="{{ $i % 2 === 1 ? 'odd' : '' }}">
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $item->date->format('d/m/Y') }}</td>
                        <td>{{ $item->title }}</td>
                        <td>{{ $categoryLabels[$item->category] ?? $item->category }}</td>
                        <td>{{ $item->source }}</td>
                        <td class="num">{{ $item->hours }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty">ยังไม่มีกิจกรรมที่ได้รับชั่วโมง</td></tr>
                @endforelse
            </tbody>
        </table>

        <p class="footer">เอกสารนี้สรุปข้อมูลจากระบบเช็กชื่อกิจกรรมนักศึกษา ณ วันที่ออกเอกสารข้างต้น เพื่อใช้ติดตามความคืบหน้าของตนเองเท่านั้น ไม่ใช่เอกสารรับรองผลอย่างเป็นทางการจากมหาวิทยาลัย</p>
    </div>
</body>
</html>
