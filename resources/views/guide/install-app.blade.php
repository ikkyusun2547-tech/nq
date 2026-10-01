@extends('layouts.dashboard')

@section('content')
<div class="mx-auto max-w-3xl">
    <x-brand-header eyebrow="{{ __('คู่มือนักศึกษา') }}" :title="__('วิธีติดตั้งแอป')" />

    @include('guide.partials.install-app')
</div>
@endsection
