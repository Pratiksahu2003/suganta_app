<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ensure all teachers have slugs
        $teachers = DB::table('teacher_profiles')
            ->join('users', 'teacher_profiles.user_id', '=', 'users.id')
            ->whereNull('teacher_profiles.slug')
            ->orWhere('teacher_profiles.slug', '')
            ->select('teacher_profiles.id', 'users.name')
            ->get();

        foreach ($teachers as $teacher) {
            $baseSlug = Str::slug($teacher->name);
            $slug = $baseSlug;
            $counter = 1;
            
            // Ensure unique slug
            while (DB::table('teacher_profiles')->where('slug', $slug)->where('id', '!=', $teacher->id)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }
            
            DB::table('teacher_profiles')
                ->where('id', $teacher->id)
                ->update(['slug' => $slug]);
        }

        // Ensure all institutes have slugs
        $institutes = DB::table('institutes')
            ->whereNull('slug')
            ->orWhere('slug', '')
            ->select('id', 'institute_name')
            ->get();

        foreach ($institutes as $institute) {
            $baseSlug = Str::slug($institute->institute_name);
            $slug = $baseSlug;
            $counter = 1;
            
            // Ensure unique slug
            while (DB::table('institutes')->where('slug', $slug)->where('id', '!=', $institute->id)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }
            
            DB::table('institutes')
                ->where('id', $institute->id)
                ->update(['slug' => $slug]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration cannot be safely reversed
        // as it would remove slugs that might be in use
    }
};
