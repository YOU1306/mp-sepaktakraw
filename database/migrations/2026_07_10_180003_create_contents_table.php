<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contents', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // news, notice, result, event, page
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('body')->nullable();
            $table->string('status')->default('draft'); // draft, published
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('district_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contents');
    }
};
