<?php

namespace App\Livewire;

use App\Models\Item;
use App\Models\Player;
use Livewire\Component;

class PlayerInventory extends Component
{
    public $player;

    public $inventory = [];

    public $resources = [];

    protected $listeners = [
        'player-updated' => 'refreshInventory',
        'quest-completed' => 'refreshInventory',
    ];

    public function mount()
    {
        $this->loadInventory();
    }

    public function loadInventory()
    {
        $this->player = Player::with(['items', 'resources'])->first();

        if (! $this->player) {
            $this->player = Player::create([
                'username' => 'Demo Player',
                'email' => 'demo@example.com',
                'password' => bcrypt('password'),
                'level' => 1,
                'experience' => 0,
            ]);
        }

        // Load items with quantity
        $this->inventory = $this->player->items->map(function (Item $item): array {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'description' => $item->description,
                'type' => $item->type,
                'rarity' => $item->rarity,
                'quantity' => data_get($item->getRelationValue('pivot'), 'quantity'),
            ];
        })->toArray();

        // Load resources
        $this->resources = $this->player->resources->map(function ($resource) {
            return [
                'id' => $resource->id,
                'resource_type' => $resource->resource_type,
                'quantity' => $resource->quantity,
            ];
        })->toArray();
    }

    public function useItem($itemId)
    {
        $item = $this->player->items()->where('item_id', $itemId)->first();

        if (! $item) {
            session()->flash('error', 'Item not found in inventory!');

            return;
        }

        // Decrease quantity
        $newQuantity = $item->pivot->quantity - 1;

        if ($newQuantity <= 0) {
            // Remove item if quantity is 0
            $this->player->items()->detach($itemId);
        } else {
            // Update quantity
            $this->player->items()->updateExistingPivot($itemId, [
                'quantity' => $newQuantity,
            ]);
        }

        $this->loadInventory();
        $this->dispatch('item-used', itemId: $itemId);
        session()->flash('success', "Used {$item->name}!");
    }

    public function dropItem($itemId)
    {
        $item = $this->player->items()->where('item_id', $itemId)->first();

        if (! $item) {
            session()->flash('error', 'Item not found in inventory!');

            return;
        }

        $this->player->items()->detach($itemId);

        $this->loadInventory();
        $this->dispatch('item-dropped', itemId: $itemId);
        session()->flash('info', "Dropped {$item->name}.");
    }

    public function refreshInventory()
    {
        $this->loadInventory();
    }

    public function render()
    {
        return view('livewire.player-inventory');
    }
}
