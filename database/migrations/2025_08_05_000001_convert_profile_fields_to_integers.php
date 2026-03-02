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
        Schema::table('profiles', function (Blueprint $table) {
            // Convert gender from string to integer
            $table->integer('gender_id')->nullable()->after('gender');
            
            // Convert country from string to integer
            $table->integer('country_id')->nullable()->after('country');
            
            // Convert timezone from string to integer
            $table->integer('timezone_id')->nullable()->after('timezone');
        });

        Schema::table('profile_teaching_info', function (Blueprint $table) {
            // Convert teaching mode from string to integer
            $table->integer('teaching_mode_id')->nullable()->after('teaching_mode');
            
            // Convert availability status from string to integer
            $table->integer('availability_status_id')->nullable()->after('availability_status');
        });

        Schema::table('profile_institute_info', function (Blueprint $table) {
            // Convert institute type from string to integer
            $table->integer('institute_type_id')->nullable()->after('institute_type');
            
            // Convert institute category from string to integer
            $table->integer('institute_category_id')->nullable()->after('institute_category');
        });

        Schema::table('profile_student_info', function (Blueprint $table) {
            // Convert current class from string to integer
            $table->integer('current_class_id')->nullable()->after('current_class');
            
            // Convert board from string to integer
            $table->integer('board_id')->nullable()->after('board');
            
            // Convert stream from string to integer
            $table->integer('stream_id')->nullable()->after('stream');
        });

        // Migrate existing data
        $this->migrateExistingData();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn(['gender_id', 'country_id', 'timezone_id']);
        });

        Schema::table('profile_teaching_info', function (Blueprint $table) {
            $table->dropColumn(['teaching_mode_id', 'availability_status_id']);
        });

        Schema::table('profile_institute_info', function (Blueprint $table) {
            $table->dropColumn(['institute_type_id', 'institute_category_id']);
        });

        Schema::table('profile_student_info', function (Blueprint $table) {
            $table->dropColumn(['current_class_id', 'board_id', 'stream_id']);
        });
    }

    /**
     * Migrate existing string data to integer values
     */
    private function migrateExistingData()
    {
        // Migrate profiles table
        $profiles = \DB::table('profiles')->get();
        foreach ($profiles as $profile) {
            $updates = [];
            
            // Convert gender
            if ($profile->gender) {
                $genderId = ProfileOptionsHelper::getValue('gender', $profile->gender);
                if ($genderId !== null) {
                    $updates['gender_id'] = $genderId;
                }
            }
            
            // Convert country
            if ($profile->country) {
                $countryId = ProfileOptionsHelper::getValue('country', $profile->country);
                if ($countryId !== null) {
                    $updates['country_id'] = $countryId;
                }
            }
            
            // Convert timezone
            if ($profile->timezone) {
                $timezoneId = ProfileOptionsHelper::getValue('timezone', $profile->timezone);
                if ($timezoneId !== null) {
                    $updates['timezone_id'] = $timezoneId;
                }
            }
            
            if (!empty($updates)) {
                \DB::table('profiles')->where('id', $profile->id)->update($updates);
            }
        }

        // Migrate profile_teaching_info table
        $teachingInfos = \DB::table('profile_teaching_info')->get();
        foreach ($teachingInfos as $info) {
            $updates = [];
            
            // Convert teaching mode
            if ($info->teaching_mode) {
                $teachingModeId = ProfileOptionsHelper::getValue('teaching_mode', $info->teaching_mode);
                if ($teachingModeId !== null) {
                    $updates['teaching_mode_id'] = $teachingModeId;
                }
            }
            
            // Convert availability status
            if ($info->availability_status) {
                $availabilityStatusId = ProfileOptionsHelper::getValue('availability_status', $info->availability_status);
                if ($availabilityStatusId !== null) {
                    $updates['availability_status_id'] = $availabilityStatusId;
                }
            }
            
            if (!empty($updates)) {
                \DB::table('profile_teaching_info')->where('id', $info->id)->update($updates);
            }
        }

        // Migrate profile_institute_info table
        $instituteInfos = \DB::table('profile_institute_info')->get();
        foreach ($instituteInfos as $info) {
            $updates = [];
            
            // Convert institute type
            if ($info->institute_type) {
                $instituteTypeId = ProfileOptionsHelper::getValue('institute_type', $info->institute_type);
                if ($instituteTypeId !== null) {
                    $updates['institute_type_id'] = $instituteTypeId;
                }
            }
            
            // Convert institute category
            if ($info->institute_category) {
                $instituteCategoryId = ProfileOptionsHelper::getValue('institute_category', $info->institute_category);
                if ($instituteCategoryId !== null) {
                    $updates['institute_category_id'] = $instituteCategoryId;
                }
            }
            
            if (!empty($updates)) {
                \DB::table('profile_institute_info')->where('id', $info->id)->update($updates);
            }
        }

        // Migrate profile_student_info table
        $studentInfos = \DB::table('profile_student_info')->get();
        foreach ($studentInfos as $info) {
            $updates = [];
            
            // Convert current class
            if ($info->current_class) {
                $currentClassId = ProfileOptionsHelper::getValue('current_class', $info->current_class);
                if ($currentClassId !== null) {
                    $updates['current_class_id'] = $currentClassId;
                }
            }
            
            // Convert board
            if ($info->board) {
                $boardId = ProfileOptionsHelper::getValue('board', $info->board);
                if ($boardId !== null) {
                    $updates['board_id'] = $boardId;
                }
            }
            
            // Convert stream
            if ($info->stream) {
                $streamId = ProfileOptionsHelper::getValue('stream', $info->stream);
                if ($streamId !== null) {
                    $updates['stream_id'] = $streamId;
                }
            }
            
            if (!empty($updates)) {
                \DB::table('profile_student_info')->where('id', $info->id)->update($updates);
            }
        }
    }
}; 