<script setup>
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import AppShell from '../Layouts/AppShell.vue';
import { useTime } from '../composables/useTime';

const { format, todayDate, currentHour, daysUntil, toTimestamp } = useTime();

const props = defineProps({
  navigation: {
    type: Object,
    default: () => ({ sections: [] }),
  },
});

function buildEmptyHeatmap() {
  return Array.from({ length: 14 }, () => Array.from({ length: 6 }, () => 0));
}

const userProfile = ref({ name: '', daily_goal_hours: 6 });
const dailyGoalHours = ref(6);
const todayStats = ref({ total_seconds: 0, focus_sessions: 0, avg_session_seconds: 0, pomodoro_count: 0 });
const weekStats = ref({ total_seconds: 0, goal_seconds: 0 });
const streak = ref({ current: 0, longest: 0 });
const xp = ref({ total: 0, level: 1, next_level: 200, title: 'Starter' });
const challenge = ref({ title: 'No challenge today', progress: 0, target: 0, reward: 0 });
const activeSession = ref(null);
const timerSeconds = ref(0);
const exams = ref([]);
const timetableToday = ref([]);
const dailyPlan = ref([]);
const habitsToday = ref([]);
const heatmap = ref(buildEmptyHeatmap());
const recentSessions = ref([]);
const insights = ref([]);

const habitPalette = ['var(--tf-violet)', 'var(--tf-rose)', 'var(--tf-mint)', 'var(--tf-amber)', 'var(--tf-sky)', 'var(--tf-red)'];
const levelTitles = {
  1: 'Starter',
  2: 'Focused',
  3: 'Consistent',
  4: 'Dedicated',
  5: 'Relentless',
  6: 'Flow State',
  7: 'Deep Worker',
  8: 'TimeFlow Master',
};

const greetingName = computed(() => (userProfile.value.name ? userProfile.value.name.split(' ')[0] : 'there'));
const greetingText = computed(() => {
  const hour = currentHour();
  if (hour < 12) return 'Good morning';
  if (hour < 17) return 'Good afternoon';
  return 'Good evening';
});
const loggedHours = computed(() => (todayStats.value.total_seconds / 3600).toFixed(1));
const goalPercent = computed(() => {
  const goal = Math.max(Number(dailyGoalHours.value) || 1, 1);
  const logged = Number(todayStats.value.total_seconds || 0) / 3600;
  return Math.min(100, Math.round((logged / goal) * 100));
});

const ringStrokeColor = computed(() => {
  if (goalPercent.value < 34) return '#EF4444';
  if (goalPercent.value < 67) return '#F5A623';
  if (goalPercent.value < 100) return '#0ECFA4';
  return '#7C5CFC';
});

const formattedTimer = computed(() => {
  const total = timerSeconds.value;
  const h = String(Math.floor(total / 3600)).padStart(2, '0');
  const m = String(Math.floor((total % 3600) / 60)).padStart(2, '0');
  const s = String(total % 60).padStart(2, '0');
  return `${h}:${m}:${s}`;
});

const liveBarPercent = computed(() => {
  const goalSeconds = Math.max(1, Number(dailyGoalHours.value || 6) * 3600);
  return Math.min(100, Math.round((timerSeconds.value / goalSeconds) * 100));
});

const dateLabel = computed(() => format(undefined, 'ddd, MMM D'));
const hasActiveSession = computed(() => !!activeSession.value);
const activeSessionLabel = computed(() => {
  if (!activeSession.value) return 'No active session';
  const label = activeSession.value.label || 'Session';
  const category = activeSession.value.category ? ` - ${activeSession.value.category}` : '';
  return `${label}${category}`;
});

const togglePlan = async (id) => {
  const plan = dailyPlan.value.find((item) => item.id === id);
  if (!plan) return;
  plan.done = !plan.done;
  await saveDailyPlan();
};

