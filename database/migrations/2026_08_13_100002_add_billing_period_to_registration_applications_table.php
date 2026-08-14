<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registration_applications', function (Blueprint $table) {
            $table->string('billing_period')->nullable()->after('district_id'); // quarterly, half_yearly, yearly
        });
    }

    public function down(): void
    {
        Schema::table('registration_applications', function (Blueprint $table) {
            $table->dropColumn('billing_period');
        });
    }
};
