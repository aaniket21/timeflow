<script setup>
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';
import AppShell from '../Layouts/AppShell.vue';
import { useTime } from '../composables/useTime';

const time = useTime();

const props = defineProps({
  navigation: {
    type: Object,
    default: () => ({ sections: [] }),
  },
});

const tab = ref('daily');
const userProfile = ref({ daily_goal_hours: 6 });

const dailyDate = ref(time.now());
const weeklyStartDate = ref(time.startOfWeek());
const monthlyDate = ref(time.now());

const dailySummary = ref({
  total_seconds: 0,
  focus_sessions: 0,
  avg_session_seconds: 0,
  longest_session_seconds: 0,
});
const hourlyBreakdown = ref([]);
const dailySessions = ref([]);

const weeklySummary = ref({
  start_date: null,
  end_date: null,
  total_seconds: 0,
  avg_daily_seconds: 0,
  best_day: null,
  worst_day: null,
  daily_totals: [],
  category_breakdown: [],
});
const weeklyDeltaSeconds = ref(0);

const monthlySummary = ref({
  month: null,
  total_seconds: 0,
  daily_totals: [],
  top_projects: [],
});

const insights = ref([]);

const dailyGoalSeconds = computed(() => {
  const hours = Number(userProfile.value.daily_goal_hours || 6);
  return Math.max(1, hours * 3600);
});

const focusScore = computed(() => {
  const avg = Number(dailySummary.value.avg_session_seconds || 0);
  if (avg <= 0) return 0;
  return Math.min(100, Math.round((avg / (25 * 60)) * 100));
});

const focusOffset = computed(() => 214 - (214 * focusScore.value) / 100);

const focusColor = computed(() => {
  if (focusScore.value >= 70) return 'var(--tf-violet)';
  if (focusScore.value >= 40) return 'var(--tf-amber)';
  return 'var(--tf-red)';
});

const dateLabel = computed(() => {
  if (tab.value === 'daily') {
    return formatDateLabel(dailyDate.value);
  }

  if (tab.value === 'weekly') {
    const start = weeklySummary.value.start_date || formatDateInput(weeklyStartDate.value);
    const end = weeklySummary.value.end_date || formatDateInput(addDays(weeklyStartDate.value, 6));
    return formatRangeLabel(start, end);
  }

  const month = monthlySummary.value.month || formatMonthInput(monthlyDate.value);
  return formatMonthLabel(month);
});

const hourlyBars = computed(() => {
  const fallback = Array.from({ length: 24 }, (_, hour) => ({ hour, total_seconds: 0 }));
  const data = hourlyBreakdown.value.length ? hourlyBreakdown.value : fallback;
  const max = Math.max(1, ...data.map((entry) => entry.total_seconds));
  return data.map((entry) => ({
    hour: entry.hour,
    percent: Math.round((entry.total_seconds / max) * 100),
  }));
});

const weeklyBars = computed(() => {
  const totals = weeklySummary.value.daily_totals || [];
  if (!totals.length) return [];

  const max = Math.max(
    1,
    ...totals.map((entry) => Math.max(entry.total_seconds, entry.goal_seconds || 0)),
  );

  return totals.map((entry) => ({
    label: formatDayLabel(entry.date),
    totalPercent: Math.round((entry.total_seconds / max) * 100),
    goalPercent: Math.round(((entry.goal_seconds || 0) / max) * 100),
  }));
});

const weeklyDeltaLabel = computed(() => {
  const hours = weeklyDeltaSeconds.value / 3600;
  const sign = hours >= 0 ? '+' : '';
  return `${sign}${hours.toFixed(1)}h`;
});

const weeklyFocusAverage = computed(() => {
  if (dailyGoalSeconds.value <= 0) return 0;
  return Math.min(100, Math.round((weeklySummary.value.avg_daily_seconds / dailyGoalSeconds.value) * 100));
});

const categoryBreakdown = computed(() => weeklySummary.value.category_breakdown || []);

