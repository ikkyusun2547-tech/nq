@extends('layouts.dashboard')

@section('content')
<div class="mx-auto max-w-[90rem]">
    <x-brand-header :title="__('ปฏิทินกิจกรรม')" :eyebrow="__('กองพัฒนานักศึกษา')">
        <x-slot:actions>
            <div class="flex flex-wrap items-center gap-2">
                @include('partials.activity-view-toggle', ['listRoute' => 'admin.activities.index', 'calendarRoute' => 'admin.activities.calendar', 'active' => 'calendar'])
                <a href="{{ route('admin.activities.create') }}"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-brand-green-500 px-4 py-2.5 text-sm font-semibold text-brand-purple-950 shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:bg-brand-green-400 hover:shadow-lg">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    {{ __('สร้างกิจกรรม') }}
                </a>
            </div>
        </x-slot:actions>
    </x-brand-header>

    <x-activity-calendar
        :month="$month"
        :weeks="$weeks"
        route="admin.activities.calendar"
        :link-to="fn ($activity) => route('admin.activities.edit', $activity)"
        :show-status="true"
    />
</div>
@endsection
