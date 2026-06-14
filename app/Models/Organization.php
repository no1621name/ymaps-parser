<?php

namespace App\Models;

use App\Enums\OrganizationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'name',
        'avg_rating',
        'reviews_count',
        'ratings_count',
        'status',
        'error_message',
        'parsed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrganizationStatus::class,
            'avg_rating' => 'decimal:2',
            'parsed_at' => 'datetime',
        ];
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function latestReview(): HasMany
    {
        return $this->hasMany(Review::class)->latest('published_at');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', OrganizationStatus::Pending);
    }

    public function scopeNeedsUpdate(Builder $query, int $minutes = 60): Builder
    {
        return $query->where(function (Builder $q) use ($minutes) {
            $q->where('status', OrganizationStatus::Done)
                ->where(function (Builder $q) use ($minutes) {
                    $q->whereNull('parsed_at')
                        ->orWhere('parsed_at', '<', now()->subMinutes($minutes));
                });
        });
    }

    public function isParsingAllowed(): bool
    {
        if ($this->status === OrganizationStatus::Pending) {
            return true;
        }

        if ($this->status === OrganizationStatus::Parsing) {
            return false;
        }

        if ($this->status === OrganizationStatus::Done) {
            $rateLimit = config('yandex_maps.parsing.rate_limit_minutes', 60);

            return $this->parsed_at === null
                || $this->parsed_at->lessThan(now()->subMinutes($rateLimit));
        }

        return true;
    }

    public function markAsParsing(): void
    {
        $this->update(['status' => OrganizationStatus::Parsing]);
    }

    public function markAsDone(): void
    {
        $this->update([
            'status' => OrganizationStatus::Done,
            'parsed_at' => now(),
            'error_message' => null,
        ]);
    }

    public function markAsFailed(string $message): void
    {
        $this->update([
            'status' => OrganizationStatus::Failed,
            'error_message' => $message,
        ]);
    }
}