const donutStyle = computed(() => {
  if (!categoryBreakdown.value.length) {
    return { background: 'conic-gradient(rgba(80, 60, 20, 0.12) 0 360deg)' };
  }

  let offset = 0;
  const segments = categoryBreakdown.value.map((item) => {
    const start = offset;
    const value = Math.max(0, Number(item.percent) || 0);
    offset += value;
    return `${item.color} ${start}% ${offset}%`;
  });

  if (offset < 100) {
    segments.push(`rgba(80, 60, 20, 0.12) ${offset}% 100%`);
  }

  return { background: `conic-gradient(${segments.join(', ')})` };
});

const bestDayLabel = computed(() => {
  if (!weeklySummary.value.best_day) return '--';
  return `${formatDayLabel(weeklySummary.value.best_day.date)} - ${formatHours(weeklySummary.value.best_day.total_seconds)}`;
});

const worstDayLabel = computed(() => {
  if (!weeklySummary.value.worst_day) return '--';
  return `${formatDayLabel(weeklySummary.value.worst_day.date)} - ${formatHours(weeklySummary.value.worst_day.total_seconds)}`;
});

const monthlyBars = computed(() => {
  const totals = monthlySummary.value.daily_totals || [];
  if (!totals.length) return [];
  const max = Math.max(1, ...totals.map((entry) => entry.total_seconds));
  return totals.map((entry) => ({
    totalPercent: Math.round((entry.total_seconds / max) * 100),
  }));
});

const monthlyProjects = computed(() => {
  const projects = monthlySummary.value.top_projects || [];
  if (!projects.length) return [];
  const max = Math.max(1, ...projects.map((entry) => entry.total_seconds));
  return projects.map((entry) => ({
    id: entry.id,
    name: entry.name,
    color: entry.color,
    hours: formatHourMinute(entry.total_seconds),
    percent: Math.round((entry.total_seconds / max) * 100),
  }));
});

const monthlyCalendar = computed(() => {
  const totals = monthlySummary.value.daily_totals || [];
  if (!totals.length) {
    const days = daysInMonth(monthlyDate.value);
    return Array.from({ length: days }, () => ({ level: 0 }));
  }

  return totals.map((entry) => ({
    date: entry.date,
    level: heatmapLevel(entry.total_seconds),
  }));
});

const monthlyInsight = computed(() => {
  if (insights.value.length) return insights.value[0].message;
  return 'No insights yet. Keep logging sessions to unlock patterns.';
});

const weeklyLegend = [
  { id: 'logged', label: 'Logged hours', color: 'var(--tf-violet)' },
  { id: 'goal', label: 'Daily goal', color: 'var(--tf-amber)' },
];

const setTab = (value) => {
  tab.value = value;
  if (value === 'daily') {
    loadDaily();
  } else if (value === 'weekly') {
    loadWeekly();
  } else {
    loadMonthly();
  }
};

const shiftDate = (direction) => {
  if (tab.value === 'daily') {
    dailyDate.value = addDays(dailyDate.value, direction);
    loadDaily();
    return;
  }

  if (tab.value === 'weekly') {
    weeklyStartDate.value = addDays(weeklyStartDate.value, direction * 7);
    loadWeekly();
    return;
  }

  monthlyDate.value = addMonths(monthlyDate.value, direction);
  loadMonthly();
};

const loadUser = async () => {
  try {
    const response = await axios.get('/api/user');
    if (response.data) {
      userProfile.value = response.data;
    }
  } catch (error) {
    console.warn('User profile fetch failed', error);
  }
};

const loadDaily = async () => {
  const date = formatDateInput(dailyDate.value);

  try {
    const response = await axios.get('/api/analytics/daily', { params: { date } });
    const data = response.data?.data;

    if (!data) {
      return;
    }

    dailySummary.value = {
      total_seconds: Number(data.total_seconds || 0),
      focus_sessions: Number(data.focus_sessions || 0),
      avg_session_seconds: Number(data.avg_session_seconds || 0),
      longest_session_seconds: Number(data.longest_session_seconds || 0),
    };

    hourlyBreakdown.value = Array.isArray(data.hourly_breakdown) ? data.hourly_breakdown : [];
    dailySessions.value = Array.isArray(data.sessions)
      ? data.sessions.map((session) => ({
        id: session.id,
        name: session.label,
        category: session.category,
        color: session.color,
        time: formatTimeLabel(session.started_at),
        duration: formatDuration(session.duration_seconds),
      }))
      : [];
  } catch (error) {
    console.warn('Daily analytics fetch failed', error);
  }
};

