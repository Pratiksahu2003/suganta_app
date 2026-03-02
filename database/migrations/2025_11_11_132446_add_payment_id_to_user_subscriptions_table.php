<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            // Add payment_id column only if it doesn't exist
            if (!Schema::hasColumn('user_subscriptions', 'payment_id')) {
                $table->unsignedBigInteger('payment_id')->nullable()->after('user_id');
            }
        });

        // Add foreign key constraint only if payments table exists and foreign key doesn't exist
        if (Schema::hasTable('payments')) {
            // Check if foreign key already exists by trying to get table foreign keys
            $foreignKeys = [];
            try {
                $foreignKeys = Schema::getConnection()
                    ->getDoctrineSchemaManager()
                    ->listTableForeignKeys('user_subscriptions');
            } catch (\Exception $e) {
                // If we can't get foreign keys, continue anyway
            }

            $foreignKeyExists = false;
            foreach ($foreignKeys as $foreignKey) {
                if ($foreignKey->getColumns() === ['payment_id']) {
                    $foreignKeyExists = true;
                    break;
                }
            }

            if (!$foreignKeyExists && Schema::hasColumn('user_subscriptions', 'payment_id')) {
                Schema::table('user_subscriptions', function (Blueprint $table) {
                    $table->foreign('payment_id')
                          ->references('id')
                          ->on('payments')
                          ->onDelete('set null');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            // Drop foreign key first if it exists
            $foreignKeys = [];
            try {
                $foreignKeys = Schema::getConnection()
                    ->getDoctrineSchemaManager()
                    ->listTableForeignKeys('user_subscriptions');
            } catch (\Exception $e) {
                // If we can't get foreign keys, continue anyway
            }

            foreach ($foreignKeys as $foreignKey) {
                if ($foreignKey->getColumns() === ['payment_id']) {
                    $table->dropForeign(['payment_id']);
                    break;
                }
            }
            
            // Then drop the column if it exists
            if (Schema::hasColumn('user_subscriptions', 'payment_id')) {
                $table->dropColumn('payment_id');
            }
        });
    }
};
