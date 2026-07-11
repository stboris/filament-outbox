<?php

namespace Stboris\FilamentOutbox\Tests\Fixtures;

use Filament\Panel;
use Filament\PanelProvider;
use Stboris\FilamentOutbox\FilamentOutboxPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->plugin(FilamentOutboxPlugin::make());
    }
}
