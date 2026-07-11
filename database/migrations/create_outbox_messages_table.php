<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbox_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outbox_endpoint_id')->nullable()->constrained('outbox_endpoints')->nullOnDelete();
            $table->foreignId('resent_from_id')->nullable()->constrained('outbox_messages')->nullOnDelete();
            $table->string('channel');
            $table->text('url');
            $table->string('method')->default('post');
            $table->json('headers')->nullable();
            $table->json('payload')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->text('error')->nullable();
            $table->string('notification')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'channel']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbox_messages');
    }
};
