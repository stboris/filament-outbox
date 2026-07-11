<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbox_endpoints', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('channel');
            $table->text('url');
            $table->text('secret')->nullable();
            $table->json('settings')->nullable();
            $table->json('environments')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->index(['channel', 'enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbox_endpoints');
    }
};
