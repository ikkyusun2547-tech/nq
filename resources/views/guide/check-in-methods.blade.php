@extends('layouts.dashboard')

@section('content')
@php
    $isStudent = auth()->user()?->role === 'student';
    $validityMinutes = (int) ceil(app(\App\Services\DynamicQrTokenGenerator::class)->scanValiditySeconds() / 60);

    // A short student guide. Facts mirror AttendanceAutomationService,
    // DynamicQrTokenGenerator and Activity::acceptsCheckIn/acceptsLateRequest.
    $steps = [
        [__('สแกน QR'), __('กดปุ่มสแกน แล้วส่องจอที่หน้างาน'), 'M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z'],
        [__('ถ่ายเซลฟี'), __('ยืนยันว่าเป็นตัวเรา'), 'M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z'],
        [__('กดส่ง'), __('ได้ชั่วโมงทันที'), 'M4.5 12.75l6 6 9-13.5'],
    ];

    $evidenceSteps = [
        [__('เปิดกิจกรรม'), __('ในช่วงเวลาที่เปิดให้ส่ง'), 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
        [__('แนบรูป'), __('ถ่ายหรือเลือกรูปหลักฐาน'), 'M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z'],
        [__('กดส่ง'), __('รอเจ้าหน้าที่ตรวจ'), 'M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5'],
    ];

@endphp

<div class="mx-auto max-w-3xl" x-data="{ tab: (['qr', 'evidence', 'late', 'external', 'credit'].includes(location.hash.slice(1)) ? location.hash.slice(1) : 'qr'), go(key) { this.tab = key; history.replaceState(null, '', '#' + key); } }">
    <x-brand-header eyebrow="{{ __('คู่มือนักศึกษา') }}" :title="__('วิธีเช็คชื่อกิจกรรม')" />

    {{-- Which method an activity uses is the organizer's call. --}}
    <div class="mb-4 flex gap-3 rounded-2xl border border-brand-purple-100 bg-brand-purple-50 p-4 text-sm text-brand-purple-900 dark:border-brand-purple-500/20 dark:bg-brand-purple-500/10 dark:text-brand-purple-100">
        <svg class="mt-0.5 h-5 w-5 shrink-0 text-brand-purple-600 dark:text-brand-purple-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/></svg>
        <p class="leading-relaxed">
            {{ __('แต่ละกิจกรรมใช้วิธีเช็คชื่อตามที่กองพัฒนานักศึกษากำหนด') }}
            <span class="font-semibold">{{ __('ดูได้ที่หน้ารายละเอียดของกิจกรรมนั้น') }}</span>
        </p>
    </div>

    {{-- Topic picker: one guide at a time instead of one long page.
         The choice lives in the URL hash so a link can open a topic directly. --}}
    @php
        $topics = [
            __('เช็คชื่อ') => [
                'qr' => [__('สแกน QR'), 'M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z'],
                'evidence' => [__('แนบหลักฐาน'), 'M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z'],
                'late' => [__('ย้อนหลัง'), 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z'],
            ],
            __('ขอชั่วโมง') => [
                'external' => [__('กิจกรรมภายนอก'), 'M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418'],
                'credit' => [__('เทียบโอนตำแหน่ง'), 'M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5'],
            ],
        ];
    @endphp
    <nav aria-label="{{ __('หัวข้อ') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-[3fr_2fr]">
        @foreach ($topics as $group => $items)
            <div>
                <p class="mb-2 text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $group }}</p>
                <div class="grid gap-2" style="grid-template-columns: repeat({{ count($items) }}, minmax(0, 1fr));">
                    @foreach ($items as $key => [$label, $icon])
                        <button type="button" @click="go('{{ $key }}')" :aria-current="tab === '{{ $key }}'"
                            class="flex flex-col items-center gap-1.5 rounded-2xl border px-2 py-3 text-center text-xs font-semibold transition-colors sm:text-sm"
                            :class="tab === '{{ $key }}'
                                ? 'border-brand-purple-700 bg-brand-purple-700 text-white dark:border-brand-purple-500 dark:bg-brand-purple-600'
                                : 'border-slate-200 bg-white text-slate-700 hover:border-brand-purple-300 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200'">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>
        @endforeach
    </nav>

    {{-- Main method --}}
    <section x-show="tab === 'qr'" class="mt-4 rounded-3xl bg-brand-purple-700 p-5 text-white dark:bg-brand-purple-600/90 sm:p-7">
        <p class="text-sm font-semibold text-white/80">{{ __('วิธีที่ 1') }}</p>
        <h2 class="mt-1 font-display text-2xl">{{ __('เช็คชื่อหน้างานใน 3 ขั้นตอน') }}</h2>

        <ol class="mt-5 grid grid-cols-3 gap-2 sm:gap-3">
            @foreach ($steps as $n => [$stepTitle, $stepText, $stepIcon])
                <li class="rounded-2xl bg-white/10 p-3 text-center sm:p-4">
                    <span class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-white text-brand-purple-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $stepIcon }}"/></svg>
                    </span>
                    <p class="mt-2.5 text-sm font-semibold sm:text-base">{{ $n + 1 }}. {{ $stepTitle }}</p>
                    <p class="mt-0.5 text-xs leading-snug text-white/75 sm:text-sm">{{ $stepText }}</p>
                </li>
            @endforeach
        </ol>

        <ul class="mt-5 space-y-1 text-sm text-white/85">
            <li>• {{ __('ต้องอยู่ในพื้นที่จัดงาน (ระบบเช็ค GPS)') }}</li>
            <li>• {{ __('ส่งให้เสร็จภายใน :m นาทีหลังสแกน', ['m' => $validityMinutes]) }}</li>
        </ul>

        @if ($isStudent)
            <a href="{{ route('checkin.show') }}" class="mt-5 flex h-12 items-center justify-center gap-2 rounded-2xl bg-white text-[0.95rem] font-semibold text-brand-purple-800 transition-colors hover:bg-brand-purple-50">
                {{ __('ไปสแกน QR') }}
            </a>
        @endif
    </section>

    {{-- Evidence (self-report) method --}}
    <section x-show="tab === 'evidence'" x-cloak class="mt-4 rounded-3xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900 sm:p-7">
        <p class="text-sm font-semibold text-fuchsia-700 dark:text-fuchsia-300">{{ __('วิธีที่ 2') }}</p>
        <h2 class="mt-1 font-display text-2xl text-slate-900 dark:text-white">{{ __('แนบรูปหลักฐานใน 3 ขั้นตอน') }}</h2>

        <ol class="mt-5 grid grid-cols-3 gap-2 sm:gap-3">
            @foreach ($evidenceSteps as $n => [$stepTitle, $stepText, $stepIcon])
                <li class="rounded-2xl bg-fuchsia-50 p-3 text-center dark:bg-fuchsia-500/10 sm:p-4">
                    <span class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-fuchsia-600 text-white">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $stepIcon }}"/></svg>
                    </span>
                    <p class="mt-2.5 text-sm font-semibold text-slate-900 dark:text-white sm:text-base">{{ $n + 1 }}. {{ $stepTitle }}</p>
                    <p class="mt-0.5 text-xs leading-snug text-slate-600 dark:text-slate-400 sm:text-sm">{{ $stepText }}</p>
                </li>
            @endforeach
        </ol>

        <ul class="mt-5 space-y-1 text-sm text-slate-600 dark:text-slate-300">
            <li>• {{ __('ไม่ต้องสแกน QR และไม่เช็ค GPS') }}</li>
            <li>• {{ __('ปุ่ม "ส่งหลักฐานเช็คชื่อ" จะขึ้นเฉพาะช่วงเวลาที่ผู้จัดเปิดให้ส่ง') }}</li>
            <li>• {{ __('ใช้รูปที่เห็นว่าเข้าร่วมกิจกรรมจริง ส่งได้ครั้งเดียว') }}</li>
        </ul>

        @if ($isStudent)
            <a href="{{ route('activities.index') }}" class="mt-5 flex h-12 items-center justify-center rounded-2xl border border-slate-200 text-[0.95rem] font-semibold text-slate-800 transition-colors hover:border-fuchsia-300 hover:text-fuchsia-700 dark:border-slate-700 dark:text-slate-100">
                {{ __('ดูกิจกรรมของฉัน') }}
            </a>
        @endif
    </section>

    {{-- Late check-in request (rules: Activity::acceptsLateRequest,
         LateCheckInController::ensureNoUnresolvedRequest, LateCheckInStoreRequest) --}}
    <section x-show="tab === 'late'" x-cloak class="mt-4 rounded-3xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900 sm:p-7">
        <p class="text-sm font-semibold text-amber-700 dark:text-amber-300">{{ __('เช็คชื่อไม่ทัน?') }}</p>
        <h2 class="mt-1 font-display text-2xl text-slate-900 dark:text-white">{{ __('ขอเช็คชื่อย้อนหลัง') }}</h2>
        <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400">{{ __('ถ้าเข้าร่วมกิจกรรมจริง แต่เช็คชื่อไม่สำเร็จ เช่น แบตหมด หรืออินเทอร์เน็ตใช้ไม่ได้ ยื่นคำร้องให้เจ้าหน้าที่พิจารณาได้') }}</p>

        <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="rounded-2xl bg-amber-50 p-4 dark:bg-amber-500/10">
                <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('เงื่อนไข') }}</p>
                <ul class="mt-2 space-y-1.5 text-sm text-slate-700 dark:text-slate-300">
                    @foreach ([
                        __('ยื่นได้หลังกิจกรรมปิดแล้วเท่านั้น'),
                        __('ต้องเป็นกิจกรรมที่คุณมีสิทธิ์เข้าร่วม'),
                        __('ต้องยังไม่มีการเช็คชื่อกิจกรรมนั้น'),
                        __('ยื่นได้ทีละ 1 คำร้องต่อกิจกรรม ถ้าถูกปฏิเสธยื่นใหม่ได้'),
                    ] as $rule)
                        <li class="flex gap-2">
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            <span>{{ $rule }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-800/60">
                <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('วิธียื่น') }}</p>
                <ol class="mt-2 space-y-1.5 text-sm text-slate-700 dark:text-slate-300">
                    @foreach ([
                        __('ไปที่หน้ากิจกรรม แท็บ "จบไปแล้ว" แล้วเปิดกิจกรรมนั้น'),
                        __('กดปุ่ม "ขอเช็คชื่อย้อนหลัง"'),
                        __('เขียนเหตุผล (ไม่เกิน 500 ตัวอักษร) และแนบรูปหลักฐาน (ไม่เกิน 2 MB)'),
                        __('กดส่ง แล้วรอผล ระบบจะแจ้งเตือนเมื่อพิจารณาแล้ว'),
                    ] as $n => $step)
                        <li class="flex gap-2">
                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-slate-900 text-[0.7rem] font-semibold text-white dark:bg-white dark:text-slate-900">{{ $n + 1 }}</span>
                            <span>{{ $step }}</span>
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>

        <p class="mt-4 text-xs text-slate-500 dark:text-slate-400">{{ __('เจ้าหน้าที่อาจอนุมัติชั่วโมงน้อยกว่าที่กิจกรรมกำหนด ขึ้นอยู่กับหลักฐาน') }}</p>

        @if ($isStudent)
            <a href="{{ route('activities.index', ['status_group' => 'ended']) }}" class="mt-5 flex h-12 items-center justify-center rounded-2xl border border-slate-200 text-[0.95rem] font-semibold text-slate-800 transition-colors hover:border-amber-300 hover:text-amber-700 dark:border-slate-700 dark:text-slate-100">
                {{ __('ดูกิจกรรมที่จบไปแล้ว') }}
            </a>
        @endif
    </section>
    {{-- Hour requests outside check-in. Rules mirror ExternalActivityStoreRequest
         (incl. ExternalActivityRequest::ANNUAL_HOUR_CAP) and CreditTransferStoreRequest. --}}
    @php
        $positionHours = collect(\App\Models\CreditTransferPosition::labelsMap())
            ->map(fn ($label, $key) => [__($label), \App\Models\CreditTransferPosition::hoursMap()[$key]])
            ->values();
        $requestGuides = [
            [
                'key' => 'external',
                'eyebrow' => __('ไปร่วมกิจกรรมนอกมหาวิทยาลัย?'),
                'title' => __('ขอชั่วโมงกิจกรรมภายนอก'),
                'intro' => __('กิจกรรมที่หน่วยงานอื่นจัด เช่น บริจาคโลหิต แข่งขันวิชาการ หรืองานจิตอาสา นำมาขอชั่วโมงได้'),
                'tone' => 'text-sky-700 dark:text-sky-300',
                'box' => 'bg-sky-50 dark:bg-sky-500/10',
                'tick' => 'text-sky-600 dark:text-sky-400',
                'rules' => [
                    __('ต้องเป็นกิจกรรมที่จัดไปแล้ว'),
                    __('รวมได้ไม่เกิน :cap ชั่วโมงต่อปีการศึกษา', ['cap' => \App\Models\ExternalActivityRequest::ANNUAL_HOUR_CAP]),
                    __('มีหลักฐาน เช่น เกียรติบัตร หรือรูปถ่าย (JPG, PNG, PDF ไม่เกิน 2 MB)'),
                ],
                'steps' => [
                    __('ไปที่เมนู "คำร้อง" แท็บ "กิจกรรมภายนอก"'),
                    __('กรอกชื่อกิจกรรม หน่วยงานที่จัด วันที่ หมวดหมู่ และจำนวนชั่วโมง'),
                    __('แนบหลักฐาน แล้วกดส่ง'),
                    __('รอเจ้าหน้าที่ตรวจ (ยกเลิกได้ระหว่างรอ)'),
                ],
                'url' => $isStudent ? route('hour-requests.index', ['tab' => 'external']) : null,
                'cta' => __('ยื่นคำร้องกิจกรรมภายนอก'),
            ],
            [
                'key' => 'credit',
                'eyebrow' => __('ดำรงตำแหน่งในองค์กรนักศึกษา?'),
                'title' => __('เทียบโอนชั่วโมงจากตำแหน่ง'),
                'intro' => __('ผู้ดำรงตำแหน่งได้รับชั่วโมงกิจกรรมตามที่มหาวิทยาลัยกำหนดให้แต่ละตำแหน่ง'),
                'tone' => 'text-emerald-700 dark:text-emerald-300',
                'box' => 'bg-emerald-50 dark:bg-emerald-500/10',
                'tick' => 'text-emerald-600 dark:text-emerald-400',
                'rules' => [
                    __('ยื่นได้ 1 ครั้งต่อปีการศึกษา'),
                    __('ชั่วโมงเป็นไปตามตำแหน่ง (ดูตารางด้านล่าง)'),
                    __('มีหลักฐาน เช่น คำสั่งแต่งตั้ง (JPG, PNG, PDF ไม่เกิน 2 MB)'),
                ],
                'steps' => [
                    __('ไปที่เมนู "คำร้อง" แท็บ "เทียบโอนตำแหน่ง"'),
                    __('เลือกตำแหน่งและปีการศึกษา'),
                    __('แนบหลักฐาน แล้วกดส่ง'),
                    __('รอเจ้าหน้าที่ตรวจ (ยกเลิกได้ระหว่างรอ)'),
                ],
                'positions' => $positionHours,
                'url' => $isStudent ? route('hour-requests.index', ['tab' => 'credit']) : null,
                'cta' => __('ยื่นคำร้องเทียบโอน'),
            ],
        ];
    @endphp


    @foreach ($requestGuides as $g)
        <section x-show="tab === '{{ $g['key'] }}'" x-cloak class="mt-4 rounded-3xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900 sm:p-7">
            <p class="text-sm font-semibold {{ $g['tone'] }}">{{ $g['eyebrow'] }}</p>
            <h2 class="mt-1 font-display text-2xl text-slate-900 dark:text-white">{{ $g['title'] }}</h2>
            <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400">{{ $g['intro'] }}</p>

            <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="rounded-2xl p-4 {{ $g['box'] }}">
                    <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('เงื่อนไข') }}</p>
                    <ul class="mt-2 space-y-1.5 text-sm text-slate-700 dark:text-slate-300">
                        @foreach ($g['rules'] as $rule)
                            <li class="flex gap-2">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 {{ $g['tick'] }}" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                <span>{{ $rule }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-800/60">
                    <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('วิธียื่น') }}</p>
                    <ol class="mt-2 space-y-1.5 text-sm text-slate-700 dark:text-slate-300">
                        @foreach ($g['steps'] as $n => $step)
                            <li class="flex gap-2">
                                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-slate-900 text-[0.7rem] font-semibold text-white dark:bg-white dark:text-slate-900">{{ $n + 1 }}</span>
                                <span>{{ $step }}</span>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </div>

            @isset($g['positions'])
                <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800">
                    <p class="border-b border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-semibold text-slate-900 dark:border-slate-800 dark:bg-slate-800/60 dark:text-white">{{ __('ชั่วโมงตามตำแหน่ง') }}</p>
                    <ul class="divide-y divide-slate-100 text-sm dark:divide-slate-800">
                        @foreach ($g['positions'] as [$label, $hours])
                            <li class="flex items-center justify-between gap-3 px-4 py-2.5">
                                <span class="text-slate-700 dark:text-slate-300">{{ $label }}</span>
                                <span class="shrink-0 font-semibold text-slate-900 dark:text-white">{{ $hours }} {{ __('ชม.') }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endisset

            @if ($g['url'])
                <a href="{{ $g['url'] }}" class="mt-5 flex h-12 items-center justify-center rounded-2xl border border-slate-200 text-[0.95rem] font-semibold text-slate-800 transition-colors hover:border-brand-purple-300 hover:text-brand-purple-700 dark:border-slate-700 dark:text-slate-100">
                    {{ $g['cta'] }}
                </a>
            @endif
        </section>
    @endforeach
    {{-- Pending review --}}
    <section x-show="['qr', 'evidence'].includes(tab)" class="mb-4 mt-4 rounded-3xl bg-amber-50 p-5 dark:bg-amber-500/10">
        <p class="font-semibold text-amber-900 dark:text-amber-200">{{ __('ขึ้นว่า "รอตรวจสอบ"?') }}</p>
        <p class="mt-1 text-sm leading-relaxed text-amber-900/80 dark:text-amber-100/80">
            {{ __('เช็คชื่อสำเร็จแล้ว แค่รอเจ้าหน้าที่ดูรูปก่อน ชั่วโมงจะเข้าเมื่ออนุมัติ เช่น เมื่อ GPS อยู่นอกพื้นที่ หรือส่งแบบแนบรูปหลักฐาน') }}
            @if ($isStudent)
                <a href="{{ route('contact.index') }}" class="font-semibold underline underline-offset-2">{{ __('มีปัญหา? ติดต่อเจ้าหน้าที่') }}</a>
            @endif
        </p>
    </section>
</div>
@endsection
