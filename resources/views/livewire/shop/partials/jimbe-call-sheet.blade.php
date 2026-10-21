{{-- Appeler un serveur : fenêtre du bas avec motif, envoi, confirmation animée --}}
@php
    $jbCallAreas = empty($table?->id)
        ? \App\Models\Area::where('branch_id', $shopBranch->id)
            ->with(['tables' => fn ($q) => $q->where('status', 'active')])->get()
            ->filter(fn ($a) => $a->tables->count() > 0)
        : collect();
    $jbCallTable = !empty($table?->table_code) ? $table->table_code : null;
@endphp

<div class="jb-call" x-cloak
    x-data="{
        open: false, reason: 'help', busy: false, state: 'form', at: '', tableHash: '', err: '',
        reasons: [
            { k: 'help', e: '🙋', t: @js(__('modules.waiterRequest.reason_help')) },
            { k: 'bill', e: '🧾', t: @js(__('modules.waiterRequest.reason_bill')) },
            { k: 'water', e: '💧', t: @js(__('modules.waiterRequest.reason_water')) },
            { k: 'other', e: '✨', t: @js(__('modules.waiterRequest.reason_other')) },
        ],
        hasTable: @js(!empty($table?->id)),
        show() { this.state = 'form'; this.err = ''; this.open = true; },
        close() { this.open = false; },
        async send() {
            if (!this.hasTable && !this.tableHash) { this.err = @js(__('modules.shop.tableRequired')); return; }
            this.busy = true; this.err = '';
            try {
                const wire = Livewire.find(lwId);
                const r = await wire.call('callWaiterFromShop', this.reason, this.hasTable ? null : this.tableHash);
                this.at = (r && r.at) || '';
                this.state = (r && r.ok) ? 'sent' : ((r && r.code === 'pending') ? 'wait' : 'error');
                if (this.state === 'error') { this.err = (r && r.message) || @js(__('modules.waiterRequest.callFailed')); }
            } catch (e) { this.state = 'error'; this.err = @js(__('modules.waiterRequest.callFailed')); }
            this.busy = false;
        },
    }"
    x-on:jb-call-open.window="show()"
    @keydown.escape.window="close()">

    <div class="jb-call__veil" x-show="open" x-transition.opacity @click="close()"></div>

    <div class="jb-call__sheet" x-show="open" role="dialog" aria-modal="true"
        x-transition:enter="jb-call-enter" x-transition:enter-start="jb-call-enter-start" x-transition:enter-end="jb-call-enter-end"
        x-transition:leave="jb-call-leave" x-transition:leave-start="jb-call-leave-start" x-transition:leave-end="jb-call-leave-end">
        <div class="jb-call__grip"></div>

        {{-- Formulaire --}}
        <div x-show="state === 'form'">
            <div class="jb-call__bell">🔔</div>
            <h3 class="jb-call__title">@lang('modules.waiterRequest.callTitle')</h3>
            <p class="jb-call__sub">
                @if ($jbCallTable) <b>{{ $jbCallTable }}</b> · @endif
                @lang('modules.waiterRequest.callSub')
            </p>

            @unless ($jbCallTable)
                <select class="jb-call__select" x-model="tableHash">
                    <option value="">@lang('modules.shop.chooseTable')</option>
                    @foreach ($jbCallAreas as $area)
                        <optgroup label="{{ $area->area_name }}">
                            @foreach ($area->tables as $t)
                                <option value="{{ $t->hash }}">{{ $t->table_code }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            @endunless

            <div class="jb-call__reasons">
                <template x-for="r in reasons" :key="r.k">
                    <button type="button" class="jb-call__reason" :class="reason === r.k ? 'is-on' : ''" @click="reason = r.k">
                        <span x-text="r.e"></span><b x-text="r.t"></b>
                    </button>
                </template>
            </div>

            <p class="jb-call__err" x-show="err" x-text="err"></p>

            <button type="button" class="jb-call__cta" :disabled="busy" @click="send()">
                <span x-show="!busy">@lang('modules.waiterRequest.callNow')</span>
                <span x-show="busy">@lang('modules.shop.sending')…</span>
            </button>
            <button type="button" class="jb-call__later" @click="close()">@lang('app.cancel')</button>
        </div>

        {{-- Envoyé --}}
        <div x-show="state === 'sent'" class="jb-call__done">
            <div class="jb-call__ring"><span>🔔</span><i></i><i></i></div>
            <h3 class="jb-call__title">@lang('modules.waiterRequest.sentTitle')</h3>
            <p class="jb-call__sub"><span x-text="at ? @js(__('modules.waiterRequest.sentAt')) + ' ' + at : ''"></span></p>
            <button type="button" class="jb-call__cta" @click="close()">@lang('modules.waiterRequest.ok')</button>
        </div>

        {{-- Déjà prévenu --}}
        <div x-show="state === 'wait'" class="jb-call__done">
            <div class="jb-call__ring"><span>⏳</span><i></i><i></i></div>
            <h3 class="jb-call__title">@lang('modules.waiterRequest.alreadyTitle')</h3>
            <p class="jb-call__sub">@lang('modules.waiterRequest.alreadySub')</p>
            <button type="button" class="jb-call__cta" @click="close()">@lang('modules.waiterRequest.ok')</button>
        </div>

        {{-- Erreur --}}
        <div x-show="state === 'error'" class="jb-call__done">
            <div class="jb-call__ring is-err"><span>⚠️</span></div>
            <h3 class="jb-call__title">@lang('modules.waiterRequest.errorTitle')</h3>
            <p class="jb-call__sub" x-text="err"></p>
            <button type="button" class="jb-call__cta" @click="state = 'form'">@lang('modules.waiterRequest.retry')</button>
        </div>
    </div>
</div>
