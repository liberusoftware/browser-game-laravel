<?php

namespace App\Services;

use App\Models\Menu;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;

class MenuService
{
    public function buildMenu(): HtmlString
    {
        $items = Menu::query()
            ->whereNull('parent_id')
            ->with('children')
            ->orderBy('order')
            ->get();

        return new HtmlString($this->renderItems($items));
    }

    /**
     * @param  Collection<int, Menu>  $items
     */
    private function renderItems(Collection $items): string
    {
        $html = '<ul class="flex items-center space-x-4">';

        foreach ($items as $item) {
            $label = e($item->name);
            $url = e($item->url);
            $children = $item->children;
            $html .= '<li><a href="'.$url.'" class="p-2 text-sm">'.$label.'</a>';

            if ($children->isNotEmpty()) {
                $html .= $this->renderItems($children);
            }

            $html .= '</li>';
        }

        return $html.'</ul>';
    }
}
