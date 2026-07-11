<?php

namespace Stboris\FilamentOutbox\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Order extends Model
{
    protected $guarded = [];

    public static function migrate(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('customer');
            $table->decimal('total', 10, 2)->default(0);
            $table->timestamps();
        });
    }
}
