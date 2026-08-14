<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Short-lived OTP codes used to verify a phone number or email address
     * during registration, before any account exists.
     */
    public function up(): void
    {
        Schema::create('verification_codes', function (Blueprint $table) {
            $table->id();
            $table->string('channel'); // phone, email
            $table->string('destination'); // the phone number or email address
            $table->string('purpose')->default('registration');
            $table->string('code', 6);
            $table->string('token')->unique(); // opaque token returned to the browser, proves this destination was verified
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['channel', 'destination']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_codes');
    }
};
