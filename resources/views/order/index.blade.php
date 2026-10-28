@extends('layouts.app')

@section('content')

@if (config('jimeb.simple_mode'))
    {{-- Jimbe : « En direct » pour le service, « Historique » pour retrouver une commande --}}
    @php $jbTab = in_array(request('tab'), ['history', 'advanced'], true) ? request('tab') : 'live'; @endphp
    <div class="p-4">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <div class="inline-flex rounded-xl bg-gray-100 p-1 dark:bg-gray-800">
                <a href="{{ route('orders.index') }}" class="rounded-lg px-4 py-2 text-sm font-semibold {{ $jbTab === 'live' ? 'bg-white text-skin-base shadow dark:bg-gray-700' : 'text-gray-500' }}">@lang('modules.order.boardTitle')</a>
                <a href="{{ route('orders.index', ['tab' => 'history']) }}" class="rounded-lg px-4 py-2 text-sm font-semibold {{ $jbTab === 'history' ? 'bg-white text-skin-base shadow dark:bg-gray-700' : 'text-gray-500' }}">@lang('modules.order.historyTitle')</a>
            </div>
            @if ($jbTab !== 'advanced')
                <a href="{{ route('orders.index', ['tab' => 'advanced']) }}" class="text-xs text-gray-400 underline">@lang('modules.order.advancedView')</a>
            @endif
        </div>

        @if ($jbTab === 'live')
            @livewire('dashboard.today-order-list')
        @elseif ($jbTab === 'history')
            @livewire('order.order-history')
        @endif
    </div>

    @if ($jbTab === 'advanced')
        @livewire('order.Orders')
    @endif
@else
    @livewire('order.Orders')
@endif

@endsection
