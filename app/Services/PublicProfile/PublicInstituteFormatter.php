<?php

namespace App\Services\PublicProfile;

use App\Helpers\PublicProfileOptionsMapper;
use App\Models\Institute;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PublicInstituteFormatter
{
    /**
     * Format institute for list view.
     */
    public function listItem(Institute $institute): array
    {
        return [
            'id'             => $institute->id,
            'name'           => $institute->display_name,
            'slug'           => $institute->slug,
            'description'    => Str::limit($institute->description ?? $institute->specialization ?? '', 150),
            'logo_url'       => $this->storageUrl($institute->logo),
            'city'           => $institute->city ?? $institute->branch_city,
            'state'          => $institute->state ?? $institute->branch_state,
            'rating'         => (float) ($institute->rating ?? 0),
            'teachers_count' => $institute->teachers_count ?? $institute->teachers()->count(),
            'subjects'       => $this->safeRelation($institute, 'subjects')
                ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'slug' => $s->slug])
                ->take(5)
                ->all(),
            'verified'       => (bool) $institute->verified,
            'is_featured'    => (bool) $institute->is_featured,
        ];
    }

    /**
     * Format institute for show/detail view.
     */
    public function show(Institute $institute): array
    {
        $info    = $institute->user?->profile?->instituteInfo;
        $options = PublicProfileOptionsMapper::mapInstituteOptions($institute, $info);

        return [
            'id'               => $institute->id,
            'slug'             => $institute->slug,
            'user'             => $this->formatUser($institute),
            'profile'          => $this->formatProfile($institute, $options),
            'social'           => $this->formatSocialLinks($institute->user?->profile?->socialLinks),
            'rating'           => (float) ($institute->rating ?? 0),
            'counts'           => $this->formatCounts($institute),
            'subjects'         => $this->safeRelation($institute, 'subjects')
                ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'slug' => $s->slug, 'category' => $s->category])
                ->all(),
            'exams'            => $this->safeRelation($institute, 'exams')
                ->map(fn ($e) => ['id' => $e->id, 'name' => $e->name, 'slug' => $e->slug])
                ->all(),
            'branches'         => $this->formatBranches($institute),
            'teachers_preview' => $this->formatTeachersPreview($institute),
            'reviews'          => $this->formatReviews($institute),
            'verified'         => (bool) $institute->verified,
            'is_featured'      => (bool) $institute->is_featured,
        ];
    }

    // ─── Private helpers (DRY) ───────────────────────────────────

    private function formatUser(Institute $institute): array
    {
        return [
            'id'    => $institute->user?->id,
            'name'  => $institute->user?->name,
            'email' => $institute->user?->email,
        ];
    }

    private function formatProfile(Institute $institute, array $options): array
    {
        return [
            'name'                 => $institute->display_name,
            'description'          => $institute->description,
            'specialization'       => $institute->specialization,
            'affiliation'          => $institute->affiliation,
            'registration_number'  => $institute->registration_number,
            'website'              => $institute->website,
            'contact_person'       => $institute->contact_person,
            'contact_phone'        => $institute->contact_phone,
            'contact_email'        => $institute->contact_email,
            'address'              => $institute->address ?? $institute->branch_address,
            'city'                 => $institute->city ?? $institute->branch_city,
            'state'                => $institute->state ?? $institute->branch_state,
            'pincode'              => $institute->pincode ?? $institute->branch_pincode,
            'latitude'             => $institute->latitude ? (float) $institute->latitude : null,
            'longitude'            => $institute->longitude ? (float) $institute->longitude : null,
            'established_year'     => $institute->established_year,
            'institute_type'       => $options['institute_type'],
            'institute_category'   => $options['institute_category'],
            'establishment_year'   => $options['establishment_year'],
            'total_students'       => $institute->total_students,
            'total_students_range' => $options['total_students'],
            'total_teachers_range' => $options['total_teachers'],
            'logo_url'             => $this->storageUrl($institute->logo),
            'gallery_urls'         => $this->galleryUrls($institute->gallery_images),
            'facilities'           => $institute->facilities ?? [],
        ];
    }

    private function formatCounts(Institute $institute): array
    {
        return [
            'teachers' => $institute->teachers_count ?? $institute->teachers()->count(),
            'branches' => $institute->child_branches_count ?? $institute->childBranches()->count(),
            'subjects' => $institute->subjects_count ?? $institute->subjects()->count(),
        ];
    }

    private function formatBranches(Institute $institute): array
    {
        return $this->safeRelation($institute, 'childBranches')
            ->map(fn ($b) => [
                'id'      => $b->id,
                'name'    => $b->branch_name ?: $b->institute_name,
                'address' => $b->branch_address,
                'city'    => $b->branch_city,
                'state'   => $b->branch_state,
                'phone'   => $b->branch_phone,
                'email'   => $b->branch_email,
            ])->all();
    }

    private function formatTeachersPreview(Institute $institute): array
    {
        return $this->safeRelation($institute, 'teachers')
            ->map(fn ($t) => [
                'id'   => $t->id,
                'name' => $t->user?->name ?? 'Teacher',
            ])->all();
    }

    private function formatReviews(Institute $institute): array
    {
        return $this->safeRelation($institute, 'reviews')
            ->map(fn ($r) => [
                'id'            => $r->id,
                'rating'        => $r->rating,
                'comment'       => $r->comment,
                'reviewer_name' => $r->reviewer_name ?? $r->user?->name ?? 'Anonymous',
                'created_at'    => $r->created_at?->toIso8601String(),
            ])->all();
    }

    private function formatSocialLinks($socialLinks): ?array
    {
        if (!$socialLinks || ($socialLinks instanceof Collection && $socialLinks->isEmpty())) {
            return null;
        }

        $link = $socialLinks instanceof Collection ? $socialLinks->first() : $socialLinks;
        if (!$link) {
            return null;
        }

        $fields = [
            'facebook_url', 'twitter_url', 'instagram_url', 'linkedin_url',
            'youtube_url', 'tiktok_url', 'telegram_username', 'discord_username',
            'github_url', 'portfolio_url', 'blog_url', 'website_url',
        ];

        $data = collect($fields)
            ->mapWithKeys(fn ($f) => [$f => $link->{$f} ?? null])
            ->filter()
            ->all();

        return $data ?: null;
    }

    // ─── Shared utilities ────────────────────────────────────────

    private function safeRelation(Institute $institute, string $relation): Collection
    {
        return $institute->relationLoaded($relation)
            ? $institute->getRelation($relation) ?? collect()
            : collect();
    }

    private function storageUrl(?string $path): ?string
    {
        return $path ? storage_file_url($path) : null;
    }

    private function galleryUrls(mixed $images): array
    {
        if (empty($images) || !is_array($images)) {
            return [];
        }

        return collect($images)
            ->filter(fn ($img) => is_string($img) && $img !== '')
            ->map(fn ($img) => storage_file_url($img))
            ->values()
            ->all();
    }
}
