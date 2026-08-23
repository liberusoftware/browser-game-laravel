<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Player;
use Filament\Widgets\ChartWidget;

class PlayerProgressWidget extends ChartWidget
{
    protected ?string $heading = 'Player Progress Metrics';

    protected function getData(): array
    {
        $metrics = [
            'Quests Completed' => Player::withCount(['quests' => function ($query) {
                $query->where('status', 'completed');
            }])->get()->sum('quests_count'),
            'Quests In Progress' => Player::withCount(['quests' => function ($query) {
                $query->where('status', 'in-progress');
            }])->get()->sum('quests_count'),
            'Total Players' => Player::count(),
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Count',
                    'data' => array_values($metrics),
                    'backgroundColor' => [
                        '#10B981',
                        '#F59E0B',
                        '#3B82F6',
                    ],
                ],
            ],
            'labels' => array_keys($metrics),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
