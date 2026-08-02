@php
    // Answers to the questions this contact chat actually gets asked most —
    // each links straight to the feature that already handles it, so a
    // student can self-serve instead of waiting on a reply where possible.
    $linkClass = 'font-medium text-brand-purple-600 underline decoration-brand-purple-200 hover:decoration-brand-purple-600 dark:text-brand-purple-400 dark:decoration-brand-purple-500/40';
    $faqs = [
        [
            'q' => __('เช็คชื่อกิจกรรมไม่ทันหรือลืมเช็คชื่อ ต้องทำอย่างไร?'),
            'a' => __('เปิดหน้ารายละเอียดกิจกรรมนั้นจาก:href_activities แล้วกดปุ่ม "เช็คชื่อย้อนหลัง" แนบหลักฐานเพื่อขอเช็คชื่อย้อนหลังได้เลยครับ', [
                'href_activities' => '<a href="'.route('activities.index').'" class="'.$linkClass.'">'.__('หน้ากิจกรรม').'</a>',
            ]),
        ],
        [
            'q' => __('การเช็คชื่อติดธงแดง (flagged) หมายถึงอะไร?'),
            'a' => __('ระบบตรวจพบว่าตำแหน่ง GPS หรือรูปเซลฟีตอนเช็คชื่อไม่ตรงตามเงื่อนไขที่กำหนด เจ้าหน้าที่จะตรวจสอบและอนุมัติหรือปฏิเสธอีกครั้ง ดูสถานะได้ที่:href_history', [
                'href_history' => '<a href="'.route('activity-history.index').'" class="'.$linkClass.'">'.__('หน้าประวัติกิจกรรม').'</a>',
            ]),
        ],
        [
            'q' => __('เข้าร่วมกิจกรรมภายนอกมหาวิทยาลัย ขอเทียบชั่วโมงได้ไหม?'),
            'a' => __('ได้ครับ ยื่นคำร้องพร้อมแนบหลักฐาน (เกียรติบัตร/ภาพเข้าร่วม) ได้ที่:href_external', [
                'href_external' => '<a href="'.route('hour-requests.index', ['tab' => 'external']).'" class="'.$linkClass.'">'.__('หน้าขอชั่วโมงกิจกรรม แท็บ "กิจกรรมภายนอก"').'</a>',
            ]),
        ],
        [
            'q' => __('เป็นผู้นำนักศึกษา (สโมสร/ชมรม) ขอเทียบโอนชั่วโมงตำแหน่งได้อย่างไร?'),
            'a' => __('ยื่นคำร้องพร้อมหลักฐานการดำรงตำแหน่งได้ที่:href_credit (ทำได้ปีการศึกษาละ 1 ครั้ง)', [
                'href_credit' => '<a href="'.route('hour-requests.index', ['tab' => 'credit']).'" class="'.$linkClass.'">'.__('หน้าขอชั่วโมงกิจกรรม แท็บ "เทียบโอนตำแหน่ง"').'</a>',
            ]),
        ],
        [
            'q' => __('ต้องทำกิจกรรมกี่ชั่วโมงถึงจะผ่านเกณฑ์?'),
            'a' => __('เกณฑ์ชั่วโมงกำหนดตามชั้นปีการศึกษา ดูเป้าหมายและความคืบหน้าสะสมของตัวเองได้ที่:href_dashboard', [
                'href_dashboard' => '<a href="'.route('dashboard').'" class="'.$linkClass.'">'.__('หน้าแดชบอร์ด').'</a>',
            ]),
        ],
    ];
@endphp

<div class="rounded-2xl glass-card p-5 shadow-soft" x-data="{ openFaq: null }">
    <h2 class="mb-1 text-sm font-bold text-slate-900 dark:text-slate-100">{{ __('คำถามที่พบบ่อย') }}</h2>
    <div class="divide-y divide-slate-100 dark:divide-slate-800">
        @foreach ($faqs as $i => $faq)
            <div class="py-2.5">
                <button type="button" @click="openFaq = openFaq === {{ $i }} ? null : {{ $i }}"
                    class="flex w-full items-center justify-between gap-3 py-1 text-left text-sm font-medium text-slate-700 dark:text-slate-200">
                    <span>{{ $faq['q'] }}</span>
                    <svg class="h-4 w-4 shrink-0 text-slate-400 transition-transform duration-200" :class="openFaq === {{ $i }} && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                </button>
                <div x-show="openFaq === {{ $i }}" x-cloak
                    x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                    class="pt-1.5 text-sm leading-relaxed text-slate-500 dark:text-slate-400">
                    {!! $faq['a'] !!}
                </div>
            </div>
        @endforeach
    </div>
</div>
