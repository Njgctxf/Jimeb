@extends('layouts.public')

@push('styles')
<style>
    #main-content { overflow: hidden; height: 100%; }
    #main-content main { height: 100%; }
</style>
@endpush

@section('content')
    @livewire('customer-display')
@endsection

@if(pusherSettings()->is_enabled_pusher_broadcast)
@push('scripts')
<script>
    (function () {
        const userId = @json(auth()->id());
        const channelName = 'customer-display-user-' + userId;

        function refreshCustomerDisplay(data) {
            if (!window.Livewire || typeof window.Livewire.getByName !== 'function') {
                return;
            }

            const payload = data && data.order_data ? data.order_data : null;
            window.Livewire.getByName('customer-display').forEach(function (wire) {
                if (wire && typeof wire.call === 'function') {
                    wire.call('refreshCustomerDisplay', payload);
                }
            });
        }

        function bindCustomerDisplayChannel() {
            if (window.__customerDisplayPusherBound || !window.PUSHER) {
                return;
            }

            window.__customerDisplayPusherBound = true;

            try {
                const channel = window.PUSHER.subscribe(channelName);
                channel.bind('customer-display.updated', function (data) {
                    refreshCustomerDisplay(data);
                });
            } catch (error) {
                window.__customerDisplayPusherBound = false;
                console.error('❌ Customer display Pusher channel bind failed:', error);
            }
        }

        bindCustomerDisplayChannel();
        document.addEventListener('livewire:init', bindCustomerDisplayChannel);
    })();
</script>
@endpush
@endif 