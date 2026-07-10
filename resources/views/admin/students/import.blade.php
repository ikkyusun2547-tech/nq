@extends('layouts.dashboard')

@section('content')
<div class="mx-auto max-w-3xl">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-3xl brand-gradient p-6 shadow-soft-lg sm:p-8">
        <div>
            <p class="text-xs font-medium uppercase tracking-[0.2em] text-violet-200/70">{{ __('กองพัฒนานักศึกษา') }}</p>
            <h1 class="mt-1 text-xl font-bold text-white sm:text-2xl">{{ __('นำเข้ารายชื่อนักศึกษา') }}</h1>
        </div>
        <a href="{{ route('admin.students.index') }}"
            class="inline-flex items-center gap-1.5 rounded-xl bg-white/10 px-4 py-2 text-sm font-medium text-white shadow-soft ring-1 ring-white/15 backdrop-blur transition-all duration-300 hover:-translate-y-0.5 hover:bg-white/15">
            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            {{ __('กลับรายชื่อนักศึกษา') }}
        </a>
    </div>

    <div class="mb-6 rounded-2xl glass-card p-5 shadow-soft">
        <p class="text-sm text-slate-600 dark:text-slate-300">
            {{ __('อัปโหลดไฟล์ Excel/CSV ตามเทมเพลตด้านล่าง ระบบจะสร้างบัญชีนักศึกษาให้อัตโนมัติ (จับคู่ด้วยรหัสนักศึกษาหรืออีเมล) เมื่อนักศึกษาล็อกอินด้วย Google ครั้งแรกด้วยอีเมลที่ตรงกัน จะข้ามขั้นตอนกรอกโปรไฟล์ไปเข้าแดชบอร์ดได้ทันที') }}
        </p>
        <a href="{{ route('admin.students.import.template') }}"
            class="mt-3 inline-flex items-center gap-2 rounded-xl bg-brand-purple-50 px-4 py-2 text-sm font-semibold text-brand-purple-700 shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg dark:bg-brand-purple-500/10 dark:text-brand-purple-400">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
            {{ __('ดาวน์โหลดเทมเพลต') }}
        </a>
        <p class="mt-3 text-xs text-slate-400 dark:text-slate-500">
            {{ __('คอลัมน์ "คณะ" และ "สาขา" ต้องสะกดตรงกับที่มีอยู่ในระบบทุกตัวอักษร (ดูรายชื่อที่ถูกต้องได้จากตัวกรองคณะ/สาขาในหน้ารายชื่อนักศึกษา) — ไม่ต้องใส่คำย่อวุฒิการศึกษาต่อท้ายชื่อสาขา') }}
        </p>
    </div>

    @if (session('import_result'))
        @php $result = session('import_result'); @endphp
        <div class="mb-6 rounded-2xl glass-card p-5 shadow-soft">
            <h2 class="mb-3 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('ผลการนำเข้า') }}</h2>
            <div class="flex flex-wrap gap-3">
                <span class="rounded-xl bg-brand-green-50 px-4 py-2 text-sm font-medium text-brand-green-700 dark:bg-brand-green-500/10 dark:text-brand-green-400">
                    {{ __('สร้างใหม่ :count คน', ['count' => $result['created']]) }}
                </span>
                <span class="rounded-xl bg-sky-50 px-4 py-2 text-sm font-medium text-sky-700 dark:bg-sky-500/10 dark:text-sky-400">
                    {{ __('อัปเดตข้อมูลเดิม :count คน', ['count' => $result['updated']]) }}
                </span>
                <span class="rounded-xl bg-red-50 px-4 py-2 text-sm font-medium text-red-700 dark:bg-red-500/10 dark:text-red-400">
                    {{ __('ผิดพลาด :count แถว', ['count' => count($result['errors'])]) }}
                </span>
            </div>

            @if (count($result['errors']) > 0)
                <div class="mt-4 max-h-72 overflow-y-auto rounded-xl bg-red-50/60 p-3 dark:bg-red-500/5">
                    <ul class="space-y-1.5 text-xs text-red-700 dark:text-red-400">
                        @foreach ($result['errors'] as $error)
                            <li>{{ __('แถวที่ :row:', ['row' => $error['row']]) }} {{ $error['message'] }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700 shadow-soft ring-1 ring-red-100 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/20">
            <ul class="list-inside list-disc space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-2xl glass-card p-5 shadow-soft">
        <form method="POST" action="{{ route('admin.students.import.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div
                x-data="{ fileName: '' }"
                class="relative flex flex-col items-center justify-center gap-2 overflow-hidden rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50/60 p-8 text-center transition-colors duration-200 dark:border-slate-600 dark:bg-slate-800/40 @error('roster_file') border-red-400 dark:border-red-500/70 @enderror"
            >
                <svg class="h-8 w-8 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l-3.75 3.75M12 9.75l3.75 3.75M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z"/></svg>
                <p class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('คลิกเพื่อเลือกไฟล์ Excel (.xlsx) หรือ CSV') }}</p>
                <p class="max-w-full truncate text-xs font-medium text-slate-600 dark:text-slate-300" x-show="fileName" x-text="fileName"></p>
                <input
                    type="file" name="roster_file" accept=".xlsx,.xls,.csv" required
                    class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                    @change="fileName = $event.target.files[0]?.name ?? ''"
                >
            </div>
            <button type="submit" class="w-full rounded-xl bg-brand-green-500 px-4 py-3 text-sm font-semibold text-brand-purple-950 shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:bg-brand-green-400 hover:shadow-lg">
                {{ __('นำเข้ารายชื่อ') }}
            </button>
        </form>
    </div>
</div>
@endsection
