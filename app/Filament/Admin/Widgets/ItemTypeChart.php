<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Item;
use Filament\Widgets\ChartWidget;

class ItemTypeChart extends ChartWidget
{
    protected ?string $heading = 'Items by Type';

    protected function getData(): array
    {
        $itemTypes = Item::selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Items',
                    'data' => array_values($itemTypes),
                    'backgroundColor' => [
                        '#EF4444', // weapon - red
                        '#3B82F6', // armor - blue
                        '#10B981', // consumable - green
                        '#F59E0B', // material - yellow
                        '#8B5CF6', // quest - purple
                        '#6B7280', // misc - gray
                    ],
                ],
            ],
            'labels' => array_map('ucfirst', array_keys($itemTypes)),
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
