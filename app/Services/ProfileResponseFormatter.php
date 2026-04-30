<?php

namespace App\Services;

use App\Helpers\ProfileOptionsHelper;
use App\Models\Profile;

class ProfileResponseFormatter
{
    /**
     * Format profile for API response with option structures (id + label) from config/options.php.
     *
     * @param Profile $profile
     * @param callable|null $getFileUrl Callback for file URLs, e.g. fn($path) => $this->getFileUrl($path)
     * @return array
     */
    public static function format(Profile $profile, ?callable $getFileUrl = null): array
    {
        $profile->load(['instituteInfo', 'studentInfo', 'teachingInfo', 'professionalInfo']);

        $profileArray = $profile->toArray();

        $formattedProfile = self::transformProfileOptions($profileArray);

        if ($profile->instituteInfo) {
            $formattedProfile['institute_info'] = self::transformInstituteOptions($profile->instituteInfo->toArray());
        }

        if ($profile->studentInfo) {
            $formattedProfile['student_info'] = self::transformStudentOptions($profile->studentInfo->toArray());
        }

        if ($profile->teachingInfo) {
            $formattedProfile['teaching_info'] = self::transformTeachingOptions($profile->teachingInfo->toArray());
        }

        $result = [
            'profile' => $formattedProfile,
            'profile_image_url' => $getFileUrl && $profile->profile_image
                ? $getFileUrl($profile->profile_image)
                : null,
            'completion_percentage' => $profile->profile_completion_percentage,
        ];

        return $result;
    }

    /**
     * Transform profile-level ID fields to option structures.
     */
    private static function transformProfileOptions(array $data): array
    {
        $optionMappings = [
            'gender_id' => 'gender',
            'country_id' => 'country',
            'timezone_id' => 'timezone',
        ];

        foreach ($optionMappings as $idField => $optionKey) {
            if (isset($data[$idField]) && $data[$idField] !== null) {
                $structure = ProfileOptionsHelper::getOptionStructure($optionKey, $data[$idField]);
                $baseName = str_replace('_id', '', $idField);
                $data[$baseName] = $structure;
            }
        }

        return $data;
    }

    /**
     * Transform institute info ID fields to option structures.
     */
    private static function transformInstituteOptions(array $data): array
    {
        $optionMappings = [
            'institute_type_id' => 'institute_type',
            'institute_category_id' => 'institute_category',
            'establishment_year_id' => 'establishment_year_range',
            'total_students_id' => 'total_students_range',
            'total_teachers_id' => 'total_teachers_range',
        ];

        foreach ($optionMappings as $idField => $optionKey) {
            if (isset($data[$idField]) && $data[$idField] !== null) {
                $structure = ProfileOptionsHelper::getOptionStructure($optionKey, $data[$idField]);
                $baseName = str_replace('_id', '', $idField);
                $data[$baseName] = $structure;
            }
        }

        return $data;
    }

    /**
     * Transform student info ID fields to option structures.
     */
    private static function transformStudentOptions(array $data): array
    {
        $optionMappings = [
            'current_class_id' => 'current_class',
            'board_id' => 'board',
            'stream_id' => 'stream',
        ];

        foreach ($optionMappings as $idField => $optionKey) {
            if (isset($data[$idField]) && $data[$idField] !== null) {
                $structure = ProfileOptionsHelper::getOptionStructure($optionKey, $data[$idField]);
                $baseName = str_replace('_id', '', $idField);
                $data[$baseName] = $structure;
            }
        }

        return $data;
    }

    /**
     * Transform teaching info ID fields to option structures.
     */
    private static function transformTeachingOptions(array $data): array
    {
        $optionMappings = [
            'hourly_rate_id' => 'hourly_rate_range',
            'monthly_rate_id' => 'monthly_rate_range',
            'travel_radius_km_id' => 'travel_radius_km',
            'teaching_mode_id' => 'teaching_mode',
            'availability_status_id' => 'availability_status',
        ];

        foreach ($optionMappings as $idField => $optionKey) {
            if (isset($data[$idField]) && $data[$idField] !== null) {
                $structure = ProfileOptionsHelper::getOptionStructure($optionKey, $data[$idField]);
                $baseName = str_replace('_id', '', $idField);
                $data[$baseName] = $structure;
            }
        }

        return $data;
    }
}
