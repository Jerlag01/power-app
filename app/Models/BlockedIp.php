<?php

namespace App\Models;

use Database\Factories\BlockedIpFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * An IP address or range (CIDR, e.g. 203.0.113.0/24) that may not visit the public website.
 */
#[Fillable(['ip', 'reason', 'expires_at', 'is_active', 'user_id'])]
class BlockedIp extends Model
{
    /** @use HasFactory<BlockedIpFactory> */
    use HasFactory;

    public const CACHE_KEY = 'blocked-ips';

    /** Narrowest prefix that may be blocked, so a typo cannot lock out a whole provider or everyone. */
    public const MIN_PREFIX = ['v4' => 16, 'v6' => 32];

    protected static function booted(): void
    {
        static::saving(function (BlockedIp $blockedIp): void {
            $blockedIp->ip = static::normalize((string) $blockedIp->ip);
        });

        // Counting hits must not throw the rule list out of the cache on every blocked request.
        static::saved(function (BlockedIp $blockedIp): void {
            if ($blockedIp->wasRecentlyCreated || $blockedIp->wasChanged(['ip', 'expires_at', 'is_active'])) {
                Cache::forget(self::CACHE_KEY);
            }
        });

        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The active rule that blocks this visitor, if any. The rule list is cached; expiry is checked here.
     */
    public static function matching(?string $ip): ?self
    {
        if (blank($ip)) {
            return null;
        }

        $rules = Cache::rememberForever(self::CACHE_KEY, fn (): array => static::query()
            ->where('is_active', true)
            ->get(['id', 'ip', 'expires_at'])
            ->map(fn (BlockedIp $rule): array => ['id' => $rule->id, 'ip' => $rule->ip, 'expires_at' => $rule->expires_at?->getTimestamp()])
            ->all());

        foreach ($rules as $rule) {
            if (($rule['expires_at'] === null || $rule['expires_at'] > now()->getTimestamp()) && IpUtils::checkIp($ip, $rule['ip'])) {
                return static::find($rule['id']);
            }
        }

        return null;
    }

    public function registerHit(): void
    {
        $this->forceFill(['hits' => $this->hits + 1, 'last_hit_at' => now()])->saveQuietly();
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isBlocking(): bool
    {
        return $this->is_active && ! $this->isExpired();
    }

    /**
     * Whether this rule covers the given address (used to warn admins about their own IP).
     */
    public function covers(?string $ip): bool
    {
        return filled($ip) && IpUtils::checkIp($ip, $this->ip);
    }

    /**
     * Lower-case, trimmed and with a CIDR range reduced to its network address (10.0.0.7/24 → 10.0.0.0/24).
     */
    public static function normalize(string $value): string
    {
        $value = strtolower(trim($value));

        if (! str_contains($value, '/')) {
            return filter_var($value, FILTER_VALIDATE_IP) ? (string) inet_ntop((string) inet_pton($value)) : $value;
        }

        [$address, $prefix] = explode('/', $value, 2);
        $packed = filter_var($address, FILTER_VALIDATE_IP) ? inet_pton($address) : false;

        if ($packed === false || ! ctype_digit($prefix)) {
            return $value;
        }

        $bits = (int) $prefix;
        $bytes = str_split($packed);

        foreach ($bytes as $index => $byte) {
            $keep = max(0, min(8, $bits - $index * 8));
            $bytes[$index] = chr(ord($byte) & (0xFF << (8 - $keep)) & 0xFF);
        }

        return inet_ntop(implode('', $bytes)).'/'.$bits;
    }

    /**
     * Validation for the admin form: a single IPv4/IPv6 address or a CIDR range that is not too wide.
     */
    public static function validationError(string $value): ?string
    {
        $value = trim($value);

        if (! str_contains($value, '/')) {
            return filter_var($value, FILTER_VALIDATE_IP) ? null : __('blocked_ips.validation.invalid');
        }

        [$address, $prefix] = explode('/', $value, 2);
        $version = filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? 'v4' : (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? 'v6' : null);
        $max = $version === 'v4' ? 32 : 128;

        if ($version === null || ! ctype_digit($prefix) || (int) $prefix > $max) {
            return __('blocked_ips.validation.invalid');
        }

        if ((int) $prefix < self::MIN_PREFIX[$version]) {
            return __('blocked_ips.validation.too_wide', ['prefix' => self::MIN_PREFIX[$version]]);
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
            'last_hit_at' => 'datetime',
        ];
    }
}
