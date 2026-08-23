<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->string('aadhaar_verification_status', 20)->default('pending')->after('aadhaar_identity_match');
        });

        Schema::table('office_bearers', function (Blueprint $table) {
            $table->string('aadhaar_number_masked', 20)->nullable()->after('email');
            $table->string('aadhaar_verification_status', 20)->default('pending')->after('aadhaar_number_masked');
            $table->text('aadhaar_verification_note')->nullable()->after('aadhaar_verification_status');
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn('aadhaar_verification_status');
        });

        Schema::table('office_bearers', function (Blueprint $table) {
            $table->dropColumn([
                'aadhaar_number_masked',
                'aadhaar_verification_status',
                'aadhaar_verification_note',
            ]);
        });
    }
};
