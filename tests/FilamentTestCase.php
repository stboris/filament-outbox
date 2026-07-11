<?php

namespace Stboris\FilamentOutbox\Tests;

use Filament\Facades\Filament;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\Factories\UserFactory;

#[WithMigration]
abstract class FilamentTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(UserFactory::new()->create());

        Filament::setCurrentPanel('admin');
    }
}
