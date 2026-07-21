<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->nullable()->constrained('registration_applications')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('club_id')->nullable(); // set for club members (constrained later)
            $table->string('member_role')->default('player'); // player, team_manager, coach, referee, scorer, official
            $table->string('category')->nullable(); // sub_junior, junior, senior (players only)
            $table->string('name');
            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->date('dob');
            $table->string('sex'); // male, female, other
            $table->string('email')->nullable();
            $table->string('contact_number', 10)->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
