<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Review extends Model
{
    use HasFactory;

    protected $table = 'reviews';

    protected $fillable = [
        'user_id',
        'reviewable_type',
        'reviewable_id',
        'rating',
        'comment',
        'title',
        'tags',
        'is_verified',
        'is_helpful',
        'helpful_count',
        'status',
        'reviewed_at',
        'reply',
        'replied_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'is_verified' => 'boolean',
        'is_helpful' => 'boolean',
        'rating' => 'integer',
        'helpful_count' => 'integer',
        'reviewed_at' => 'datetime',
    ];

    protected $attributes = [
        'is_verified' => false,
        'is_helpful' => false,
        'helpful_count' => 0,
        'status' => 'published',
    ];

    /**
     * Get the attributes that should be visible in arrays.
     */
    protected $visible = [
        'id',
        'user_id',
        'reviewable_type',
        'reviewable_id',
        'rating',
        'comment',
        'title',
        'tags',
        'is_verified',
        'is_helpful',
        'helpful_count',
        'status',
        'reviewed_at',
        'created_at',
        'updated_at',
        'user', // Include user relationship
    ];
    
    /**
     * Append custom accessors to arrays
     */
    protected $appends = [
        'reviewable_type_name',
        'time_ago',
    ];

    /**
     * Get the user who wrote the review
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the reviewable entity (teacher, institute, branch)
     */
    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope for published reviews
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope for verified reviews
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope for helpful reviews
     */
    public function scopeHelpful($query)
    {
        return $query->where('is_helpful', true);
    }

    /**
     * Get the average rating for a reviewable entity
     */
    public static function getAverageRating($reviewable)
    {
        return static::where('reviewable_type', get_class($reviewable))
            ->where('reviewable_id', $reviewable->id)
            ->where('status', 'published')
            ->avg('rating') ?? 0;
    }

    /**
     * Get the total reviews count for a reviewable entity
     */
    public static function getReviewsCount($reviewable)
    {
        return static::where('reviewable_type', get_class($reviewable))
            ->where('reviewable_id', $reviewable->id)
            ->where('status', 'published')
            ->count();
    }

    /**
     * Check if user has already reviewed this entity
     */
    public static function hasUserReviewed($user, $reviewable)
    {
        return static::where('user_id', $user->id)
            ->where('reviewable_type', get_class($reviewable))
            ->where('reviewable_id', $reviewable->id)
            ->exists();
    }

    /**
     * Get reviews for a specific user (reviews written by the user)
     */
    public static function getUserReviews($userId, $limit = null)
    {
        $query = static::where('user_id', $userId)
            ->with(['reviewable', 'user'])
            ->latest();
            
        return $limit ? $query->limit($limit)->get() : $query->get();
    }

    /**
     * Get reviews for a specific reviewable entity
     */
    public static function getReviewsForEntity($reviewable, $limit = null)
    {
        $query = static::where('reviewable_type', get_class($reviewable))
            ->where('reviewable_id', $reviewable->id)
            ->where('status', 'published')
            ->with(['user'])
            ->latest();
            
        return $limit ? $query->limit($limit)->get() : $query->get();
    }

    /**
     * Get rating distribution for a reviewable entity
     */
    public static function getRatingDistribution($reviewable)
    {
        return static::where('reviewable_type', get_class($reviewable))
            ->where('reviewable_id', $reviewable->id)
            ->where('status', 'published')
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->orderBy('rating', 'desc')
            ->get();
    }

    /**
     * Get recent reviews with pagination
     */
    public static function getRecentReviews($limit = 10)
    {
        return static::with(['user', 'reviewable'])
            ->where('status', 'published')
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get helpful reviews for a reviewable entity
     */
    public static function getHelpfulReviews($reviewable, $limit = 5)
    {
        return static::where('reviewable_type', get_class($reviewable))
            ->where('reviewable_id', $reviewable->id)
            ->where('status', 'published')
            ->where('is_helpful', true)
            ->with(['user'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get verified reviews for a reviewable entity
     */
    public static function getVerifiedReviews($reviewable, $limit = 5)
    {
        return static::where('reviewable_type', get_class($reviewable))
            ->where('reviewable_id', $reviewable->id)
            ->where('status', 'published')
            ->where('is_verified', true)
            ->with(['user'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Scope for reviews by rating
     */
    public function scopeByRating($query, $rating)
    {
        return $query->where('rating', $rating);
    }

    /**
     * Scope for reviews by date range
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope for reviews with comments
     */
    public function scopeWithComments($query)
    {
        return $query->whereNotNull('comment')->where('comment', '!=', '');
    }

    /**
     * Get the reviewable entity name
     */
    public function getReviewableNameAttribute()
    {
        if ($this->reviewable) {
            if (method_exists($this->reviewable, 'name')) {
                return $this->reviewable->name;
            } elseif (method_exists($this->reviewable, 'title')) {
                return $this->reviewable->title;
            } elseif (isset($this->reviewable->display_name)) {
                return $this->reviewable->display_name;
            }
        }
        return 'Unknown';
    }

    /**
     * Get the reviewable entity type (formatted name)
     */
    public function getReviewableTypeNameAttribute()
    {
        // Access the raw attribute to avoid circular reference
        $type = $this->attributes['reviewable_type'] ?? null;
        if ($type) {
            return strtolower(class_basename($type));
        }
        return 'unknown';
    }

    /**
     * Get formatted rating stars
     */
    public function getStarsAttribute()
    {
        $stars = '';
        for ($i = 1; $i <= 5; $i++) {
            $stars .= $i <= $this->rating ? '★' : '☆';
        }
        return $stars;
    }

    /**
     * Get time since review was created
     */
    public function getTimeAgoAttribute()
    {
        return $this->created_at->diffForHumans();
    }
}