const loadWeekly = async () => {
  const start = formatDateInput(weeklyStartDate.value);
  const previousStart = formatDateInput(addDays(weeklyStartDate.value, -7));

  try {
    const [currentResult, previousResult] = await Promise.allSettled([
      axios.get('/api/analytics/weekly', { params: { start } }),
      axios.get('/api/analytics/weekly', { params: { start: previousStart } }),
    ]);

    if (currentResult.status === 'fulfilled') {
      const data = currentResult.value.data?.data;
      if (data) {
        weeklySummary.value = {
          start_date: data.start_date,
          end_date: data.end_date,
          total_seconds: Number(data.total_seconds || 0),
          avg_daily_seconds: Number(data.avg_daily_seconds || 0),
          best_day: data.best_day,
          worst_day: data.worst_day,
          daily_totals: Array.isArray(data.daily_totals) ? data.daily_totals : [],
          category_breakdown: Array.isArray(data.category_breakdown) ? data.category_breakdown : [],
        };
      }
    }

    if (previousResult.status === 'fulfilled') {
      const previousData = previousResult.value.data?.data;
      const previousTotal = Number(previousData?.total_seconds || 0);
      weeklyDeltaSeconds.value = Number(weeklySummary.value.total_seconds || 0) - previousTotal;
    } else {
      weeklyDeltaSeconds.value = 0;
    }
  } catch (error) {
    console.warn('Weekly analytics fetch failed', error);
  }
};

const loadMonthly = async () => {
  const month = formatMonthInput(monthlyDate.value);

  try {
    const response = await axios.get('/api/analytics/monthly', { params: { month } });
    const data = response.data?.data;
    if (!data) {
      return;
    }

    monthlySummary.value = {
      month: data.month,
      total_seconds: Number(data.total_seconds || 0),
      daily_totals: Array.isArray(data.daily_totals) ? data.daily_totals : [],
      top_projects: Array.isArray(data.top_projects) ? data.top_projects : [],
    };
  } catch (error) {
    console.warn('Monthly analytics fetch failed', error);
  }
};

const loadInsights = async () => {
  try {
    const response = await axios.get('/api/analytics/insights');
    if (Array.isArray(response.data?.data)) {
      insights.value = response.data.data;
    }
  } catch (error) {
    console.warn('Insights fetch failed', error);
  }
};

onMounted(() => {
  const params = new URLSearchParams(window.location.search);
  const requested = params.get('tab');
  if (requested === 'weekly' || requested === 'monthly') {
    tab.value = requested;
  }

  Promise.allSettled([
    loadUser(),
    loadInsights(),
    loadDaily(),
    loadWeekly(),
    loadMonthly(),
  ]);
});

function startOfWeek(date) {
  return time.startOfWeek(date);
}

function addDays(date, days) {
  return time.parse(date).add(days, 'day');
}

function addMonths(date, months) {
  return time.parse(date).add(months, 'month');
}

function daysInMonth(date) {
  return time.daysInMonth(date);
}

function formatDateInput(date) {
  return time.parse(date).format('YYYY-MM-DD');
}

function formatMonthInput(date) {
  return time.parse(date).format('YYYY-MM');
}

function formatDateLabel(date) {
  return time.format(date, 'ddd, MMM D');
}

function formatRangeLabel(start, end) {
  const startLabel = time.formatDate(start, 'MMM D');
  const endLabel = time.formatDate(end, 'MMM D');
  return `${startLabel} - ${endLabel}`;
}

function formatMonthLabel(month) {
  return time.parse(`${month}-01`).format('MMMM YYYY');
}

