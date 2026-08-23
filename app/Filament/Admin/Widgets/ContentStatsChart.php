<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Item;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ContentStatsChart extends ChartWidget
{
    protected ?string $heading = 'Content by Category';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $itemsByType = Item::select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        $labels = [];
        $data = [];
        $colors = [];

        $typeColors = [
            'weapon' => '#EF4444',
            'armor' => '#3B82F6',
            'consumable' => '#10B981',
            'material' => '#F59E0B',
            'quest' => '#8B5CF6',
            'misc' => '#6B7280',
        ];

        foreach ($itemsByType as $type => $count) {
            $labels[] = ucfirst($type);
            $data[] = $count;
            $colors[] = $typeColors[$type] ?? '#6B7280';
        }

        return [
            'datasets' => [
                [
                    'label' => 'Items',
                    'data' => $data,
                    'backgroundColor' => $colors,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
