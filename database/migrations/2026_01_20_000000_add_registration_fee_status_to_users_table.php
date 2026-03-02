<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // pending: payment required but not paid yet
            // paid: registration fee paid successfully
            // not_required: role doesn't require registration payment
            // failed: payment attempted but failed/cancelled/expired
            $table->string('registration_fee_status', 30)
                ->nullable()
                ->after('verification_status')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['registration_fee_status']);
            $table->dropColumn('registration_fee_status');
        });
    }
};

