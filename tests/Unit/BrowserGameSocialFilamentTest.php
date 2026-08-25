<?php

use Liberu\BrowserGame\SocialFilament\Resources\SocialResource;
use Liberu\BrowserGame\SocialFilament\Resources\SocialResource\Pages\CreateSocial;
use Liberu\BrowserGame\SocialFilament\Resources\SocialResource\Pages\EditSocial;
use Liberu\BrowserGame\SocialFilament\Resources\SocialResource\Pages\ListSocial;

it('exposes resolvable social resource pages', function (): void {
    expect(SocialResource::getPages())
        ->toHaveKeys(['index', 'create', 'edit'])
        ->and(SocialResource::getPages()['index']->getPage())->toBe(ListSocial::class)
        ->and(SocialResource::getPages()['create']->getPage())->toBe(CreateSocial::class)
        ->and(SocialResource::getPages()['edit']->getPage())->toBe(EditSocial::class);
});
