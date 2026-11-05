<?php

namespace App\Filament\App\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Page;

class AdventureGuide extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static string|\UnitEnum|null $navigationGroup = 'Play & progress';

    protected static ?string $navigationLabel = 'Adventure guide';

    protected static ?int $navigationSort = -20;

    protected string $view = 'filament.app.pages.adventure-guide';

    public function complete(): void
    {
        abort_unless(auth()->check(), 403);
        auth()->user()->forceFill(['game_guide_completed_at' => now()])->save();
        Notification::make()->title('You are ready for your adventure')->success()->send();
        $this->redirect(Dashboard::getUrl(panel: 'app'));
    }
}
