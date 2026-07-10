<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intake_openings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('district_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('fee_amount'); // paise
            $table->json('form_schema')->nullable();
            $table->timestamp('opens_at')->nullable();
            $table->timestamp('closes_at')->nullable();
            $table->string('status')->default('draft'); // draft, open, closed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intake_openings');
    }
};
