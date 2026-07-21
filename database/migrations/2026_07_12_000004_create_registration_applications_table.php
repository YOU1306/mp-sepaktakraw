<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_applications', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // individual, federation, club
            $table->string('reference_no')->unique();
            $table->string('status')->default('under_review'); // draft, pending_payment, under_review, approved, rejected
            $table->string('applicant_name')->nullable();
            $table->string('applicant_email')->nullable();
            $table->foreignId('district_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // account created on approval
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_applications');
    }
};
