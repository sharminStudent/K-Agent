@php
    use Filament\Widgets\View\Components\ChartWidgetComponent;
    use Illuminate\View\ComponentAttributeBag;

    $color = $this->getColor();
    $heading = $this->getHeading();
    $description = $this->getDescription();
    $type = $this->getType();
    $summary = $this->getTrendSummary();
    $periodLabel = now()->subDays(6)->format('M j').' - '.now()->format('M j');
@endphp

<x-filament-widgets::widget class="fi-wi-chart ka-analytics-widget">
    <x-filament::section
        :description="$description"
        :heading="$heading"
        class="ka-analytics-section"
    >
        <div class="ka-analytics-kicker-row">
            <span class="ka-analytics-kicker">Agent Performance</span>
            <span class="ka-analytics-range">{{ $periodLabel }}</span>
        </div>

        <div class="ka-analytics-summary">
            @foreach ($summary as $item)
                <div class="ka-analytics-pill ka-analytics-pill-{{ $item['tone'] }}">
                    <span class="ka-analytics-pill-label">{{ $item['label'] }}</span>
                    <span class="ka-analytics-pill-value">{{ $item['value'] }}</span>
                </div>
            @endforeach
        </div>

        <div>
            <div
                x-load
                x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('chart', 'filament/widgets') }}"
                wire:ignore
                data-chart-type="{{ $type }}"
                x-data="chart({
                            cachedData: @js($this->getCachedData()),
                            maxHeight: @js($maxHeight = $this->getMaxHeight()),
                            options: @js($this->getOptions()),
                            type: @js($type),
                        })"
                {{
                    (new ComponentAttributeBag)
                        ->color(ChartWidgetComponent::class, $color)
                        ->class([
                            'fi-wi-chart-canvas-ctn',
                            'ka-analytics-canvas-ctn',
                            'fi-wi-chart-canvas-ctn-no-aspect-ratio' => filled($maxHeight),
                        ])
                }}
            >
                <canvas
                    x-ref="canvas"
                    @if ($maxHeight)
                        style="max-height: {{ $maxHeight }}"
                    @endif
                ></canvas>

                <span
                    x-ref="backgroundColorElement"
                    class="fi-wi-chart-bg-color"
                ></span>

                <span
                    x-ref="borderColorElement"
                    class="fi-wi-chart-border-color"
                ></span>

                <span
                    x-ref="gridColorElement"
                    class="fi-wi-chart-grid-color"
                ></span>

                <span
                    x-ref="textColorElement"
                    class="fi-wi-chart-text-color"
                ></span>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