const toggleHabit = async (id) => {
  const habit = habitsToday.value.find((item) => item.id === id);
  if (!habit) return;
  const nextDone = !habit.done;
  habit.done = nextDone;

  try {
    const response = await axios.post(`/api/habits/${habit.id}/log`, {
      date: todayDate(),
      done: nextDone,
    });

    const logData = response.data?.data;
    if (logData?.log) {
      habit.done = logData.log.done;
    }
    if (typeof logData?.streak_current === 'number') {
      habit.streak = logData.streak_current;
    }
  } catch (error) {
    habit.done = !nextDone;
    console.warn('Habit toggle failed', error);
  }
};

const stopSession = async () => {
  if (!activeSession.value?.id) return;
  try {
    await axios.post(`/api/sessions/${activeSession.value.id}/stop`);
    setActiveSession(null);
    loadDashboard();
  } catch (error) {
    console.warn('Stop session failed', error);
  }
};

const dismissInsight = async (insight) => {
  if (!insight?.id) {
    insights.value = insights.value.filter((i) => i.type !== insight.type);
    return;
  }
  try {
    await axios.delete(`/api/analytics/insights/${insight.id}`);
    insights.value = insights.value.filter((i) => i.id !== insight.id);
  } catch (error) {
    console.warn('Insight dismiss failed', error);
  }
};

const navigateTo = (path) => {
  router.visit(path);
};

let timerInterval = null;

function formatDuration(seconds) {
  const total = Math.max(0, Number(seconds) || 0);
  const h = String(Math.floor(total / 3600)).padStart(2, '0');
  const m = String(Math.floor((total % 3600) / 60)).padStart(2, '0');
  return `${h}:${m}`;
}

function buildHeatmapGrid(days) {
  if (!Array.isArray(days) || days.length === 0) {
    return buildEmptyHeatmap();
  }

  const normalized = days.slice(0, 14);
  while (normalized.length < 14) {
    normalized.push({ level: 0 });
  }

  return normalized.map((day) => Array.from({ length: 6 }, () => Math.max(0, Math.min(4, Number(day.level) || 0))));
}

function startTimer(startedAt) {
  if (!startedAt) return;
  const startTime = toTimestamp(startedAt);

  const updateTimer = () => {
    timerSeconds.value = Math.max(0, Math.floor((Date.now() - startTime) / 1000));
  };

  updateTimer();
  if (timerInterval) clearInterval(timerInterval);
  timerInterval = setInterval(updateTimer, 1000);
}

function stopTimer() {
  if (timerInterval) {
    clearInterval(timerInterval);
    timerInterval = null;
  }
  timerSeconds.value = 0;
}

function setActiveSession(session) {
  activeSession.value = session || null;
  if (session?.started_at) {
    startTimer(session.started_at);
  } else {
    stopTimer();
  }
}

function resolveChallengeProgress(challengeData, completed) {
  const target = Math.round(Number(challengeData?.target_value || 0));

  if (!challengeData) {
    return { progress: 0, target: 0, reward: 0, title: 'No challenge today' };
  }

  if (completed) {
    return {
      progress: target,
      target,
      reward: Number(challengeData.xp_reward || 0),
      title: challengeData.title,
    };
  }

  if (challengeData.type === 'hours_logged') {
    const hours = Math.floor(Number(todayStats.value.total_seconds || 0) / 3600);
    return {
      progress: Math.min(target, hours),
      target,
      reward: Number(challengeData.xp_reward || 0),
      title: challengeData.title,
    };
  }

  if (challengeData.type === 'pomodoros') {
    const pomodoros = Number(todayStats.value.pomodoro_count || 0);
    return {
      progress: Math.min(target, pomodoros),
      target,
      reward: Number(challengeData.xp_reward || 0),
      title: challengeData.title,
    };
  }

  return {
    progress: 0,
    target,
    reward: Number(challengeData.xp_reward || 0),
    title: challengeData.title,
  };
}

async function saveDailyPlan() {
  if (!dailyPlan.value.length) return;

  try {
    await axios.post('/api/daily-plans', {
      date: todayDate(),
      tasks: dailyPlan.value.map((task) => ({ text: task.text, done: task.done })),
    });
  } catch (error) {
    console.warn('Daily plan save failed', error);
  }
}

