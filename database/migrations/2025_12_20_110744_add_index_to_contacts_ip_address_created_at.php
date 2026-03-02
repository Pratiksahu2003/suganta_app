<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            // Add index on ip_address and created_at for rate limiting queries
            $table->index(['ip_address', 'created_at'], 'contacts_ip_created_at_index');
        });
        
        // Add prefix index on user_agent for MySQL/MariaDB to optimize device-based queries
        $driver = DB::getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'])) {
            try {
                DB::statement('CREATE INDEX contacts_ip_user_agent_created_at_index ON contacts (ip_address, user_agent(100), created_at)');
            } catch (\Exception $e) {
                // If index creation fails, the basic index will still work
                // The query will use ip_address and created_at index, then filter by user_agent
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropIndex('contacts_ip_created_at_index');
        });
        
        // Drop the prefix index if it exists
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'])) {
            try {
                DB::statement('DROP INDEX contacts_ip_user_agent_created_at_index ON contacts');
            } catch (\Exception $e) {
                // Index might not exist, ignore error
            }
        }
    }
};
