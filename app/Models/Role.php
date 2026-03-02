<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'permissions',
        'feature_visibility',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'permissions' => 'array',
        'feature_visibility' => 'array',
    ];

    /**
     * Get the slug attribute
     */
    public function getSlugAttribute()
    {
        return Str::slug($this->name);
    }

    /**
     * Get the is_active attribute (default to true for existing roles)
     */
    public function getIsActiveAttribute()
    {
        return true; // Default to active for existing roles
    }

    /**
     * Get the users that have this role (single-role-per-user setup)
     */
    public function users()
    {
        return $this->hasMany(User::class, 'role', 'name');
    }

    /**
     * Get users through the many-to-many relationship
     */
    public function usersManyToMany()
    {
        return $this->belongsToMany(User::class, 'user_roles')->withTimestamps();
    }

    /**
     * Check if role has a specific permission
     */
    public function hasPermission(string $permission): bool
    {
        $permissions = $this->permissions ?? [];
        return in_array($permission, $permissions);
    }

    /**
     * Check if role has any of the given permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if role has all of the given permissions
     */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Add a permission to the role
     */
    public function addPermission(string $permission): void
    {
        $permissions = $this->permissions ?? [];
        
        if (!in_array($permission, $permissions)) {
            $permissions[] = $permission;
            $this->update(['permissions' => $permissions]);
        }
    }

    /**
     * Remove a permission from the role
     */
    public function removePermission(string $permission): void
    {
        $permissions = $this->permissions ?? [];
        $permissions = array_diff($permissions, [$permission]);
        
        $this->update(['permissions' => array_values($permissions)]);
    }

    /**
     * Sync permissions for the role
     */
    public function syncPermissions(array $permissions): void
    {
        $this->update(['permissions' => $permissions]);
    }

    /**
     * Check if a feature is visible for this role
     */
    public function isFeatureVisible(string $feature): bool
    {
        $featureVisibility = $this->feature_visibility ?? [];
        return isset($featureVisibility[$feature]) && $featureVisibility[$feature];
    }

    /**
     * Set feature visibility
     */
    public function setFeatureVisibility(string $feature, bool $visible): void
    {
        $featureVisibility = $this->feature_visibility ?? [];
        $featureVisibility[$feature] = $visible;
        
        $this->update(['feature_visibility' => $featureVisibility]);
    }

    /**
     * Get default permissions for admin role
     */
    public static function getDefaultAdminPermissions(): array
    {
        return [
            'view_dashboard', 'view_admin_dashboard', 'view_statistics', 'view_analytics',
            'view_users', 'create_users', 'edit_users', 'delete_users', 'verify_users', 'manage_roles',
            'view_teachers', 'create_teachers', 'edit_teachers', 'delete_teachers', 'verify_teachers',
            'view_institutes', 'create_institutes', 'edit_institutes', 'delete_institutes', 'verify_institutes',
            'manage_subjects', 'add_subject', 'manage_exams', 'create_exam_packages', 'take_exams', 'view_results',
            'manage_blog',
            'create_sessions', 'manage_sessions', 'book_sessions', 'manage_schedule',
            'manage_students', 'view_earnings', 'view_progress', 'manage_favorites',
            'manage_branches', 'manage_teachers', 'manage_courses', 'manage_enrollments',
            'view_teacher_stats', 'view_teacher_updates', 'view_institute_stats', 'view_institute_updates',
            'send_messages', 'view_notifications', 'manage_chat', 'send_announcements',
            'manage_site_settings', 'manage_email_settings', 'manage_payment_settings',
            'view_system_logs', 'manage_backups', 'view_support_tickets', 'create_support_tickets',
            'manage_jobs', 'create_jobs', 'edit_jobs', 'delete_jobs', 'approve_jobs', 'feature_jobs',
            'view_job_applications', 'manage_job_applications', 'view_job_statistics', 'export_job_data'
        ];
    }

    /**
     * Get default permissions for teacher role
     */
    public static function getDefaultTeacherPermissions(): array
    {
        return [
            'view_dashboard', 'view_statistics',
            'view_profile', 'edit_profile',
            'view_sessions', 'book_sessions', 'create_sessions', 'manage_sessions', 'manage_schedule',
            'add_subject', 'create_exam_packages',
            'manage_courses', 'create_courses', 'edit_courses', 'delete_courses', 'view_courses',
            'send_messages', 'view_notifications', 'create_support_tickets',
            'view_teachers', 'view_institutes', 'view_search', 'view_blog',
            'view_home', 'view_about', 'view_contact', 'view_help'
        ];
    }

    /**
     * Get default permissions for student role
     */
    public static function getDefaultStudentPermissions(): array
    {
        return [
            'view_dashboard',
            'view_profile', 'edit_profile',
            'view_progress', 'manage_favorites',
            'take_exams', 'view_results',
            'send_messages', 'view_notifications', 'create_support_tickets',
            'view_teachers', 'view_institutes', 'view_search', 'view_blog',
            'view_home', 'view_about', 'view_contact', 'view_help'
        ];
    }

    /**
     * Get default permissions for institute role
     */
    public static function getDefaultInstitutePermissions(): array
    {
        return [
            'view_dashboard', 'view_statistics', 'view_analytics',
            'view_profile', 'edit_profile',
            'view_sessions', 'book_sessions', 'create_sessions', 'manage_sessions', 'manage_schedule',
            'manage_students', 'manage_branches', 'manage_teachers', 'manage_courses', 'create_courses', 'edit_courses', 'delete_courses', 'view_courses', 'manage_enrollments',
            'view_teacher_stats', 'view_institute_stats', 'view_institute_updates',
            'send_messages', 'view_notifications', 'send_announcements', 'create_support_tickets',
            'view_teachers', 'view_institutes', 'view_search', 'view_blog',
            'view_home', 'view_about', 'view_contact', 'view_help'
        ];
    }
}
