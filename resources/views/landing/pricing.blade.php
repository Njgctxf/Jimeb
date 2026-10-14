@php
    $hasMonthly = $monthlyPackages->count() > 0;
    $hasAnnual = $annualPackages->count() > 0;
@endphp

<div x-data="{ isAnnual: {{ !$hasMonthly && $hasAnnual ? 'true' : 'false' }} }">
    @if ($hasMonthly && $hasAnnual)
        <div style="text-align:center">
            <div class="jb-switch" role="group" aria-label="Période de facturation">
                <button type="button" :aria-pressed="(!isAnnual).toString()" @click="isAnnual = false">@lang('modules.package.payMonthly')</button>
                <button type="button" :aria-pressed="isAnnual.toString()" @click="isAnnual = true">@lang('modules.package.payAnnually')</button>
            </div>
        </div>
    @endif

    <div class="jb-plans">
        @foreach ($packages as $package)
            @php
                $packageAllModules = array_merge(
                    $package->modules->pluck('name')->toArray(),
                    $package->additional_features ? json_decode($package->additional_features, true) : []
                );
                $isLifetime = $package->package_type == \App\Enums\PackageType::LIFETIME;
                $isFree = $package->package_type == \App\Enums\PackageType::FREE;
                $isStandard = $package->package_type == \App\Enums\PackageType::STANDARD;
                $visible = '(!isAnnual && ' . ($package->monthly_status ? 'true' : 'false')
                    . ' || isAnnual && ' . ($package->annual_status ? 'true' : 'false')
                    . ' || ' . ($isLifetime ? 'true' : 'false')
                    . ' || ' . ($isFree ? 'true' : 'false') . ')';
            @endphp

            <article class="jb-plan {{ $loop->iteration === 2 ? 'jb-plan--accent' : '' }}" x-show="{{ $visible }}">
                <div>
                    <h3>{{ $package->package_name }}</h3>
                    @if (!empty($package->description))
                        <p class="jb-plan__desc">{{ $package->description }}</p>
                    @endif
                </div>

                <div>
                    @if ($isFree)
                        <div class="jb-plan__price">Gratuit</div>
                    @endif
                    @if ($isLifetime)
                        <div class="jb-plan__price">{{ global_currency_format($package->price, $package->currency_id) }}<small>{{ __('modules.package.payOnce') }}</small></div>
                    @endif
                    @if ($isStandard && !is_null($package->monthly_price) && $package->monthly_status)
                        <div class="jb-plan__price" x-show="!isAnnual">{{ global_currency_format($package->monthly_price, $package->currency_id) }}<small>{{ __('modules.package.payMonthly') }}</small></div>
                    @endif
                    @if ($isStandard && !is_null($package->annual_price) && $package->annual_status)
                        <div class="jb-plan__price" x-show="isAnnual">{{ global_currency_format($package->annual_price, $package->currency_id) }}<small>{{ __('modules.package.payAnnually') }}</small></div>
                    @endif
                </div>

                <a href="{{ route('restaurant_signup') }}" class="jb-btn jb-btn--block {{ $loop->iteration === 2 ? 'jb-btn--blue' : 'jb-btn--dark' }}">
                    @lang('landing.getStarted')
                </a>

                <ul class="jb-plan__list">
                    @foreach ($modules as $moduleName)
                        @continue(!in_array($moduleName, $packageAllModules))
                        @php
                            $limitValue = match ($moduleName) {
                                'Menu Item' => $package->menu_items_limit,
                                'Order' => $package->order_limit,
                                'Staff' => $package->staff_limit,
                                default => null,
                            };
                        @endphp
                        <li>
                            <span>
                                {{ __('permissions.modules.' . $moduleName) }}
                                @if (!is_null($limitValue))
                                    <b>
                                        @if ($limitValue == -1)
                                            ({{ __('modules.billing.unlimited') }})
                                        @elseif ($moduleName === 'Order')
                                            ({{ number_format($limitValue) }} {{ __('modules.billing.perDay') }})
                                        @else
                                            ({{ number_format($limitValue) }})
                                        @endif
                                    </b>
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            </article>
        @endforeach
    </div>
</div>
