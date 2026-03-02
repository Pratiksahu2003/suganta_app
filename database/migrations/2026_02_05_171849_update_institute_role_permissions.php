<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $roles = ['institute', 'university'];
        
        foreach ($roles as $roleName) {
            $role = Role::where('name', $roleName)->first();
            
            if ($role) {
                $permissions = $role->permissions ?? [];
                
                // Add new permissions if they don't exist
                if (!in_array('manage_subjects', $permissions)) {
                    $permissions[] = 'manage_subjects';
                }
                
                if (!in_array('manage_exams', $permissions)) {
                    $permissions[] = 'manage_exams';
                }
                
                $role->permissions = $permissions;
                $role->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse as adding permissions is generally safe
    }
};
