<?php

namespace App\Livewire;

use App\Models\Player;
use App\Models\Recipe;
use App\Services\CraftingService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CraftingWorkshop extends Component
{
    public $player;

    public $selectedRecipe;

    public $craftingResult;

    public function mount()
    {
        $this->player = Auth::user()?->player ?? Player::where('email', Auth::user()->email)->first();
    }

    public function selectRecipe($recipeId)
    {
        $this->selectedRecipe = Recipe::with(['resultItem', 'materials.item'])->find($recipeId);
        $this->craftingResult = null;
    }

    public function craftItem()
    {
        if (! $this->selectedRecipe) {
            return;
        }

        $craftingService = app(CraftingService::class);
        $this->craftingResult = $craftingService->craftItem($this->player, $this->selectedRecipe);
        $this->player->refresh();

        $this->dispatch('item-crafted');

        if ($this->craftingResult['success']) {
            $this->dispatch('show-message', message: $this->craftingResult['message']);
        } else {
            $this->dispatch('show-error', message: $this->craftingResult['message']);
        }
    }

    public function render()
    {
        return view('livewire.crafting-workshop', [
            'knownRecipes' => $this->player->recipes()
                ->with(['resultItem', 'materials.item'])
                ->get(),
            'availableRecipes' => Recipe::where('min_level', '<=', $this->player->level)
                ->whereNotIn('id', $this->player->recipes()->pluck('recipes.id'))
                ->with(['resultItem', 'materials.item'])
                ->get(),
        ]);
    }
}