function formatDayLabel(dateString) {
  return time.format(dateString, 'ddd');
}

function formatTimeLabel(isoString) {
  if (!isoString) return '--';
  return time.formatTime(isoString);
}

function formatHours(seconds) {
  const value = Number(seconds || 0) / 3600;
  return `${value.toFixed(1)}h`;
}

function formatMinutes(seconds) {
  const value = Math.round(Number(seconds || 0) / 60);
  return `${value}m`;
}

function formatDuration(seconds) {
  const total = Math.max(0, Number(seconds || 0));
  const hours = String(Math.floor(total / 3600)).padStart(2, '0');
  const minutes = String(Math.floor((total % 3600) / 60)).padStart(2, '0');
  return `${hours}:${minutes}`;
}

function formatHourMinute(seconds) {
  const total = Math.max(0, Number(seconds || 0));
  const hours = Math.floor(total / 3600);
  const minutes = Math.floor((total % 3600) / 60);
  if (hours > 0) {
    return `${hours}h ${minutes}m`;
  }
  return `${minutes}m`;
}

function heatmapLevel(totalSeconds) {
  if (totalSeconds <= 0) return 0;
  if (totalSeconds < 3600) return 1;
  if (totalSeconds < 7200) return 2;
  if (totalSeconds < 14400) return 3;
  return 4;
}
</script>

