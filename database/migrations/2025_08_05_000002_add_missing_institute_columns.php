<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Helpers\ProfileOptionsHelper;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('profile_institute_info', function (Blueprint $table) {
            // Add missing integer ID columns
            $table->integer('establishment_year_id')->nullable()->after('establishment_year');
            $table->integer('total_students_id')->nullable()->after('total_students');
            $table->integer('total_teachers_id')->nullable()->after('total_teachers');
        });

        // Migrate existing data
        $this->migrateExistingData();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profile_institute_info', function (Blueprint $table) {
            $table->dropColumn(['establishment_year_id', 'total_students_id', 'total_teachers_id']);
        });
    }

    /**
     * Migrate existing string data to integer values
     */
    private function migrateExistingData()
    {
        // Migrate profile_institute_info table
        $instituteInfos = \DB::table('profile_institute_info')->get();
        foreach ($instituteInfos as $info) {
            $updates = [];
            
            // Convert establishment year
            if ($info->establishment_year) {
                $establishmentYearId = ProfileOptionsHelper::getValue('establishment_year_range', $info->establishment_year);
                if ($establishmentYearId !== null) {
                    $updates['establishment_year_id'] = $establishmentYearId;
                }
            }
            
            // Convert total students
            if ($info->total_students) {
                $totalStudentsId = ProfileOptionsHelper::getValue('total_students_range', $info->total_students);
                if ($totalStudentsId !== null) {
                    $updates['total_students_id'] = $totalStudentsId;
                }
            }
            
            // Convert total teachers
            if ($info->total_teachers) {
                $totalTeachersId = ProfileOptionsHelper::getValue('total_teachers_range', $info->total_teachers);
                if ($totalTeachersId !== null) {
                    $updates['total_teachers_id'] = $totalTeachersId;
                }
            }
            
            if (!empty($updates)) {
                \DB::table('profile_institute_info')->where('id', $info->id)->update($updates);
            }
        }
    }
}; 