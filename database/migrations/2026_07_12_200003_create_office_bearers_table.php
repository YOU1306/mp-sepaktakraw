<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('office_bearers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('registration_applications')->cascadeOnDelete();
            $table->string('name');
            $table->string('contact', 10);
            $table->text('address');
            $table->string('phone')->nullable();
            $table->string('email');
            $table->string('designation'); // president, vice_president, secretary, joint_secretary, treasurer, member
            $table->timestamps();

            $table->index('application_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('office_bearers');
    }
};
