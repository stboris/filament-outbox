<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Stboris\FilamentOutbox\Tests\FilamentTestCase;
use Stboris\FilamentOutbox\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->in(
    'Channels', 'Commands', 'Endpoints', 'History', 'Messages', 'Triggers', 'ServiceProviderTest.php',
);

uses(FilamentTestCase::class, RefreshDatabase::class)->in('Filament');
