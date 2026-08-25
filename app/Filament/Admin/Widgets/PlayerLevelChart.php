<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Player;
use Filament\Widgets\ChartWidget;

class PlayerLevelChart extends ChartWidget
{
    protected ?string $heading = 'Player Level Distribution';

    protected function getData(): array
    {
        $levelRanges = [
            '1-10' => Player::whereBetween('level', [1, 10])->count(),
            '11-20' => Player::whereBetween('level', [11, 20])->count(),
            '21-30' => Player::whereBetween('level', [21, 30])->count(),
            '31-40' => Player::whereBetween('level', [31, 40])->count(),
            '41-50' => Player::whereBetween('level', [41, 50])->count(),
            '51+' => Player::where('level', '>', 50)->count(),
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Players',
                    'data' => array_values($levelRanges),
                    'backgroundColor' => [
                        '#10B981',
                        '#3B82F6',
                        '#8B5CF6',
                        '#F59E0B',
                        '#EF4444',
                        '#6B7280',
                    ],
                ],
            ],
            'labels' => array_keys($levelRanges),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
