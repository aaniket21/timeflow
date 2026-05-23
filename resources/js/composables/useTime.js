/**
 * PRD §6 — useTime composable.
 *
 * Provides timezone-aware date formatting for all Vue components.
 * Replaces ALL raw `new Date()` / string timestamp usage.
 *
 * Usage:
 *   const { format, todayDate, isToday, duration, relative, ... } = useTime()
 *   format(session.started_at, 'HH:mm')          → '14:30'
 *   todayDate()                                    → '2026-05-21'
 *   isToday('2026-05-21')                          → true
 *   duration(3600)                                 → '1h 0m'
 *   relative(session.started_at)                   → '2 hours ago'
 *   now()                                          → dayjs in user tz
 *   startOfWeek()                                  → dayjs Monday 00:00
 *   formatDate(iso, 'MMM D')                       → 'May 21'
 *   weekdayShort(iso)                              → 'Mon'
 *   dayOfWeekIndex()                               → 0–6 (Mon=0)
 *   daysUntil(iso)                                 → integer days
 */
import dayjs from '../plugins/time'

export function useTime() {
  const userTimezone = window.__APP_TIMEZONE || 'UTC'

  /**
   * Get a dayjs instance for "now" in the user's timezone.
   */
  function now() {
    return dayjs().tz(userTimezone)
  }

  /**
   * Format a timestamp in the user's timezone.
   */
  function format(timestamp, fmt = 'YYYY-MM-DD HH:mm') {
    if (!timestamp) return ''
    return dayjs(timestamp).tz(userTimezone).format(fmt)
  }

  /**
   * Format a date string for display (e.g. 'May 21', 'Mon, May 21').
   */
  function formatDate(timestamp, fmt = 'MMM D') {
    if (!timestamp) return ''
    return dayjs(timestamp).tz(userTimezone).format(fmt)
  }

  /**
   * Get today's date string in the user's timezone.
   */
  function todayDate() {
    return dayjs().tz(userTimezone).format('YYYY-MM-DD')
  }

  /**
   * Check if a date string is today in the user's timezone.
   */
  function isToday(dateString) {
    if (!dateString) return false
    return dayjs(dateString).tz(userTimezone).format('YYYY-MM-DD') === todayDate()
  }

  /**
   * Check if a date string is yesterday in the user's timezone.
   */
  function isYesterday(dateString) {
    if (!dateString) return false
    const yesterday = dayjs().tz(userTimezone).subtract(1, 'day').format('YYYY-MM-DD')
    return dayjs(dateString).tz(userTimezone).format('YYYY-MM-DD') === yesterday
  }

  /**
   * Check if a date string is in the future in the user's timezone.
   */
  function isFuture(dateString) {
    if (!dateString) return false
    return dayjs(dateString).tz(userTimezone).isAfter(dayjs().tz(userTimezone), 'day')
  }

  /**
   * Format duration in seconds to human-readable string.
   */
  function duration(seconds) {
    if (!seconds || seconds <= 0) return '0m'
    const h = Math.floor(seconds / 3600)
    const m = Math.floor((seconds % 3600) / 60)
    if (h > 0) return `${h}h ${m}m`
    return `${m}m`
  }

  /**
   * Format duration in seconds to HH:MM:SS format.
   */
  function durationHMS(seconds) {
    if (!seconds || seconds <= 0) return '00:00:00'
    const h = String(Math.floor(seconds / 3600)).padStart(2, '0')
    const m = String(Math.floor((seconds % 3600) / 60)).padStart(2, '0')
    const s = String(seconds % 60).padStart(2, '0')
    return `${h}:${m}:${s}`
  }

  /**
   * Get relative time string (e.g., '2 hours ago').
   */
  function relative(timestamp) {
    if (!timestamp) return ''
    return dayjs(timestamp).tz(userTimezone).fromNow()
  }

  /**
   * Get a dayjs instance for a timestamp in the user's timezone.
   */
  function parse(timestamp) {
    return dayjs(timestamp).tz(userTimezone)
  }

  /**
   * Get the start of today in user's timezone as ISO string (for API queries).
   */
  function todayStartIso() {
    return dayjs().tz(userTimezone).startOf('day').toISOString()
  }

  /**
   * Get the end of today in user's timezone as ISO string.
   */
  function todayEndIso() {
    return dayjs().tz(userTimezone).endOf('day').toISOString()
  }

  /**
   * Get start of week (Monday) for a given date or today.
   * Returns a dayjs instance in the user's timezone.
   */
  function startOfWeek(dateOrString) {
    const d = dateOrString ? dayjs(dateOrString).tz(userTimezone) : dayjs().tz(userTimezone)
    const dayIndex = d.day() // 0=Sun, 1=Mon, ...
    const mondayOffset = dayIndex === 0 ? 6 : dayIndex - 1
    return d.subtract(mondayOffset, 'day').startOf('day')
  }

  /**
   * Get the current hour in the user's timezone.
   */
  function currentHour() {
    return dayjs().tz(userTimezone).hour()
  }

  /**
   * Get today's day of week index (Mon=0, Tue=1, ..., Sun=6).
   */
  function dayOfWeekIndex() {
    const d = dayjs().tz(userTimezone).day() // 0=Sun
    return d === 0 ? 6 : d - 1
  }

  /**
   * Get abbreviated weekday (Mon, Tue, etc.) for a date string.
   */
  function weekdayShort(dateString) {
    if (!dateString) return ''
    return dayjs(dateString).tz(userTimezone).format('ddd')
  }

  /**
   * Get single-letter weekday label (M, T, W, etc.) for a date string.
   */
  function weekdayLetter(dateString) {
    if (!dateString) return ''
    return dayjs(dateString).tz(userTimezone).format('dd').charAt(0)
  }

  /**
   * Calculate days remaining until a future date.
   */
  function daysUntil(dateString) {
    if (!dateString) return 0
    const target = dayjs(dateString).tz(userTimezone).startOf('day')
    const today = dayjs().tz(userTimezone).startOf('day')
    return Math.max(0, target.diff(today, 'day'))
  }

  /**
   * Get the UNIX timestamp (ms) for a given ISO string in user's timezone.
   */
  function toTimestamp(isoString) {
    if (!isoString) return 0
    return dayjs(isoString).tz(userTimezone).valueOf()
  }

  /**
   * Format a time-only string (e.g. '2:30 PM') from an ISO timestamp.
   */
  function formatTime(isoString) {
    if (!isoString) return ''
    return dayjs(isoString).tz(userTimezone).format('h:mm A')
  }

  /**
   * Get the number of days in a month for a given date.
   */
  function daysInMonth(dateOrString) {
    const d = dateOrString ? dayjs(dateOrString).tz(userTimezone) : dayjs().tz(userTimezone)
    return d.daysInMonth()
  }

  /**
   * Get a date label for session grouping (Today, Yesterday, or 'May 21').
   */
  function sessionGroupLabel(isoString) {
    if (!isoString) return ''
    const d = dayjs(isoString).tz(userTimezone)
    if (isToday(isoString)) return 'Today'
    if (isYesterday(isoString)) return 'Yesterday'
    return d.format('MMM D')
  }

  return {
    now,
    format,
    formatDate,
    formatTime,
    todayDate,
    isToday,
    isYesterday,
    isFuture,
    duration,
    durationHMS,
    relative,
    parse,
    todayStartIso,
    todayEndIso,
    startOfWeek,
    currentHour,
    dayOfWeekIndex,
    weekdayShort,
    weekdayLetter,
    daysUntil,
    daysInMonth,
    toTimestamp,
    sessionGroupLabel,
    dayjs,
  }
}