<template>
  <div class="analytics-page">
    <AppShell :navigation="props.navigation">
      <div class="page-header">
        <div>
          <div class="page-title">Analytics</div>
          <div class="page-subtitle">Review daily, weekly, and monthly patterns.</div>
        </div>
        <div class="date-nav">
          <button class="nav-btn" type="button" @click="shiftDate(-1)">&lt;</button>
          <div class="tf-date-badge">{{ dateLabel }}</div>
          <button class="nav-btn" type="button" @click="shiftDate(1)">&gt;</button>
        </div>
      </div>

      <div class="tab-bar">
        <button class="tab-pill" :class="{ active: tab === 'daily' }" type="button" @click="setTab('daily')">Daily</button>
        <button class="tab-pill" :class="{ active: tab === 'weekly' }" type="button" @click="setTab('weekly')">Weekly</button>
        <button class="tab-pill" :class="{ active: tab === 'monthly' }" type="button" @click="setTab('monthly')">Monthly</button>
      </div>

      <section v-if="tab === 'daily'" class="tab-panel">
        <div class="tf-card daily-focus">
          <div class="focus-ring">
            <svg viewBox="0 0 80 80" aria-hidden="true">
              <circle class="ring-track" cx="40" cy="40" r="34" />
              <circle class="ring-fill" cx="40" cy="40" r="34" :style="{ stroke: focusColor, strokeDashoffset: focusOffset }" />
            </svg>
            <div class="focus-value" :style="{ color: focusColor }">{{ focusScore }}</div>
          </div>
          <div class="focus-stats">
            <div>
              <div class="stat-label">Total time</div>
              <div class="stat-value">{{ formatHours(dailySummary.total_seconds) }}</div>
            </div>
            <div>
              <div class="stat-label">Sessions</div>
              <div class="stat-value">{{ dailySummary.focus_sessions }}</div>
            </div>
            <div>
              <div class="stat-label">Avg session</div>
              <div class="stat-value">{{ formatMinutes(dailySummary.avg_session_seconds) }}</div>
            </div>
            <div>
              <div class="stat-label">Longest</div>
              <div class="stat-value">{{ formatHourMinute(dailySummary.longest_session_seconds) }}</div>
            </div>
          </div>
        </div>

        <div class="tf-card hourly-card">
          <div class="card-title">Hour-by-hour</div>
          <div class="hourly-bars">
            <div v-for="bar in hourlyBars" :key="bar.hour" class="hour-bar">
              <span class="bar-fill" :style="{ height: bar.percent + '%' }"></span>
            </div>
          </div>
        </div>

        <div class="tf-card sessions-card">
          <div class="card-title">Sessions</div>
          <div v-if="dailySessions.length">
            <div class="session-row" v-for="session in dailySessions" :key="session.id">
              <div class="session-left">
                <span class="color-dot" :style="{ background: session.color }"></span>
                <div>
                  <div class="session-name">{{ session.name }}</div>
                  <div class="session-meta">{{ session.category }}</div>
                </div>
              </div>
              <div class="session-time">{{ session.time }}</div>
              <div class="session-duration">{{ session.duration }}</div>
            </div>
          </div>
          <div v-else class="empty-state">No sessions logged yet.</div>
        </div>
      </section>

      <section v-else-if="tab === 'weekly'" class="tab-panel">
        <div class="stats-row">
          <div class="tf-card stat-card">
            <div class="stat-label">Total hours</div>
            <div class="stat-value">{{ formatHours(weeklySummary.total_seconds) }}</div>
          </div>
          <div class="tf-card stat-card">
            <div class="stat-label">Vs last week</div>
            <div class="stat-value">{{ weeklyDeltaLabel }}</div>
          </div>
          <div class="tf-card stat-card">
            <div class="stat-label">Focus avg</div>
            <div class="stat-value">{{ weeklyFocusAverage }}</div>
          </div>
        </div>

        <div class="tf-card chart-card">
          <div class="card-title">Daily trend</div>
          <div class="chart-placeholder">
            <div v-if="weeklyBars.length" class="weekly-chart">
              <div v-for="bar in weeklyBars" :key="bar.label" class="weekly-bar">
                <span class="weekly-goal" :style="{ bottom: bar.goalPercent + '%' }"></span>
                <span class="weekly-fill" :style="{ height: bar.totalPercent + '%' }"></span>
                <div class="weekly-label">{{ bar.label }}</div>
              </div>
            </div>
            <div v-else class="empty-state">No weekly data yet.</div>
          </div>
          <div class="legend-row">
            <div v-for="item in weeklyLegend" :key="item.id" class="legend-item">
              <span class="legend-dot" :style="{ background: item.color }"></span>
              {{ item.label }}
            </div>
          </div>
        </div>

        <div class="tf-card donut-card">
          <div class="card-title">Category breakdown</div>
          <div class="donut-layout">
            <div class="donut" :style="donutStyle"></div>
            <div class="legend-list">
              <div v-if="categoryBreakdown.length">
                <div v-for="entry in categoryBreakdown" :key="entry.name" class="legend-entry">
                  <span class="legend-dot" :style="{ background: entry.color }"></span>
                  {{ entry.name }} - {{ formatHourMinute(entry.total_seconds) }} ({{ entry.percent }}%)
                </div>
              </div>
              <div v-else class="empty-state">No category data yet.</div>
            </div>
          </div>
        </div>

        <div class="stats-row">
          <div class="tf-card stat-card">
            <div class="stat-label">Best day</div>
            <div class="stat-value">{{ bestDayLabel }}</div>
          </div>
          <div class="tf-card stat-card">
            <div class="stat-label">Worst day</div>
            <div class="stat-value">{{ worstDayLabel }}</div>
          </div>
        </div>
      </section>

      <section v-else class="tab-panel">
        <div class="tf-card chart-card">
          <div class="card-title">30-day trend</div>
          <div class="chart-placeholder tall">
            <div v-if="monthlyBars.length" class="monthly-chart">
              <div v-for="(bar, index) in monthlyBars" :key="index" class="monthly-bar">
                <span class="monthly-fill" :style="{ height: bar.totalPercent + '%' }"></span>
              </div>
            </div>
            <div v-else class="empty-state">No monthly data yet.</div>
          </div>
        </div>

        <div class="tf-card projects-card">
          <div class="card-title">Top projects</div>
          <div v-if="monthlyProjects.length">
            <div class="project-row" v-for="project in monthlyProjects" :key="project.id">
              <span class="color-dot" :style="{ background: project.color }"></span>
              <div class="project-name">{{ project.name }}</div>
              <div class="project-hours">{{ project.hours }}</div>
              <div class="project-bar"><span class="project-fill" :style="{ width: project.percent + '%' }"></span></div>
            </div>
          </div>
          <div v-else class="empty-state">No project data yet.</div>
        </div>

        <div class="tf-card calendar-card">
          <div class="card-title">Streak calendar</div>
          <div class="calendar-grid">
            <div
              v-for="(day, index) in monthlyCalendar"
              :key="day.date || index"
              class="calendar-cell"
              :class="'level-' + day.level"
            ></div>
          </div>
        </div>

        <div class="tf-card insights-card">
          <div class="card-title">Insights</div>
          <div class="insight">{{ monthlyInsight }}</div>
        </div>
      </section>
    </AppShell>
  </div>
