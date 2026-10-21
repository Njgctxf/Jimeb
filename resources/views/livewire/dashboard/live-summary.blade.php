{{-- Résumé du service : un coup d'œil + son, le traitement se fait dans « Commandes » --}}
@php
    $jbTiles = [
        'new' => ['🔔', __('modules.order.boardNew'), 'bg-amber-50 text-amber-800 dark:bg-amber-900/30 dark:text-amber-200'],
        'kitchen' => ['👨‍🍳', __('modules.order.boardKitchen'), 'bg-sky-50 text-sky-800 dark:bg-sky-900/30 dark:text-sky-200'],
        'ready' => ['✅', __('modules.order.boardReady'), 'bg-emerald-50 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200'],
        'bill' => ['🧾', __('modules.order.boardBill'), 'bg-violet-50 text-violet-800 dark:bg-violet-900/30 dark:text-violet-200'],
    ];
    $jbAlertIds = collect($counts['new'])->map(fn ($id) => 'o' . $id)->merge($calls->map(fn ($id) => 'c' . $id))->join(',');
    $jbBusy = collect($counts)->flatten()->count() + $calls->count() > 0;
@endphp
<a href="{{ route('orders.index') }}" wire:poll.5s data-alerts="{{ $jbAlertIds }}"
    x-data="{
        known: null,
        baseTitle: null,
        ids() { return (this.$root.dataset.alerts || '').split(',').filter(Boolean); },
        beep() {
            try {
                if (localStorage.getItem('jbBoardSound') === '0') return;
                const c = new (window.AudioContext || window.webkitAudioContext)();
                [880, 1175, 1480].forEach((f, i) => { const o = c.createOscillator(), g = c.createGain(); o.frequency.value = f; o.connect(g); g.connect(c.destination); g.gain.setValueAtTime(.25, c.currentTime + i * .18); g.gain.exponentialRampToValueAtTime(.001, c.currentTime + i * .18 + .17); o.start(c.currentTime + i * .18); o.stop(c.currentTime + i * .18 + .17); });
            } catch (e) {}
        },
        check() {
            const ids = this.ids();
            if (this.known !== null && ids.some(id => !this.known.includes(id))) this.beep();
            this.known = ids;
            this.baseTitle ??= document.title.replace(/^\(\d+\)\s*/, '');
            document.title = (ids.length ? '(' + ids.length + ') ' : '') + this.baseTitle;
        },
        init() {
            this.check();
            Livewire.hook('morphed', () => this.$nextTick(() => this.check()));
            setInterval(() => { if (this.ids().length) this.beep(); }, 30000);
        },
    }"
    class="block rounded-xl border border-gray-100 bg-white p-4 transition hover:shadow-md dark:border-gray-700 dark:bg-gray-800">
    <div class="mb-3 flex items-center justify-between">
        <p class="flex items-center gap-2 text-base font-semibold text-gray-800 dark:text-white">
            <span class="relative flex h-2.5 w-2.5"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span></span>
            @lang('modules.order.summaryTitle')
        </p>
        <span class="text-sm font-semibold text-skin-base">@lang('modules.order.summaryOpen') →</span>
    </div>
    @if ($jbBusy)
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-5">
            @foreach ($jbTiles as $key => [$icon, $label, $tone])
                <div class="rounded-xl px-3 py-2.5 {{ $tone }}">
                    <p class="text-2xl font-extrabold">{{ count($counts[$key]) }}</p>
                    <p class="text-xs font-medium">{{ $icon }} {{ $label }}</p>
                </div>
            @endforeach
            <div class="rounded-xl px-3 py-2.5 {{ $calls->count() ? 'animate-pulse bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-200' : 'bg-gray-50 text-gray-500 dark:bg-gray-900/40' }}">
                <p class="text-2xl font-extrabold">{{ $calls->count() }}</p>
                <p class="text-xs font-medium">🙋 @lang('modules.order.summaryCalls')</p>
            </div>
        </div>
    @else
        <p class="text-sm text-gray-400">@lang('modules.order.summaryQuiet')</p>
    @endif
</a>
