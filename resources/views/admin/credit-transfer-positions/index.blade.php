@extends('layouts.dashboard')

@section('content')
<div class="mx-auto max-w-5xl">
    <x-brand-header :title="__('ตำแหน่งเทียบโอนชั่วโมง')" :eyebrow="__('กองพัฒนานักศึกษา')" :subtitle="__('จำนวนชั่วโมงมาตรฐานต่อตำแหน่งที่ใช้ในระบบเทียบโอนตำแหน่ง')">
        <x-slot:actions>
            <span class="rounded-full bg-slate-100 px-3.5 py-1.5 text-sm font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                {{ __(':count ตำแหน่ง', ['count' => $positions->count()]) }}
            </span>
            <a href="{{ route('admin.credit-transfer-positions.create') }}"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-brand-purple-300 hover:text-brand-purple-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-brand-purple-500/40 dark:hover:text-brand-purple-300">
                {{ __('เพิ่มตำแหน่งใหม่') }}
            </a>
        </x-slot:actions>
    </x-brand-header>

    <div class="overflow-x-auto rounded-3xl glass-card">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 dark:border-slate-800">
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('รหัส') }}</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('ชื่อตำแหน่ง') }}</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('ชั่วโมงมาตรฐาน') }}</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('ลำดับแสดงผล') }}</th>
                    <th class="whitespace-nowrap px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($positions as $position)
                    <tr @class([
                        'border-b border-slate-100 dark:border-slate-800 transition-colors last:border-0 hover:bg-slate-50 dark:hover:bg-slate-800/60',
                    ])>
                        <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-slate-500 dark:text-slate-400">{{ $position->key }}</td>
                        <td class="min-w-[14rem] max-w-xs whitespace-normal break-words px-4 py-3 font-medium text-slate-900 dark:text-slate-100">{{ $position->label }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-500 dark:text-slate-400">{{ $position->hours }} {{ __('ชม.') }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-500 dark:text-slate-400">{{ $position->sort_order }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right space-x-3">
                            <a href="{{ route('admin.credit-transfer-positions.edit', $position) }}" class="font-medium text-brand-purple-600 transition-colors hover:text-brand-purple-800 dark:text-brand-purple-400 dark:hover:text-brand-purple-300">{{ __('จัดการ') }}</a>
                            <form method="POST" action="{{ route('admin.credit-transfer-positions.destroy', $position) }}" class="inline">
                                @csrf
                                @method('DELETE')
                                <x-confirm-submit tone="red" :message="__('ยืนยันลบตำแหน่ง \':label\'?', ['label' => $position->label])" :label="__('ลบ')"
                                    class="font-medium text-red-500 transition-colors hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">{{ __('ลบ') }}</x-confirm-submit>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-400 dark:text-slate-500">{{ __('ยังไม่มีตำแหน่งในระบบ') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
