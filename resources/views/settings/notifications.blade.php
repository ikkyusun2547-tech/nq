@extends('layouts.dashboard')

@section('content')
@php
    $on = fn (string $category, string $channel) => (bool) data_get($prefs, "$category.$channel", true);
    // Shared switch: a hidden 0 first so an unticked box still submits "off".
    $switch = 'relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full bg-slate-200 transition-colors after:absolute after:left-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow after:transition-transform peer-checked:bg-brand-purple-600 peer-checked:after:translate-x-5 peer-focus-visible:ring-4 peer-focus-visible:ring-brand-purple-500/20 dark:bg-slate-700';
@endphp

<div class="mx-auto max-w-2xl">
    <x-brand-header eyebrow="{{ __('ตั้งค่า') }}" :title="__('การแจ้งเตือน')" />

    {{-- This device: pop-up permission is per browser, not per account. --}}
    <section class="rounded-3xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900 sm:p-6"
        x-data="{
            status: null,
            busy: false,
            refresh() { this.status = window.webPushStatus?.() ?? 'unsupported'; },
            async enable() {
                if (typeof window.enableWebPush !== 'function') return;
                this.busy = true;
                try { await window.enableWebPush(); } catch (e) {}
                this.refresh();
                this.busy = false;
            },
        }"
        x-init="setTimeout(() => refresh(), 0)">
        <div class="flex items-start gap-4">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-brand-purple-50 text-brand-purple-700 dark:bg-brand-purple-500/15 dark:text-brand-purple-300">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3"/></svg>
            </span>
            <div class="min-w-0 flex-1">
                <h2 class="font-display text-lg text-slate-900 dark:text-white">{{ __('แจ้งเตือนเด้งบนเครื่องนี้') }}</h2>
                <p class="mt-0.5 text-sm text-slate-600 dark:text-slate-400">{{ __('ต้องอนุญาตแยกในแต่ละเครื่องและเบราว์เซอร์') }}</p>

                <p x-show="status === 'granted'" class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-brand-green-50 px-3 py-1 text-sm font-semibold text-brand-green-700 dark:bg-brand-green-500/10 dark:text-brand-green-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                    {{ __('เปิดอยู่') }}
                </p>
                <button type="button" x-show="status === 'default'" @click="enable()" :disabled="busy"
                    class="mt-3 inline-flex h-10 items-center rounded-full bg-brand-purple-700 px-5 text-sm font-semibold text-white transition-colors hover:bg-brand-purple-800 disabled:opacity-60">
                    {{ __('เปิดการแจ้งเตือนบนเครื่องนี้') }}
                </button>
                <p x-show="status === 'denied'" class="mt-3 rounded-2xl bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:bg-amber-500/10 dark:text-amber-200">
                    {{ __('ถูกปิดไว้ในเบราว์เซอร์นี้ เปิดได้ที่ไอคอนแม่กุญแจข้างที่อยู่เว็บ → การแจ้งเตือน → อนุญาต แล้วรีเฟรชหน้า') }}
                </p>
                <p x-show="status === 'unsupported' || status === 'unconfigured'" class="mt-3 text-sm text-slate-500 dark:text-slate-400">
                    {{ __('เบราว์เซอร์นี้ไม่รองรับการแจ้งเตือนเด้ง บน iPhone ต้องติดตั้งแอปลงหน้าจอโฮมก่อน') }}
                    <a href="{{ route('install-guide') }}" class="font-semibold text-brand-purple-700 hover:underline dark:text-brand-purple-300">{{ __('ดูวิธีติดตั้ง') }}</a>
                </p>
            </div>
        </div>
    </section>

    <form method="POST" action="{{ route('notification-settings.update') }}" class="mt-4 space-y-4">
        @csrf
        @method('PUT')

        {{-- Per category --}}
        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-end justify-between gap-4 border-b border-slate-100 px-5 py-4 dark:border-slate-800 sm:px-6">
                <div>
                    <h2 class="font-display text-lg text-slate-900 dark:text-white">{{ __('เลือกเรื่องที่อยากรับ') }}</h2>
                    <p class="mt-0.5 text-sm text-slate-600 dark:text-slate-400">{{ __('ทุกเรื่องยังเข้ากระดิ่งเสมอ ส่วนนี้เลือกว่าจะเด้งหรือส่งอีเมลด้วยไหม') }}</p>
                </div>
                <div class="hidden shrink-0 gap-6 text-xs font-semibold text-slate-500 dark:text-slate-400 sm:flex">
                    <span class="w-11 text-center">{{ __('เด้ง') }}</span>
                    <span class="w-11 text-center">{{ __('อีเมล') }}</span>
                </div>
            </div>

            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @foreach ($categories as $key => [$label, $description, $emails])
                    <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                        <div class="min-w-0">
                            <p class="font-semibold text-slate-900 dark:text-white">{{ $label }}</p>
                            <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">{{ $description }}</p>
                        </div>
                        <div class="flex shrink-0 gap-6">
                            <label class="flex items-center gap-2 sm:block">
                                <input type="hidden" name="{{ $key }}[push]" value="0">
                                <input type="checkbox" name="{{ $key }}[push]" value="1" class="peer sr-only" @checked($on($key, 'push'))>
                                <span class="{{ $switch }}" aria-hidden="true"></span>
                                <span class="text-xs text-slate-500 dark:text-slate-400 sm:sr-only">{{ __('เด้ง') }}</span>
                            </label>
                            @if ($emails)
                                <label class="flex items-center gap-2 sm:block">
                                    <input type="hidden" name="{{ $key }}[email]" value="0">
                                    <input type="checkbox" name="{{ $key }}[email]" value="1" class="peer sr-only" @checked($on($key, 'email'))>
                                    <span class="{{ $switch }}" aria-hidden="true"></span>
                                    <span class="text-xs text-slate-500 dark:text-slate-400 sm:sr-only">{{ __('อีเมล') }}</span>
                                </label>
                            @else
                                <span class="hidden w-11 text-center text-xs text-slate-300 dark:text-slate-600 sm:block" title="{{ __('เรื่องนี้ไม่ส่งอีเมล') }}">—</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Quiet hours --}}
        <section class="rounded-3xl border border-slate-200 bg-white px-5 py-4 dark:border-slate-800 dark:bg-slate-900 sm:px-6">
            <label class="flex cursor-pointer items-center justify-between gap-4">
                <span class="min-w-0">
                    <span class="block font-semibold text-slate-900 dark:text-white">{{ __('ช่วงเวลาเงียบ :from–:until น.', ['from' => '22:00', 'until' => '07:00']) }}</span>
                    <span class="mt-0.5 block text-sm text-slate-500 dark:text-slate-400">{{ __('ไม่เด้งตอนกลางคืน เรื่องที่เกิดช่วงนั้นยังเข้ากระดิ่งตามปกติ') }}</span>
                </span>
                <input type="hidden" name="quiet_hours" value="0">
                <input type="checkbox" name="quiet_hours" value="1" class="peer sr-only" @checked(data_get($prefs, 'quiet_hours', false))>
                <span class="{{ $switch }}" aria-hidden="true"></span>
            </label>
        </section>

        <div class="flex justify-end">
            <button type="submit" class="h-12 rounded-full bg-brand-purple-700 px-8 text-sm font-semibold text-white transition-colors hover:bg-brand-purple-800">
                {{ __('บันทึกการตั้งค่า') }}
            </button>
        </div>
    </form>
</div>
@endsection
