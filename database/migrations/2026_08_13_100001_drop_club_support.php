<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Club registration has been removed — anything a club used to handle
     * (its members, office bearers) is now managed directly by the District
     * Federation / Admin / Super Admin.
     */
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn('club_id');
        });

        Schema::dropIfExists('clubs');
    }

    public function down(): void
    {
        Schema::create('clubs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('registration_applications')->cascadeOnDelete();
            $table->string('club_name');
            $table->string('registration_number');
            $table->string('place');
            $table->foreignId('district_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('players', function (Blueprint $table) {
            $table->foreignId('club_id')->nullable();
        });
    }
};
