{{-- Expects $order with order_status and order_type --}}
<div class="overflow-hidden rounded-lg bg-gray-50 dark:bg-gray-800">
    <div class="px-4 py-3 border-gray-200 bg-gray-50 dark:bg-gray-700 dark:border-gray-600">
        @if ($order->order_status->value === 'cancelled')
            <div class="flex items-center gap-2 text-red-600 dark:text-red-400">
                <div class="p-1.5 bg-red-100 rounded-full dark:bg-red-900/30">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-12.728 12.728M5.636 5.636l12.728 12.728" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold">
                        @lang('modules.order.info_cancelled')
                    </h3>
                    <p class="text-xs">
                        @lang('modules.order.orderCancelledMessage')
                    </p>
                </div>
            </div>
        @else
            @php
                $steps = \App\Enums\OrderStatus::progressStepsFor($order->order_type);
                $currentStepIndex = \App\Enums\OrderStatus::progressIndex($order->order_status->value, $order->order_type);
            @endphp

            <div>
                <!-- Progress Bar -->
                <div class="left-0 hidden w-full h-0.5 bg-gray-200 rounded sm:block top-5 mt-5 dark:bg-gray-600">
                    <div class="h-0.5 progress-bar-fill rounded bg-skin-base/80 dark:bg-skin-base/90"
                        style="width: {{ min(100, ($currentStepIndex / max(1, count($steps) - 1)) * 100) }}%"></div>
                </div>

                <!-- Status Steps -->
                <div class="flex flex-col justify-between text-gray-500 sm:flex-row sm:items-center sm:-mt-5 dark:text-gray-400">
                    @foreach($steps as $index => $status)
                        @php
                            $stepStatus = \App\Enums\OrderStatus::from($status);
                            $isCompleted = $index < $currentStepIndex;
                            $isCurrent = $index === $currentStepIndex;
                            $isNext = $index === $currentStepIndex + 1;
                        @endphp
                        <div @class([
                            'flex items-start gap-2 mb-3 sm:mb-0',
                            'flex-row sm:flex-col',
                            'sm:items-center' => !$loop->first && !$loop->last,
                            'sm:items-start' => $loop->first,
                            'sm:items-end' => $loop->last,
                            'w-full sm:w-auto',
                        ])>
                            <div @class([
                                'flex-shrink-0 flex items-center justify-center w-8 h-8 rounded-full shadow-sm transition-all duration-300 ring-2 ring-white dark:ring-gray-800 [&_.order-status-step-svg]:text-current',
                                $stepStatus->progressStepIconClasses($isCompleted, $isCurrent, $isNext),
                            ]) data-status-step="{{ $status }}">
                                @if($index <= $currentStepIndex)
                                    <svg class="w-4 h-4 {{ $index < $currentStepIndex ? 'animate-checkmark' : '' }}" fill="none" viewBox="0 0 24 24">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                @else
                                    {!! $stepStatus->icon() !!}
                                @endif
                            </div>

                            @if(!$loop->last)
                                <div class="top-8 left-4 h-full w-0.5 bg-gray-200 dark:bg-gray-600 hidden">
                                    @if($index < $currentStepIndex)
                                        <div class="h-full transition-all duration-700 ease-out bg-skin-base/60 dark:bg-skin-base/80"></div>
                                    @endif
                                </div>
                            @endif

                            <div @class([
                                'flex-1 mt-0 sm:mt-2',
                                'text-left sm:text-center' => !$loop->first && !$loop->last,
                                'text-left' => $loop->first || true,
                                'sm:text-right' => $loop->last,
                                'pt-1 sm:pt-0',
                            ])>
                                <p @class([
                                    'text-xs font-semibold transition-all duration-300',
                                    'text-skin-base dark:text-skin-base/90' => $index <= $currentStepIndex,
                                    'text-gray-500 dark:text-gray-400' => $index > $currentStepIndex,
                                ])>
                                    {{ $stepStatus->translatedLabel() }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
