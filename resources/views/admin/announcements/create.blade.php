@extends('layouts.dashboard')

@section('content')
<div class="mx-auto max-w-2xl">
    <div class="mb-6 rounded-3xl brand-gradient p-6 shadow-soft-lg sm:p-8">
        <p class="text-xs font-medium uppercase tracking-[0.2em] text-violet-200/70">{{ __('กองพัฒนานักศึกษา') }}</p>
        <h1 class="mt-1 text-xl font-bold text-white sm:text-2xl">{{ __('ส่งประกาศถึงนักศึกษา') }}</h1>
        <p class="mt-1.5 text-sm font-light text-violet-100/80">{{ __('ข้อความจะไปแสดงในศูนย์การแจ้งเตือนและ push notification ของนักศึกษาที่ตรงเงื่อนไข') }}</p>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-2xl bg-red-50 px-4 py-3 text-sm text-red-700 shadow-soft ring-1 ring-red-100 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/20">
            <ul class="list-inside list-disc space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.announcements.store') }}" class="space-y-4 rounded-2xl glass-card p-5 shadow-soft" onsubmit="return confirm('{{ __('ยืนยันส่งประกาศนี้?') }}')">
        @csrf

        <div>
            <label class="mb-1.5 block text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('หัวข้อ') }}</label>
            <input type="text" name="subject" value="{{ old('subject') }}" required maxlength="255"
                placeholder="{{ __('เช่น ปิดปรับปรุงระบบชั่วคราว') }}"
                class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-soft transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
        </div>

        <div>
            <label class="mb-1.5 block text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('เนื้อหา') }}</label>
            <textarea name="body" rows="5" required maxlength="2000"
                placeholder="{{ __('รายละเอียดประกาศ') }}"
                class="w-full resize-none rounded-2xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-soft transition-all duration-200 focus:border-brand-purple-500 focus:outline-none focus:ring-4 focus:ring-brand-purple-500/10 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">{{ old('body') }}</textarea>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('คณะ (เว้นว่าง = ทุกคณะ)') }}</label>
                @php $facultyOptions = $faculties->pluck('name_th', 'id')->all(); @endphp
                <x-premium-select name="faculty_id" :options="$facultyOptions" :selected="old('faculty_id')" placeholder="{{ __('-- ทุกคณะ --') }}" />
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('ชั้นปี (เว้นว่าง = ทุกชั้นปี)') }}</label>
                @php $yearOptions = collect([1, 2, 3, 4])->mapWithKeys(fn ($y) => [$y => __('ชั้นปีที่ :year', ['year' => $y])])->all(); @endphp
                <x-premium-select name="year_level" :options="$yearOptions" :selected="old('year_level')" placeholder="{{ __('-- ทุกชั้นปี --') }}" />
            </div>
        </div>

        <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-brand-purple-600 to-brand-purple-500 px-4 py-2.5 text-sm font-semibold text-white shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg">
            {{ __('ส่งประกาศ') }}
        </button>
    </form>
</div>
@endsection
