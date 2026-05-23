<?php

namespace App\Helpers;

use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

/**
 * PRD §6 — Unified Timezone System.
 *
 * Single source of truth for "what is today?" and "what are the UTC boundaries
 * of today?" for a given user's timezone.
 *
 * ALL date logic in the app MUST flow through this class.
 * Never use `Carbon::today()`, `now()->toDateString()`, or `new Date()` directly.
 *
 * Usage:
 *   $today = TimeHelper::todayForUser($user);                    // '2026-05-21'
 *   [$start, $end] = TimeHelper::todayBoundsUtc($user);          // UTC timestamps
 *   $weekStart = TimeHelper::startOfWeekUtc($user);              // Monday 00:00 UTC-adjusted
 */
class TimeHelper
{
    /**
     * Get today's date string in the user's local timezone.
     * This is what should be stored in `last_active_date` and `habit_logs.date`.
     */
    public static function todayForUser(User $user): string
    {
        return CarbonImmutable::now($user->timezone ?? 'UTC')->toDateString();
    }

    /**
     * Get the current time in the user's timezone as a Carbon instance.
     */
    public static function nowForUser(User $user): CarbonImmutable
    {
        return CarbonImmutable::now($user->timezone ?? 'UTC');
    }

    /**
     * Get yesterday's date string in the user's local timezone.
     */
    public static function yesterdayForUser(User $user): string
    {
        return CarbonImmutable::now($user->timezone ?? 'UTC')
            ->subDay()
            ->toDateString();
    }

    /**
     * Get the UTC start and end timestamps of "today" in the user's timezone.
     * Used for querying time_sessions and other UTC-stored timestamps.
     *
     * Example: For IST (UTC+5:30) user at 11:30 PM IST on May 21:
     *   start = May 20 18:30:00 UTC
     *   end   = May 21 18:29:59 UTC
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable} [startUtc, endUtc]
     */
    public static function todayBoundsUtc(User $user): array
    {
        $tz = $user->timezone ?? 'UTC';
        $todayStart = CarbonImmutable::now($tz)->startOfDay()->utc();
        $todayEnd = CarbonImmutable::now($tz)->endOfDay()->utc();

        return [$todayStart, $todayEnd];
    }

    /**
     * Get UTC bounds for a specific date in the user's timezone.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable} [startUtc, endUtc]
     */
    public static function dateBoundsUtc(User $user, string $date): array
    {
        $tz = $user->timezone ?? 'UTC';
        $start = CarbonImmutable::parse($date, $tz)->startOfDay()->utc();
        $end = CarbonImmutable::parse($date, $tz)->endOfDay()->utc();

        return [$start, $end];
    }

    /**
     * Get UTC start of the current week (Monday) in the user's timezone.
     */
    public static function startOfWeekUtc(User $user): CarbonImmutable
    {
        $tz = $user->timezone ?? 'UTC';

        return CarbonImmutable::now($tz)->startOfWeek()->startOfDay()->utc();
    }

    /**
     * Get UTC end of the current week (Sunday) in the user's timezone.
     */
    public static function endOfWeekUtc(User $user): CarbonImmutable
    {
        $tz = $user->timezone ?? 'UTC';

        return CarbonImmutable::now($tz)->endOfWeek()->endOfDay()->utc();
    }

    /**
     * Get UTC bounds for a specific week in the user's timezone.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable} [startUtc, endUtc]
     */
    public static function weekBoundsUtc(User $user, ?string $date = null): array
    {
        $tz = $user->timezone ?? 'UTC';
        $base = $date
            ? CarbonImmutable::parse($date, $tz)
            : CarbonImmutable::now($tz);

        $start = $base->startOfWeek()->startOfDay()->utc();
        $end = $base->endOfWeek()->endOfDay()->utc();

        return [$start, $end];
    }

    /**
     * Get UTC bounds for a specific month in the user's timezone.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable} [startUtc, endUtc]
     */
    public static function monthBoundsUtc(User $user, ?string $date = null): array
    {
        $tz = $user->timezone ?? 'UTC';
        $base = $date
            ? CarbonImmutable::parse($date, $tz)
            : CarbonImmutable::now($tz);

        $start = $base->startOfMonth()->startOfDay()->utc();
        $end = $base->endOfMonth()->endOfDay()->utc();

        return [$start, $end];
    }

    /**
     * Check if two date strings represent consecutive days.
     * Used by StreakService to determine if a streak continues.
     */
    public static function isConsecutiveDay(string $dateA, string $dateB): bool
    {
        return Carbon::parse($dateA)->diffInDays(Carbon::parse($dateB)) === 1;
    }

    /**
     * Validate an IANA timezone string.
     */
    public static function isValidTimezone(string $timezone): bool
    {
        return in_array($timezone, timezone_identifiers_list(), true);
    }
}