async function loadDashboard() {
  const [
    userResult,
    dailyResult,
    weeklyResult,
    heatmapResult,
    examsResult,
    timetableResult,
    insightsResult,
    challengeResult,
    activeResult,
    recentResult,
    planResult,
    habitsResult,
    gamificationResult,
    weeklyGoalResult,
  ] = await Promise.allSettled([
    axios.get('/api/user'),
    axios.get('/api/analytics/daily'),
    axios.get('/api/analytics/weekly'),
    axios.get('/api/analytics/heatmap'),
    axios.get('/api/exams'),
    axios.get('/api/timetable/today'),
    axios.get('/api/analytics/insights'),
    axios.get('/api/challenges/today'),
    axios.get('/api/sessions/active'),
    axios.get('/api/sessions/recent'),
    axios.get('/api/daily-plans/today'),
    axios.get('/api/habits/today'),
    axios.get('/api/gamification/profile'),
    axios.get('/api/goals', { params: { type: 'weekly_hours', active: true } }),
  ]);

  const userPayload = userResult.status === 'fulfilled' ? userResult.value.data : null;
  if (userPayload) {
    userProfile.value = userPayload;
    dailyGoalHours.value = Number(userPayload.daily_goal_hours ?? dailyGoalHours.value);
  }

  const dailyPayload = dailyResult.status === 'fulfilled' ? dailyResult.value.data?.data : null;
  if (dailyPayload) {
    todayStats.value = {
      total_seconds: Number(dailyPayload.total_seconds || 0),
      focus_sessions: Number(dailyPayload.focus_sessions || 0),
      avg_session_seconds: Number(dailyPayload.avg_session_seconds || 0),
      pomodoro_count: Number(dailyPayload.pomodoro_count || 0),
    };
  }

  const weeklyPayload = weeklyResult.status === 'fulfilled' ? weeklyResult.value.data?.data : null;
  const weeklyGoals = weeklyGoalResult.status === 'fulfilled' ? weeklyGoalResult.value.data?.data : null;
  const weeklyGoalValue = Array.isArray(weeklyGoals) && weeklyGoals.length > 0
    ? Number(weeklyGoals[0].target_value || 0)
    : 0;

  if (weeklyPayload) {
    weekStats.value = {
      total_seconds: Number(weeklyPayload.total_seconds || 0),
      goal_seconds: weeklyGoalValue > 0
        ? weeklyGoalValue * 3600
        : Number(dailyGoalHours.value || 0) * 5 * 3600,
    };
  }

  const heatmapPayload = heatmapResult.status === 'fulfilled' ? heatmapResult.value.data?.data : null;
  heatmap.value = buildHeatmapGrid(heatmapPayload?.days || []);

  const examsPayload = examsResult.status === 'fulfilled' ? examsResult.value.data?.data : null;
  if (Array.isArray(examsPayload)) {
    exams.value = examsPayload.map((exam) => {
      const daysRemaining = daysUntil(exam.exam_date);
      const urgency = daysRemaining <= 7 ? 'urgent' : daysRemaining <= 14 ? 'warn' : 'calm';
      return {
        id: exam.id,
        subject: exam.subject,
        days: daysRemaining,
        urgency,
      };
    });
  } else {
    exams.value = [];
  }

  const timetablePayload = timetableResult.status === 'fulfilled' ? timetableResult.value.data?.data : null;
  if (Array.isArray(timetablePayload)) {
    timetableToday.value = timetablePayload.map((block) => ({
      id: block.id,
      title: block.title,
      start_time: block.start_time,
      type: block.type,
      color: block.color,
    }));
  } else {
    timetableToday.value = [];
  }

  const insightsPayload = insightsResult.status === 'fulfilled' ? insightsResult.value.data?.data : null;
  insights.value = Array.isArray(insightsPayload) ? insightsPayload : [];

  const challengePayload = challengeResult.status === 'fulfilled' ? challengeResult.value.data?.data : null;
  if (challengePayload?.challenge) {
    const progress = resolveChallengeProgress(challengePayload.challenge, challengePayload.completed);
    challenge.value = {
      title: progress.title,
      progress: progress.progress,
      target: progress.target,
      reward: progress.reward,
    };
  } else {
    challenge.value = { title: 'No challenge today', progress: 0, target: 0, reward: 0 };
  }

  const activePayload = activeResult.status === 'fulfilled' ? activeResult.value.data?.data : null;
  setActiveSession(activePayload?.session || null);

  const recentPayload = recentResult.status === 'fulfilled' ? recentResult.value.data?.data : null;
  if (Array.isArray(recentPayload)) {
    recentSessions.value = recentPayload.map((session) => ({
      id: session.id,
      name: session.label,
      category: session.category,
      duration: formatDuration(session.duration_seconds),
      color: session.color,
    }));
  } else {
    recentSessions.value = [];
  }

  const planPayload = planResult.status === 'fulfilled' ? planResult.value.data?.data : null;
  const tasks = Array.isArray(planPayload?.tasks) ? planPayload.tasks : [];
  dailyPlan.value = tasks.map((task, index) => ({
    id: index + 1,
    text: task.text,
    done: !!task.done,
  }));

  const habitsPayload = habitsResult.status === 'fulfilled' ? habitsResult.value.data?.data : null;
  if (Array.isArray(habitsPayload?.habits)) {
    habitsToday.value = habitsPayload.habits.map((habit, index) => ({
      id: habit.id,
      name: habit.title,
      color: habitPalette[index % habitPalette.length],
      streak: Number(habit.streak_current || 0),
      done: !!habit.done,
    }));
  } else {
    habitsToday.value = [];
  }

  const gamificationPayload = gamificationResult.status === 'fulfilled' ? gamificationResult.value.data?.data : null;
  if (gamificationPayload) {
    const level = Number(gamificationPayload.level || 1);
    const nextLevel = Number(gamificationPayload.next_level_xp || 0);
    xp.value = {
      total: Number(gamificationPayload.xp_total || 0),
      level,
      next_level: nextLevel > 0 ? nextLevel : Math.max(Number(gamificationPayload.xp_total || 0), 1),
      title: levelTitles[level] || 'Starter',
    };

    streak.value = {
      current: Number(gamificationPayload.streak_current || 0),
      longest: Number(gamificationPayload.streak_longest || 0),
    };
  }
}

