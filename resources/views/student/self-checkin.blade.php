@extends('layouts.dashboard')

@section('content')
<div class="mx-auto max-w-md">
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-lg font-semibold text-gray-900 dark:text-slate-100">{{ __('เช็กชื่อแบบรายงานตนเอง') }}</h1>
        <a href="{{ route('activities.index') }}" class="text-sm text-gray-400 hover:text-gray-600 dark:text-slate-500 dark:hover:text-slate-300">&larr; {{ __('กลับ') }}</a>
    </div>

    <div class="rounded-3xl glass-card p-5 shadow-soft-lg sm:p-6">
        <h2 class="font-semibold text-slate-900 dark:text-slate-100">{{ $activity->title }}</h2>
        @if ($activity->location_name)
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $activity->location_name }}</p>
        @endif

        @if ($activity->checkin_opens_at && $activity->checkin_closes_at)
            <p class="mt-2 text-xs text-slate-400 dark:text-slate-500">
                {{ __('เปิดให้เช็กชื่อ :from – :to', [
                    'from' => $activity->checkin_opens_at->translatedFormat('d M Y H:i'),
                    'to' => $activity->checkin_closes_at->translatedFormat('d M Y H:i'),
                ]) }}
            </p>
        @endif

        @if ($errors->any())
            <div class="mt-4 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700 shadow-soft ring-1 ring-red-100 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/20">
                <ul class="list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (! $activity->acceptsCheckIn())
            <p class="mt-4 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-700 shadow-soft ring-1 ring-amber-100 dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-500/20">
                {{ __('ยังไม่อยู่ในช่วงเวลาที่เปิดให้เช็กชื่อกิจกรรมนี้') }}
            </p>
        @else
            <form method="POST" action="{{ route('self-checkin.store', $activity) }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-600 dark:text-slate-400">{{ __('รูปหลักฐานการเข้าร่วมกิจกรรม (ไม่เกิน 2MB)') }}</label>
                    <div
                        x-data="{ fileName: '', previewUrl: null }"
                        class="relative flex flex-col items-center justify-center gap-2 overflow-hidden rounded-2xl border-2 border-dashed bg-slate-50/60 p-5 text-center transition-colors duration-200 dark:bg-slate-800/40 @error('photo') border-red-400 dark:border-red-500/70 @else border-slate-200 dark:border-slate-600 @enderror"
                        :class="previewUrl && 'border-brand-green-300 bg-brand-green-50/40 dark:border-brand-green-500/40 dark:bg-brand-green-500/5'"
                    >
                        <template x-if="! previewUrl">
                            <div class="flex flex-col items-center gap-1.5 text-slate-400 dark:text-slate-500">
                                <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l-3.75 3.75M12 9.75l3.75 3.75M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z"/></svg>
                                <p class="text-xs font-medium">{{ __('คลิกเพื่อเลือกรูปภาพ (ถ่ายใหม่หรือเลือกจากคลังภาพ)') }}</p>
                                <p class="text-[0.68rem] text-slate-350 dark:text-slate-600">PNG, JPG {{ __('ไม่เกิน') }} 2MB</p>
                            </div>
                        </template>
                        <template x-if="previewUrl">
                            <img :src="previewUrl" class="max-h-44 rounded-xl object-contain shadow-soft">
                        </template>
                        <p class="max-w-full truncate text-xs font-medium text-slate-600 dark:text-slate-300" x-show="fileName" x-text="fileName"></p>

                        <input
                            type="file" name="photo" accept="image/*" required
                            class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                            @change="
                                const file = $event.target.files[0];
                                fileName = file ? file.name : '';
                                previewUrl = file ? URL.createObjectURL(file) : null;
                            "
                        >
                    </div>
                </div>

                <button type="submit" class="w-full rounded-xl bg-brand-green-500 px-4 py-3 text-sm font-semibold text-brand-purple-950 shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:bg-brand-green-400 hover:shadow-lg">
                    {{ __('ส่งหลักฐานเพื่อเช็กชื่อ') }}
                </button>
                <p class="text-center text-xs text-slate-400 dark:text-slate-500">{{ __('การเช็กชื่อแบบนี้ต้องรอเจ้าหน้าที่ตรวจสอบก่อนจึงจะได้รับชั่วโมงกิจกรรม') }}</p>
            </form>
        @endif
    </div>
</div>
@endsection
