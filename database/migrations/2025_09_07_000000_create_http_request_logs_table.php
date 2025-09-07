<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('http_request_logs', function (Blueprint $table) {
            $table->id();
            $table->string('method', 10)->index();
            $table->text('url');
            $table->string('ip', 45)->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->json('headers')->nullable();
            $table->json('body')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('http_request_logs');
    }
};