onMounted(() => {
  loadDashboard().catch((error) => {
    console.warn('Dashboard data fetch failed', error);
  });
});

onUnmounted(() => {
  stopTimer();
});
</script>

<template>
  <div class="dashboard-page">
    <AppShell :navigation="props.navigation">
      <div class="page-header">
        <div>
          <div class="page-title">{{ greetingText }}, {{ greetingName }} 👋</div>
          <div class="page-subtitle">Stay consistent and keep the streak alive.</div>
        </div>
        <div class="tf-date-badge">{{ dateLabel }}</div>
      </div>

      <div v-if="exams.length" class="exam-block">
        <div class="tf-section-label">Upcoming exams</div>
        <div class="exam-chips">
          <div v-for="exam in exams" :key="exam.id" class="exam-chip" :class="exam.urgency">
            {{ exam.subject }} - {{ exam.days }} days
          </div>
          <button class="exam-chip add" type="button">Add exam</button>
        </div>
      </div>

      <div v-if="timetableToday.length" class="timetable-block">
        <div class="tf-section-label">Today schedule</div>
        <div class="timetable-strip">
          <div v-for="block in timetableToday" :key="block.id" class="timetable-item" :style="{ borderColor: block.color, color: block.color }">
            <div class="timetable-time">{{ block.start_time }}</div>
            <div class="timetable-name">{{ block.title }}</div>
            <div class="timetable-type">{{ block.type }}</div>
          </div>
        </div>
      </div>

      <div class="grid-two">
        <div class="tf-card">
          <div class="tf-section-label">Today focus</div>
          <div class="focus-row">
            <div class="focus-ring">
              <svg viewBox="0 0 72 72" aria-hidden="true">
                <circle class="ring-track" cx="36" cy="36" r="30" />
                <circle class="ring-fill" cx="36" cy="36" r="30" :style="{ strokeDashoffset: 188 - (188 * goalPercent) / 100, stroke: ringStrokeColor }" />
              </svg>
              <div class="ring-center">
                <div class="focus-hours">{{ loggedHours }}h</div>
                <div class="focus-label">of {{ dailyGoalHours }}h</div>
              </div>
            </div>
            <div class="focus-stats">
              <div class="focus-percent">{{ goalPercent }}%</div>
              <div class="stat">Sessions {{ todayStats.focus_sessions }}</div>
              <div class="stat">Avg {{ Math.round(todayStats.avg_session_seconds / 60) }}m</div>
            </div>
          </div>
        </div>
        <div class="tf-card live-card">
          <div class="live-header">
            <div class="live-tag">{{ hasActiveSession ? 'Live' : 'Idle' }}</div>
            <button v-if="hasActiveSession" class="outline-btn" type="button" @click="stopSession">Stop</button>
          </div>
          <div class="live-project">{{ activeSessionLabel }}</div>
          <div class="live-timer">{{ formattedTimer }}</div>
          <div class="live-bar"><span :style="{ width: liveBarPercent + '%' }"></span></div>
        </div>
      </div>

      <div class="tf-card">
        <div class="plan-header">
          <div class="tf-section-label">Today priorities</div>
          <div class="plan-xp">+30 XP</div>
        </div>
        <div v-for="item in dailyPlan" :key="item.id" class="plan-row" @click="togglePlan(item.id)">
          <span class="plan-check" :class="{ done: item.done }"></span>
          <span class="plan-text" :class="{ done: item.done }">{{ item.text }}</span>
        </div>
      </div>

      <div class="stats-row">
        <div class="tf-card stat-card">
          <div class="stat-label">Today</div>
          <div class="stat-value">{{ loggedHours }}h</div>
          <div class="stat-meta">logged so far</div>
        </div>
        <div class="tf-card stat-card">
          <div class="stat-label">This week</div>
          <div class="stat-value">{{ (weekStats.total_seconds / 3600).toFixed(1) }}h</div>
          <div class="stat-meta">goal {{ (weekStats.goal_seconds / 3600).toFixed(0) }}h</div>
        </div>
        <div class="tf-card stat-card">
          <div class="stat-label">Streak</div>
          <div class="stat-value">{{ streak.current }}d</div>
          <div class="stat-meta">best {{ streak.longest }} days</div>
        </div>
      </div>

      <div class="grid-two">
        <div class="tf-card">
          <div class="xp-header">
            <div>
              <div class="xp-title">{{ xp.title }}</div>
              <div class="xp-level">Level {{ xp.level }}</div>
            </div>
            <div class="xp-total">{{ xp.total }} XP</div>
          </div>
          <div class="xp-bar"><span :style="{ width: (xp.total / xp.next_level) * 100 + '%' }"></span></div>
          <div class="xp-meta">Next {{ xp.next_level }} XP</div>
        </div>
        <div class="tf-card">
          <div class="challenge-label">Daily challenge</div>
          <div class="challenge-title">{{ challenge.title }}</div>
          <div class="challenge-progress">
            <span v-for="index in challenge.target" :key="index" class="challenge-dot" :class="{ done: index <= challenge.progress }"></span>
            <span class="challenge-xp">+{{ challenge.reward }} XP</span>
          </div>
        </div>
      </div>

      <div v-if="habitsToday.length" class="tf-card">
        <div class="habits-header">
          <div class="tf-section-label">Habits today</div>
        </div>
        <div v-for="habit in habitsToday" :key="habit.id" class="habit-row" @click="toggleHabit(habit.id)">
          <div class="habit-name">{{ habit.name }}</div>
          <span class="habit-dot" :style="{ background: habit.done ? habit.color : 'transparent', borderColor: habit.color }"></span>
          <div class="habit-streak">{{ habit.streak }}</div>
        </div>
      </div>

      <div class="quick-start">
        <div class="tf-section-label">Quick start</div>
        <div class="quick-grid">
          <button class="quick-btn" type="button" @click="navigateTo('/timer')"><i class="ti ti-alarm" aria-hidden="true"></i> Pomodoro</button>
          <button class="quick-btn" type="button" @click="navigateTo('/analytics')"><i class="ti ti-chart-bar" aria-hidden="true"></i> Analytics</button>
          <button class="quick-btn" type="button" @click="navigateTo('/reports')"><i class="ti ti-file-analytics" aria-hidden="true"></i> Report</button>
        </div>
      </div>

      <div class="tf-card heatmap">
        <div class="heatmap-header">
          <div class="heatmap-title">Activity</div>
          <div class="heatmap-scale">Low - High</div>
        </div>
        <div class="heatmap-grid">
          <div v-for="(col, colIndex) in heatmap" :key="colIndex" class="heatmap-col">
            <div v-for="(cell, cellIndex) in col" :key="cellIndex" class="heatmap-cell" :class="'level-' + cell"></div>
          </div>
        </div>
      </div>

      <div class="tf-card">
        <div class="recent-header">
          <div class="recent-title">Recent sessions</div>
          <button class="link-btn" type="button" @click="navigateTo('/timer')">See all â†’</button>
        </div>
        <div v-for="session in recentSessions" :key="session.id" class="recent-row">
          <span class="color-dot" :style="{ background: session.color }"></span>
          <div>
            <div class="recent-name">{{ session.name }}</div>
            <div class="recent-meta">{{ session.category }}</div>
          </div>
          <div class="recent-duration">{{ session.duration }}</div>
        </div>
      </div>

      <div v-if="insights.length" class="insights">
        <div class="tf-section-label">Insights</div>
        <div v-for="insight in insights" :key="insight.id || insight.type" class="insight-card">
          <span>{{ insight.message }}</span>
          <button class="insight-dismiss" type="button" @click="dismissInsight(insight)" aria-label="Dismiss insight">
            <i class="ti ti-x" aria-hidden="true"></i>
          </button>
        </div>
      </div>
    </AppShell>
  </div>
