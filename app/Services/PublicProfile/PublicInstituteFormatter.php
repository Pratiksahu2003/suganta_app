<?php

namespace App\Services\PublicProfile;

use App\Helpers\PublicProfileOptionsMapper;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PublicInstituteFormatter
{
    /**
     * Format institute user for list view.
     */
    public function listItem(User $user): array
    {
        $profile = $user->profile;
        $info = $profile?->instituteInfo;

        return [
            'id'                => $user->id,
            'name'              => $info?->institute_name ?? $profile?->display_name ?? $user->name,
            'slug'              => $profile?->slug,
            'description'       => Str::limit($info?->institute_description ?? $profile?->bio ?? '', 200),
            'logo_url'          => $this->storageUrl($profile?->profile_image),
            'cover_url'         => $this->storageUrl($profile?->cover_image),
            'city'              => $profile?->city,
            'state'             => $profile?->state,
            'area'              => $profile?->area,
            'pincode'           => $profile?->pincode,
            'institute_type'    => $info?->institute_type_name,
            'institute_category'=> $info?->institute_category_name,
            'establishment_year'=> $info?->establishment_year_name,
            'total_students'    => $info?->total_students_name,
            'total_teachers'    => $info?->total_teachers_name,
            'total_branches'    => $info?->total_branches,
            'facilities'        => $info?->facilities ?? [],
            'specializations'   => $info?->specializations ?? [],
            'courses_offered'   => $info?->courses_offered ?? [],
            'affiliations'      => $info?->affiliations ?? [],
            'accreditations'    => $info?->accreditations ?? [],
            'principal_name'    => $info?->principal_name,
            'website'           => $profile?->website,
            'phone_primary'     => $profile?->phone_primary,
            'whatsapp'          => $profile?->whatsapp,
            'latitude'          => $profile?->latitude ? (float) $profile->latitude : null,
            'longitude'         => $profile?->longitude ? (float) $profile->longitude : null,
            'verified'          => (bool) ($profile?->is_verified ?? false),
            'is_featured'       => (bool) ($profile?->is_featured ?? false),
        ];
    }

    /**
     * Format institute user for show/detail view.
     */
    public function show(User $user): array
    {
        $profile = $user->profile;
        $info = $profile?->instituteInfo;
        $options = PublicProfileOptionsMapper::mapInstituteOptions((object) [], $info);

        return [
            'id'      => $user->id,
            'slug'    => $profile?->slug,
            'user'    => $this->formatUser($user),
            'profile' => $this->formatProfile($profile, $info, $options),
            'portfolio'=> $this->formatPortfolio($user->portfolio) ?? [],
            'social'  => $this->formatSocialLinks($profile),
            'counts'  => $this->formatCounts($info),
            'verified'    => (bool) ($profile?->is_verified ?? false),
            'is_featured' => (bool) ($profile?->is_featured ?? false),
        ];
    }

    // ─── Private helpers ─────────────────────────────────────────

    private function formatUser(User $user): array
    {
        return [
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $user->role,
        ];
    }

    private function formatProfile($profile, $info, array $options): array
    {
        return [
            'name'                 => $info?->institute_name ?? $profile?->display_name,
            'description'          => $info?->institute_description ?? $profile?->bio,
            'specializations'      => $info?->specializations ?? [],
            'courses_offered'      => $info?->courses_offered ?? [],
            'affiliation_number'   => $info?->affiliation_number,
            'registration_number'  => $info?->registration_number,
            'udise_code'           => $info?->udise_code,
            'aicte_code'           => $info?->aicte_code,
            'ugc_code'             => $info?->ugc_code,
            'website'              => $profile?->website,
            'principal_name'       => $info?->principal_name,
            'principal_phone'      => $info?->principal_phone,
            'principal_email'      => $info?->principal_email,
            'phone_primary'        => $profile?->phone_primary,
            'whatsapp'             => $profile?->whatsapp,
            'address'              => $this->formatAddress($profile),
            'city'                 => $profile?->city,
            'state'                => $profile?->state,
            'area'                 => $profile?->area,
            'pincode'              => $profile?->pincode,
            'latitude'             => $profile?->latitude ? (float) $profile->latitude : null,
            'longitude'            => $profile?->longitude ? (float) $profile->longitude : null,
            'institute_type'       => $options['institute_type'],
            'institute_category'   => $options['institute_category'],
            'establishment_year'   => $options['establishment_year'],
            'total_students_range' => $options['total_students'],
            'total_teachers_range' => $options['total_teachers'],
            'facilities'           => $info?->facilities ?? [],
            'accreditations'       => $info?->accreditations ?? [],
            'affiliations'         => $info?->affiliations ?? [],
            'logo_url'             => $this->storageUrl($profile?->profile_image),
            'cover_url'            => $this->storageUrl($profile?->cover_image),
            'gallery_urls'         => $this->galleryUrls($profile?->gallery_images),
        ];
    }

    private function formatCounts($info): array
    {
        return [
            'total_students' => $info?->total_students_name,
            'total_teachers' => $info?->total_teachers_name,
            'total_branches' => $info?->total_branches,
        ];
    }

    private function formatSocialLinks($profile): ?array
    {
        if (!$profile) {
            return null;
        }

        $socialLinks = $profile->relationLoaded('socialLinks') ? $profile->socialLinks : null;

        if ($socialLinks && $socialLinks instanceof Collection && $socialLinks->isNotEmpty()) {
            $link = $socialLinks->first();
            $fields = [
                'facebook_url', 'twitter_url', 'instagram_url', 'linkedin_url',
                'youtube_url', 'tiktok_url', 'telegram_username', 'discord_username',
                'github_url', 'portfolio_url', 'blog_url', 'website_url',
            ];
            $data = collect($fields)
                ->mapWithKeys(fn ($f) => [$f => $link->{$f} ?? null])
                ->filter()
                ->all();
            if (!empty($data)) {
                return $data;
            }
        }

        $inline = $profile->social_media_links ?? [];
        return !empty($inline) ? $inline : null;
    }

    private function formatAddress($profile): ?string
    {
        if (!$profile) {
            return null;
        }
        $parts = array_filter([
            $profile->address_line_1,
            $profile->address_line_2,
            $profile->area,
        ]);
        return $parts ? implode(', ', $parts) : null;
    }

    // ─── Shared utilities ────────────────────────────────────────

    private function storageUrl(?string $path): ?string
    {
        return ($path && trim($path) !== '') ? storage_file_url($path) : null;
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


      /**
     * Format portfolio for public display. Returns null if no portfolio or status is not published.
     */
    private function formatPortfolio($portfolio): ?array
    {
        if (!$portfolio || $portfolio->status !== 'published') {
            return null;
        }

        return [
            'id' => $portfolio->id,
            'title' => $portfolio->title,
            'description' => $portfolio->description,
            'images' => $this->formatPortfolioImages($portfolio->images ?? []),
            'files' => $this->formatPortfolioFiles($portfolio->files ?? []),
            'category' => $portfolio->category,
            'categories_array' => $portfolio->categories_array ?? [],
            'tags' => $portfolio->tags,
            'tags_array' => $portfolio->tags_array ?? [],
            'url' => $portfolio->url,
            'is_featured' => (bool) $portfolio->is_featured,
            'order' => (int) ($portfolio->order ?? 0),
        ];
    }

    private function formatPortfolioImages(array $images): array
    {
        return array_map(fn (string $path) => [
            'path' => $path,
            'url' => storage_file_url($path),
        ], $images);
    }

    private function formatPortfolioFiles(array $files): array
    {
        return array_map(fn (string $path) => [
            'path' => $path,
            'url' => storage_file_url($path),
            'name' => basename($path),
        ], $files);
    }
}
