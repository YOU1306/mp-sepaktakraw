<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->boolean('aadhaar_verified')->default(false)->after('address');
            $table->string('aadhaar_number_masked', 20)->nullable()->after('aadhaar_verified');
            $table->json('aadhaar_kyc_data')->nullable()->after('aadhaar_number_masked');
            $table->text('aadhaar_kyc_note')->nullable()->after('aadhaar_kyc_data');
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn(['aadhaar_verified', 'aadhaar_number_masked', 'aadhaar_kyc_data', 'aadhaar_kyc_note']);
        });
    }
};