</template>

<style scoped>
.dashboard-page {
  min-height: 100vh;
  background: var(--tf-bg-page);
  font-family: 'Plus Jakarta Sans', 'Segoe UI', sans-serif;
  color: var(--tf-text-primary);
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
}

.page-subtitle {
  font-size: 15px;
  color: var(--tf-text-secondary);
}

.exam-block,
.timetable-block {
  margin-top: 15px;
}

.exam-chips {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.exam-chip {
  padding: 5px 13px;
  border-radius: 1249px;
  font-size: 14px;
  font-weight: 600;
  border: 1px solid var(--tf-border-default);
  color: var(--tf-text-secondary);
  background: var(--tf-bg-card-alt);
}

.exam-chip.calm {
  border-color: rgba(56, 189, 248, 0.28);
  color: #0369a1;
}

.exam-chip.warn {
  border-color: rgba(245, 166, 35, 0.28);
  color: #92400e;
}

.exam-chip.urgent {
  border-color: rgba(239, 68, 68, 0.28);
  color: #991b1b;
}

.exam-chip.add {
  border-style: dashed;
}

.timetable-strip {
  display: flex;
  gap: 8px;
  overflow-x: auto;
}

.timetable-item {
  min-width: 113px;
  padding: 8px 10px;
  border-radius: 10px;
  border: 1px solid;
}

.timetable-time {
  font-size: 11px;
  font-family: 'JetBrains Mono', 'Cascadia Code', monospace;
}

.timetable-name {
  font-size: 14px;
  font-weight: 700;
}

.timetable-type {
  font-size: 11px;
  opacity: 0.7;
}

.grid-two {
  display: grid;
  grid-template-columns: 1fr;
  gap: 13px;
  margin-top: 15px;
}

.focus-row {
  display: flex;
  gap: 15px;
  align-items: center;
}

.focus-ring {
  position: relative;
  width: 90px;
  height: 90px;
}

.focus-ring svg {
  width: 90px;
  height: 90px;
  transform: rotate(-90deg);
}

.ring-track {
  fill: none;
  stroke: rgba(80, 60, 20, 0.12);
  stroke-width: 5;
}

.ring-fill {
  fill: none;
  stroke: var(--tf-violet);
  stroke-width: 5;
  stroke-linecap: round;
  stroke-dasharray: 188;
}

.ring-center {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.focus-hours {
  font-size: 16px;
  font-weight: 600;
  font-family: 'JetBrains Mono', 'Cascadia Code', monospace;
}

.focus-label {
  font-size: 11px;
  color: var(--tf-text-hint);
}

.focus-stats {
  display: flex;
  flex-direction: column;
  gap: 5px;
  font-size: 13px;
  color: var(--tf-text-secondary);
}

.focus-percent {
  font-size: 23px;
  font-weight: 800;
  color: var(--tf-violet);
}

.live-card {
  position: relative;
  overflow: hidden;
}

.live-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.live-tag {
  font-size: 13px;
  font-weight: 700;
  color: var(--tf-mint);
}

.live-project {
  margin-top: 8px;
  font-size: 14px;
  color: var(--tf-mint);
}

.live-timer {
  font-size: 35px;
  font-weight: 600;
  font-family: 'JetBrains Mono', 'Cascadia Code', monospace;
  margin-top: 5px;
}

.live-bar {
  height: 4px;
  border-radius: 3px;
  background: rgba(14, 207, 164, 0.2);
  margin-top: 13px;
  overflow: hidden;
}

.live-bar span {
  display: block;
  height: 100%;
  width: 63%;
  background: var(--tf-mint);
}

.plan-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.plan-xp {
  font-size: 11px;
  font-weight: 700;
  color: #5b3fd4;
  background: rgba(124, 92, 252, 0.12);
  border: 1px solid rgba(124, 92, 252, 0.22);
  padding: 3px 8px;
  border-radius: 1249px;
}

.plan-row {
  display: flex;
  gap: 10px;
  align-items: center;
  padding: 8px 0;
  border-bottom: 1px solid var(--tf-border-default);
}

.plan-row:last-child {
  border-bottom: none;
}

.plan-check {
  width: 20px;
  height: 20px;
  border-radius: 5px;
  border: 2px solid var(--tf-border-default);
}

.plan-check.done {
  background: var(--tf-mint);
  border-color: var(--tf-mint);
}

.plan-text {
  font-size: 15px;
}

.plan-text.done {
  text-decoration: line-through;
  color: var(--tf-text-hint);
}

.stats-row {
  display: grid;
  grid-template-columns: 1fr;
  gap: 13px;
  margin-top: 15px;
}

.stat-card {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.stat-label {
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--tf-text-hint);
}

.stat-value {
  font-size: 23px;
  font-weight: 700;
  font-family: 'JetBrains Mono', 'Cascadia Code', monospace;
}

.stat-meta {
  font-size: 13px;
  color: var(--tf-text-secondary);
}

.xp-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.xp-title {
  font-size: 16px;
  font-weight: 700;
}

.xp-level {
  font-size: 13px;
  color: var(--tf-text-secondary);
}

.xp-total {
  font-size: 15px;
  font-weight: 700;
  font-family: 'JetBrains Mono', 'Cascadia Code', monospace;
}

.xp-bar {
  height: 5px;
  border-radius: 1249px;
  background: rgba(124, 92, 252, 0.15);
  margin-top: 10px;
  overflow: hidden;
}

.xp-bar span {
  display: block;
  height: 100%;
  background: linear-gradient(90deg, var(--tf-violet), #a78bfa);
}

.xp-meta {
  font-size: 13px;
  color: var(--tf-text-hint);
  margin-top: 5px;
}

.challenge-label {
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--tf-text-hint);
}

.challenge-title {
  font-size: 15px;
  font-weight: 600;
  margin-top: 5px;
}

.challenge-progress {
  display: flex;
  align-items: center;
  gap: 5px;
  margin-top: 10px;
}

.challenge-dot {
  width: 13px;
  height: 13px;
  border-radius: 4px;
  background: rgba(80, 60, 20, 0.12);
}

.challenge-dot.done {
  background: var(--tf-amber);
}

.challenge-xp {
  font-size: 13px;
  font-weight: 600;
  color: var(--tf-amber);
  margin-left: auto;
}

.habits-header {
  margin-bottom: 8px;
}

.habit-row {
  display: grid;
  grid-template-columns: 1fr 30px 50px;
  align-items: center;
  gap: 10px;
  padding: 8px 0;
  border-bottom: 1px solid var(--tf-border-default);
}

.habit-row:last-child {
  border-bottom: none;
}

.habit-name {
  font-size: 15px;
  font-weight: 600;
}

.habit-dot {
  width: 23px;
  height: 23px;
  border-radius: 6px;
  border: 1px solid;
}

.habit-streak {
  font-size: 13px;
  color: var(--tf-amber);
  font-family: 'JetBrains Mono', 'Cascadia Code', monospace;
}

.quick-start {
  margin-top: 15px;
}

.quick-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 10px;
}

.quick-btn {
  height: 75px;
  border-radius: 13px;
  border: 1px solid var(--tf-border-default);
  background: var(--tf-bg-card);
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  color: var(--tf-text-primary);
}

.heatmap {
  margin-top: 15px;
}

.heatmap-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.heatmap-title {
  font-size: 15px;
  font-weight: 700;
}

.heatmap-scale {
  font-size: 11px;
  color: var(--tf-text-hint);
}

.heatmap-grid {
  display: flex;
  gap: 4px;
}

.heatmap-col {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.heatmap-cell {
  width: 11px;
  height: 11px;
  border-radius: 3px;
  background: rgba(80, 60, 20, 0.1);
}

.heatmap-cell.level-1 { background: rgba(124, 92, 252, 0.2); }
.heatmap-cell.level-2 { background: rgba(124, 92, 252, 0.4); }
.heatmap-cell.level-3 { background: rgba(124, 92, 252, 0.65); }
.heatmap-cell.level-4 { background: var(--tf-violet); }

.recent-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.recent-title {
  font-size: 15px;
  font-weight: 700;
}

.link-btn {
  border: none;
  background: transparent;
  font-size: 14px;
  color: #5b3fd4;
  cursor: pointer;
}

.recent-row {
  display: grid;
  grid-template-columns: 13px 1fr auto;
  align-items: center;
  gap: 13px;
  padding: 8px 0;
  border-bottom: 1px solid var(--tf-border-default);
}

.recent-row:last-child {
  border-bottom: none;
}

.recent-name {
  font-size: 15px;
  font-weight: 600;
}

.recent-meta {
  font-size: 13px;
  color: var(--tf-text-hint);
}

.recent-duration {
  font-size: 14px;
  font-family: 'JetBrains Mono', 'Cascadia Code', monospace;
  color: var(--tf-text-secondary);
}

.insights {
  margin-top: 15px;
}

.insight-card {
  margin-top: 8px;
  padding: 13px 15px;
  border-left: 3px solid var(--tf-violet);
  background: rgba(124, 92, 252, 0.08);
  font-size: 15px;
  color: var(--tf-text-secondary);
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 13px;
}

.insight-dismiss {
  width: 28px;
  height: 28px;
  border: none;
  background: transparent;
  color: var(--tf-text-hint);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 5px;
  flex-shrink: 0;
}

.insight-dismiss:hover {
  color: var(--tf-text-primary);
  background: rgba(80, 60, 20, 0.1);
}

.action-btn {
  height: 44px;
  padding: 0 13px;
  border-radius: 10px;
  border: 1px solid var(--tf-border-default);
  background: transparent;
  font-size: 14px;
  color: var(--tf-text-secondary);
  cursor: pointer;
}

.outline-btn {
  height: 44px;
  padding: 0 13px;
  border-radius: 10px;
  border: 1px solid var(--tf-border-default);
  background: transparent;
  font-size: 14px;
  color: var(--tf-text-secondary);
  cursor: pointer;
}

.color-dot {
  width: 9px;
  height: 9px;
  border-radius: 50%;
}

@media (min-width: 768px) {
  .grid-two {
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  }
  .stats-row {
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  }
}

@media (min-width: 1125px) {
  .quick-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}
</style>
