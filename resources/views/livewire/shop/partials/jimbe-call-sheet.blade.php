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
        open: false, reason: 'help', msg: '', busy: false, state: 'form', at: '', tableHash: '', err: '',
        reasons: [
            { k: 'help', t: @js(__('modules.waiterRequest.reason_help')) },
            { k: 'bill', t: @js(__('modules.waiterRequest.reason_bill')) },
            { k: 'water', t: @js(__('modules.waiterRequest.reason_water')) },
            { k: 'other', t: @js(__('modules.waiterRequest.reason_other')) },
        ],
        hasTable: @js(!empty($table?->id)),
        show() { this.state = 'form'; this.err = ''; this.msg = ''; this.open = true; },
        close() { this.open = false; },
        async send() {
            if (!this.hasTable && !this.tableHash) { this.err = @js(__('modules.shop.tableRequired')); return; }
            this.busy = true; this.err = '';
            try {
                const wire = Livewire.find(lwId);
                const r = await wire.call('callWaiterFromShop', this.reason, this.hasTable ? null : this.tableHash, this.msg.trim());
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
            <div class="jb-call__bell"><svg width="34" height="34" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg></div>
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
                    <button type="button" class="jb-call__reason" :class="reason === r.k ? 'is-on' : ''" @click="reason = r.k; if (r.k === 'other') $nextTick(() => $refs.msg.focus())">
                        <b x-text="r.t"></b>
                    </button>
                </template>
            </div>

            <div class="jb-call__msg">
                <textarea x-model="msg" x-ref="msg" maxlength="140" rows="2"
                    placeholder="{{ __('modules.waiterRequest.messagePlaceholder') }}"
                    aria-label="{{ __('modules.waiterRequest.messageLabel') }}"></textarea>
                <span class="jb-call__count" x-text="msg.length + '/140'"></span>
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
            <div class="jb-call__ring"><span><svg width="30" height="30" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg></span><i></i><i></i></div>
            <h3 class="jb-call__title">@lang('modules.waiterRequest.sentTitle')</h3>
            <p class="jb-call__sub"><span x-text="at ? @js(__('modules.waiterRequest.sentAt')) + ' ' + at : ''"></span></p>
            <button type="button" class="jb-call__cta" @click="close()">@lang('modules.waiterRequest.ok')</button>
        </div>

        {{-- Déjà prévenu --}}
        <div x-show="state === 'wait'" class="jb-call__done">
            <div class="jb-call__ring"><span><svg width="30" height="30" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg></span><i></i><i></i></div>
            <h3 class="jb-call__title">@lang('modules.waiterRequest.alreadyTitle')</h3>
            <p class="jb-call__sub">@lang('modules.waiterRequest.alreadySub')</p>
            <button type="button" class="jb-call__cta" @click="close()">@lang('modules.waiterRequest.ok')</button>
        </div>

        {{-- Erreur --}}
        <div x-show="state === 'error'" class="jb-call__done">
            <div class="jb-call__ring is-err"><span><svg width="30" height="30" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/></svg></span></div>
            <h3 class="jb-call__title">@lang('modules.waiterRequest.errorTitle')</h3>
            <p class="jb-call__sub" x-text="err"></p>
            <button type="button" class="jb-call__cta" @click="state = 'form'">@lang('modules.waiterRequest.retry')</button>
        </div>
    </div>
</div>
