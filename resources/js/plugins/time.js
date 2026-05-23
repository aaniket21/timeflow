/**
 * PRD §6 — dayjs timezone plugin.
 *
 * Configures dayjs with UTC, timezone, and relativeTime plugins.
 * Sets the default timezone from window.__APP_TIMEZONE (injected in Blade).
 *
 * Import this once in app.js: import './plugins/time'
 */
import dayjs from 'dayjs'
import utc from 'dayjs/plugin/utc'
import timezone from 'dayjs/plugin/timezone'
import relativeTime from 'dayjs/plugin/relativeTime'
import duration from 'dayjs/plugin/duration'
import isSameOrBefore from 'dayjs/plugin/isSameOrBefore'
import isSameOrAfter from 'dayjs/plugin/isSameOrAfter'
import isToday from 'dayjs/plugin/isToday'
import weekOfYear from 'dayjs/plugin/weekOfYear'

dayjs.extend(utc)
dayjs.extend(timezone)
dayjs.extend(relativeTime)
dayjs.extend(duration)
dayjs.extend(isSameOrBefore)
dayjs.extend(isSameOrAfter)
dayjs.extend(isToday)
dayjs.extend(weekOfYear)

// Set default timezone from server-injected value
const appTimezone = window.__APP_TIMEZONE || 'UTC'
dayjs.tz.setDefault(appTimezone)

export default dayjs
