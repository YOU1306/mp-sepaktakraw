<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('federations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('registration_applications')->cascadeOnDelete();
            $table->string('registration_number');
            $table->foreignId('district_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('federations');
    }
};
