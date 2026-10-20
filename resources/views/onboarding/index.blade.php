@extends('layouts.app')

@section('content')

@if (config('jimeb.simple_mode'))
    @livewire('onboarding.setup-wizard')
@else
    @livewire('onboarding.onboardingSteps')
@endif

@endsection
