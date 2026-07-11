<?php

use Stboris\FilamentOutbox\Support\TemplateRenderer;
use Stboris\FilamentOutbox\Tests\Fixtures\Order;

beforeEach(function () {
    Order::migrate();
});

it('replaces attribute placeholders', function () {
    $order = Order::create(['customer' => 'Boris', 'total' => 12.5]);

    expect(TemplateRenderer::render('{customer} owes {total}', $order))
        ->toBe('Boris owes 12.5');
});

it('prefers extra values over attributes', function () {
    $order = Order::create(['customer' => 'Boris']);

    expect(TemplateRenderer::render('{customer} / {event}', $order, ['event' => 'created']))
        ->toBe('Boris / created');
});

it('leaves unknown placeholders visible', function () {
    $order = Order::create(['customer' => 'Boris']);

    expect(TemplateRenderer::render('{customer} {typo_placeholder}', $order))
        ->toBe('Boris {typo_placeholder}');
});

it('stringifies booleans and arrays sensibly', function () {
    $order = Order::create(['customer' => 'Boris']);

    expect(TemplateRenderer::render('{flag} {list}', $order, ['flag' => true, 'list' => ['a' => 1]]))
        ->toBe('true {"a":1}');
});
