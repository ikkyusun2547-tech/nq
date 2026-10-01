@extends('layouts.dashboard')

@section('content')
<div class="mx-auto max-w-4xl">
    <x-brand-header :title="__('คำถามแบบประเมินกิจกรรม')" :eyebrow="__('กองพัฒนานักศึกษา')" :subtitle="__('ใช้กับแบบประเมินความพึงพอใจหลังกิจกรรมทุกกิจกรรม (มาตราส่วน 1–5)')" />

    <p class="mb-4 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:bg-amber-500/10 dark:text-amber-300">
        {{ __('การแก้ไขข้อความของคำถามที่มีผู้ตอบแล้วจะเปลี่ยนวิธีอ่านผลเก่าไปด้วย ควรแก้เฉพาะถ้อยคำโดยคงความหมายเดิม') }}
    </p>

    <div class="space-y-3">
        @foreach ($questions as $question)
            <div @class([
                'rounded-2xl glass-card p-4 shadow-soft',
                'opacity-60' => ! $question->is_active,
            ])>
                <form method="POST" action="{{ route('admin.survey-questions.update', $question) }}" class="flex flex-wrap items-end gap-3">
                    @csrf
                    @method('PUT')
                    <div class="w-20">
                        <label class="mb-1 block text-xs text-slate-500 dark:text-slate-400">{{ __('ลำดับ') }}</label>
                        <input type="number" name="sort_order" value="{{ $question->sort_order }}" min="0" max="999" required
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                    </div>
                    <div class="min-w-[14rem] flex-1">
                        <label class="mb-1 block text-xs text-slate-500 dark:text-slate-400">
                            {{ __('คำถาม') }}
                            @if ($question->answers_count)
                                <span class="text-slate-400">· {{ __('ตอบแล้ว :count ครั้ง', ['count' => $question->answers_count]) }}</span>
                            @endif
                        </label>
                        <input type="text" name="text" value="{{ $question->text }}" maxlength="255" required
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                    </div>
                    <label class="flex items-center gap-2 pb-2 text-sm text-slate-600 dark:text-slate-300">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" @checked($question->is_active) class="rounded border-slate-300 text-brand-purple-600">
                        {{ __('เปิดใช้งาน') }}
                    </label>
                    <button type="submit" class="rounded-xl bg-brand-purple-600 px-4 py-2 text-sm font-medium text-white shadow-soft hover:bg-brand-purple-700">{{ __('บันทึก') }}</button>
                </form>
            </div>
        @endforeach
    </div>
</div>
@endsection