</template>

<style scoped>
.analytics-page {
  min-height: 100vh;
  background: var(--tf-bg-page);

  color: var(--tf-text-primary);
  font-family: 'Plus Jakarta Sans', 'Segoe UI', sans-serif;
}

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 15px;
}

.page-title {
  font-size: 21px;
  font-weight: 800;
  letter-spacing: -0px;
}

.page-subtitle {
  font-size: 15px;
  color: var(--tf-text-secondary);
}

.date-nav {
  display: flex;
  align-items: center;
  gap: 10px;
}

.nav-btn {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  border: 1px solid var(--tf-border-default);
  background: var(--tf-bg-card-alt);
  color: var(--tf-text-secondary);
  cursor: pointer;
}

.tab-bar {
  display: inline-flex;
  gap: 10px;
  padding: 5px;
  background: var(--tf-bg-card-alt);
  border-radius: 1249px;
  margin-top: 15px;
  max-width: 100%;
  overflow-x: auto;
  white-space: nowrap;
}

.tab-bar::-webkit-scrollbar {
  display: none;
}

.tab-pill {
  font-size: 15px;
  font-weight: 600;
  padding: 8px 20px;
  border-radius: 1249px;
  border: none;
  background: transparent;
  color: var(--tf-text-secondary);
  cursor: pointer;
}

.tab-pill.active {
  background: var(--tf-violet);
  color: #fff;
}

.tab-panel {
  display: flex;
  flex-direction: column;
  gap: 15px;
  margin-top: 15px;
}

.daily-focus {
  display: grid;
  grid-template-columns: 150px 1fr;
  gap: 20px;
  align-items: center;
}

.focus-ring {
  width: 100px;
  height: 100px;
  position: relative;
}

.focus-ring svg {
  width: 100px;
  height: 100px;
  transform: rotate(-90deg);
}

.ring-track {
  fill: none;
  stroke: rgba(80, 60, 20, 0.12);
  stroke-width: 8;
}

.ring-fill {
  fill: none;
  stroke-width: 8;
  stroke-linecap: round;
  stroke-dasharray: 214;
  stroke-dashoffset: 60;
}

.focus-value {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 30px;
  font-weight: 700;
  font-family: 'JetBrains Mono', 'Cascadia Code', monospace;
}

.focus-stats {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 13px;
}

.stat-label {
  font-size: 13px;
  color: var(--tf-text-hint);
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.stat-value {
  font-size: 18px;
  font-weight: 700;
  font-family: 'JetBrains Mono', 'Cascadia Code', monospace;
}

.card-title {
  font-size: 15px;
  font-weight: 700;
}

.hourly-bars {
  display: grid;
  grid-template-columns: repeat(24, 1fr);
  gap: 3px;
  height: 200px;
  margin-top: 13px;
}

.hour-bar {
  background: rgba(80, 60, 20, 0.08);
  position: relative;
  border-radius: 3px;
  overflow: hidden;
}

.bar-fill {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  background: var(--tf-violet);
  border-radius: 3px 3px 0 0;
}

.session-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 15px;
  padding: 10px 0;
  border-bottom: 1px solid var(--tf-border-default);
}

.session-row:last-child {
  border-bottom: none;
}

.session-left {
  display: flex;
  align-items: center;
  gap: 10px;
}

.session-name {
  font-size: 16px;
  font-weight: 600;
}

.session-meta {
  font-size: 13px;
  color: var(--tf-text-hint);
}

