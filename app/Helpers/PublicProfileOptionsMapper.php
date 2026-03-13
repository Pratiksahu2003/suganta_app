<?php

namespace App\Helpers;

/**
 * Maps numeric option IDs to config/options.php labels for public API responses.
 * Uses same field->config mapping as ProfileResponseFormatter for consistency.
 */
class PublicProfileOptionsMapper
{
    /**
     * Map teacher profile + optional teachingInfo + profile to option structures.
     * Prefers ProfileTeachingInfo *_id fields when available.
     *
     * @param object $teacher TeacherProfile
     * @param object|null $teachingInfo ProfileTeachingInfo (from user.profile.teachingInfo)
     * @param object|null $profile Profile (from user.profile) for gender_id, highest_qualification
     * @return array<string, array{id: int|string, label: string}|null>
     */
    public static function mapTeacherOptions(object $teacher, ?object $teachingInfo, ?object $profile = null): array
    {
        $opt = fn (string $configKey, $value) => ProfileOptionsHelper::getOptionStructure($configKey, $value);

        return [
            'teaching_mode' => $opt('teaching_mode', $teachingInfo?->teaching_mode_id ?? $teacher->teaching_mode)
                ?? (is_string($teacher->teaching_mode)
                    ? ProfileOptionsHelper::getOptionStructure('teaching_mode_enum', $teacher->teaching_mode)
                    : null),
            'availability_status' => $opt('availability_status', $teachingInfo?->availability_status_id ?? $teacher->availability_status),
            'travel_radius_km' => $opt('travel_radius_km', $teachingInfo?->travel_radius_km_id ?? $teacher->travel_radius_km),
            'hourly_rate_range' => $opt('hourly_rate_range', $teachingInfo?->hourly_rate_id ?? $teacher->hourly_rate_id ?? null),
            'monthly_rate_range' => $opt('monthly_rate_range', $teachingInfo?->monthly_rate_id ?? $teacher->monthly_rate_id ?? null),
            'experience_years' => $opt('teaching_experience_years', $teachingInfo?->teaching_experience_years ?? $teacher->experience_years),
            'gender' => $opt('gender', $profile?->gender_id ?? null),
            'highest_qualification' => $opt('highest_qualification', $profile?->highest_qualification ?? $teacher->highest_qualification_id ?? null),
        ];
    }

    /**
     * Map institute profile + optional instituteInfo to option structures.
     * Prefers ProfileInstituteInfo *_id fields when available.
     *
     * @param object $institute Institute
     * @param object|null $instituteInfo ProfileInstituteInfo (from user.profile.instituteInfo)
     * @return array<string, array{id: int|string, label: string}|null>
     */
    public static function mapInstituteOptions(object $institute, ?object $instituteInfo): array
    {
        $opt = fn (string $configKey, $value) => ProfileOptionsHelper::getOptionStructure($configKey, $value);

        return [
            'institute_type' => $opt('institute_type', $instituteInfo?->institute_type_id ?? null),
            'institute_category' => $opt('institute_category', $instituteInfo?->institute_category_id ?? null),
            'establishment_year' => $opt('establishment_year_range', $instituteInfo?->establishment_year_id ?? $institute->established_year ?? null),
            'total_students' => $opt('total_students_range', $instituteInfo?->total_students_id ?? $institute->total_students ?? null),
            'total_teachers' => $opt('total_teachers_range', $instituteInfo?->total_teachers_id ?? null),
        ];
    }
}
