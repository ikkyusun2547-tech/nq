@extends('layouts.dashboard')

@section('content')
<div class="mx-auto max-w-6xl">
    <x-brand-header eyebrow="{{ __('กองพัฒนานักศึกษา') }}" :title="__('ปฏิทินกิจกรรม')">
        <x-slot:actions>
            @include('partials.activity-view-toggle', ['listRoute' => 'activities.index', 'calendarRoute' => 'activities.calendar', 'active' => 'calendar'])
        </x-slot:actions>
    </x-brand-header>

    <x-activity-calendar
        :month="$month"
        :weeks="$weeks"
        route="activities.calendar"
        :link-to="fn ($activity) => route('activities.show', $activity)"
        :checked-in-ids="$checkedInActivityIds"
    />
</div>
@endsection