.session-time,
.session-duration {
  font-size: 14px;
  font-family: 'JetBrains Mono', 'Cascadia Code', monospace;
  color: var(--tf-text-secondary);
}

.stats-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(175px, 1fr));
  gap: 13px;
}

.chart-placeholder {
  height: 225px;
  border-radius: 13px;
  margin-top: 13px;
  background: linear-gradient(180deg, rgba(124, 92, 252, 0.18), rgba(124, 92, 252, 0.04));
  display: flex;
  align-items: flex-end;
  justify-content: center;
  padding: 15px;
  position: relative;
  overflow: hidden;
}

.chart-placeholder.tall {
  height: 250px;
}

.weekly-chart {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 10px;
  width: 100%;
  height: 100%;
  align-items: end;
}

.weekly-bar {
  position: relative;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-end;
  gap: 8px;
  padding-bottom: 15px;
}

.weekly-fill {
  width: 100%;
  max-width: 23px;
  background: var(--tf-violet);
  border-radius: 5px 5px 0 0;
}

.weekly-goal {
  position: absolute;
  left: 0;
  right: 0;
  height: 3px;
  background: var(--tf-amber);
  opacity: 0.9;
}

.weekly-label {
  font-size: 11px;
  color: var(--tf-text-hint);
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.legend-row {
  display: flex;
  gap: 15px;
  margin-top: 10px;
  font-size: 13px;
  color: var(--tf-text-secondary);
}

.legend-item {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.legend-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
}

.donut-layout {
  display: flex;
  align-items: center;
  gap: 20px;
  margin-top: 13px;
}

.donut {
  width: 150px;
  height: 150px;
  border-radius: 50%;
  position: relative;
  background: conic-gradient(var(--tf-violet) 0 100%);
}

.donut::after {
  content: '';
  position: absolute;
  inset: 23px;
  border-radius: 50%;
  background: var(--tf-bg-card);
}

.legend-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
  font-size: 14px;
  color: var(--tf-text-secondary);
}

.project-row {
  display: grid;
  grid-template-columns: 13px 1fr 100px 1fr;
  align-items: center;
  gap: 13px;
  padding: 10px 0;
}

.project-hours {
  font-size: 14px;
  font-family: 'JetBrains Mono', 'Cascadia Code', monospace;
  color: var(--tf-text-secondary);
}

.project-bar {
  height: 8px;
  border-radius: 1249px;
  background: rgba(124, 92, 252, 0.15);
  overflow: hidden;
}

.project-fill {
  display: block;
  width: 70%;
  height: 100%;
  background: var(--tf-violet);
}

.calendar-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 8px;
  margin-top: 13px;
}

.calendar-cell {
  height: 30px;
  border-radius: 5px;
  background: rgba(80, 60, 20, 0.08);
}

.calendar-cell.level-1 {
  background: rgba(124, 92, 252, 0.2);
}

.calendar-cell.level-2 {
  background: rgba(124, 92, 252, 0.4);
}

.calendar-cell.level-3 {
  background: rgba(124, 92, 252, 0.65);
}

.calendar-cell.level-4 {
  background: var(--tf-violet);
}

.insight {
  padding: 13px 15px;
  border-left: 3px solid var(--tf-violet);
  background: rgba(124, 92, 252, 0.08);
  font-size: 15px;
  color: var(--tf-text-secondary);
}

.monthly-chart {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(5px, 1fr));
  gap: 3px;
  width: 100%;
  height: 100%;
  align-items: end;
}

.monthly-bar {
  display: flex;
  align-items: flex-end;
}

.monthly-fill {
  width: 100%;
  background: rgba(124, 92, 252, 0.65);
  border-radius: 3px 3px 0 0;
}

.empty-state {
  font-size: 14px;
  color: var(--tf-text-hint);
  padding: 13px 0;
}

.color-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
}

.dot-violet { background: var(--tf-violet); }

@media (max-width: 1125px) {
  .daily-focus {
    grid-template-columns: 1fr;
    justify-items: center;
  }

  .donut-layout {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
