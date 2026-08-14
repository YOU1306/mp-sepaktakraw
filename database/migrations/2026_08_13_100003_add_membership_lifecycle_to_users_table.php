<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('membership_period')->nullable()->after('must_change_password');
            $table->timestamp('membership_expires_at')->nullable()->after('membership_period');
            $table->timestamp('membership_reminder_sent_at')->nullable()->after('membership_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['membership_period', 'membership_expires_at', 'membership_reminder_sent_at']);
        });
    }
};
