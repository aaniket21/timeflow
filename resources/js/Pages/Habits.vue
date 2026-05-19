<script setup>
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';
import AppShell from '../Layouts/AppShell.vue';
import ModalBase from '../Components/ModalBase.vue';

const props = defineProps({
  navigation: {
    type: Object,
    default: () => ({ sections: [] }),
  },
});

const palette = ['var(--tf-violet)', 'var(--tf-mint)', 'var(--tf-rose)', 'var(--tf-amber)', 'var(--tf-sky)', 'var(--tf-red)'];

const startDate = ref(startOfWeek(new Date()));
const habits = ref([]);
const stats = ref({ checks_total: 0, active_habits: 0, longest_streak: 0 });
const showHabitModal = ref(false);
const habitForm = ref({ title: '', frequency: 'daily' });

const days = computed(() => {
  const labels = [];
  const cursor = new Date(startDate.value);
  for (let i = 0; i < 7; i += 1) {
    labels.push(cursor.toLocaleDateString('en-US', { weekday: 'short' }).slice(0, 1));
    cursor.setDate(cursor.getDate() + 1);
  }
  return labels;
});

const checksThisWeek = computed(() => stats.value.checks_total || 0);
const activeHabits = computed(() => stats.value.active_habits || 0);
const longestStreak = computed(() => stats.value.longest_streak || 0);

const loadHabits = async () => {
  try {
    const start = formatDateInput(startDate.value);
    const response = await axios.get('/api/habits/week', { params: { start } });
    const data = response.data?.data;

    if (data?.stats) {
      stats.value = data.stats;
    }

    if (Array.isArray(data?.habits)) {
      habits.value = data.habits.map((habit, index) => ({
        id: habit.id,
        name: habit.title,
        streak: habit.streak_current,
        color: palette[index % palette.length],
        checks: habit.checks,
      }));
    }
  } catch (error) {
    console.warn('Habit week fetch failed', error);
  }
};

onMounted(() => {
  loadHabits();
});

const pendingToggles = ref(new Set());

const toggleCheck = async (habit, dayIndex) => {
  const toggleKey = `${habit.id}-${dayIndex}`;
  if (pendingToggles.value.has(toggleKey)) return;
  pendingToggles.value.add(toggleKey);

  const cursor = new Date(startDate.value);
  cursor.setDate(cursor.getDate() + dayIndex);

  const now = new Date();
  now.setHours(0, 0, 0, 0);

  if (cursor > now && !habit.checks[dayIndex]) {
    if (window.TimeflowToast) window.TimeflowToast.error('Cannot log habits for future days');
    pendingToggles.value.delete(toggleKey);
    return;
  }

  const newValue = !habit.checks[dayIndex];
  // Vue 3 proxy will react to this array index mutation
  habit.checks[dayIndex] = newValue;
  
  if (newValue) {
    stats.value.checks_total = (stats.value.checks_total || 0) + 1;
  } else {
    stats.value.checks_total = Math.max(0, (stats.value.checks_total || 0) - 1);
  }

  const dateStr = formatDateInput(cursor);

  try {
    const res = await axios.post(`/api/habits/${habit.id}/log`, {
      date: dateStr,
      done: newValue,
    });
    
    // Update streak from backend response
    if (res.data?.data?.streak_current !== undefined) {
      habit.streak = res.data.data.streak_current;
      stats.value.longest_streak = Math.max(stats.value.longest_streak || 0, habit.streak);
    }
  } catch (error) {
    // Revert optimistic updates on failure
    habit.checks[dayIndex] = !newValue;
    if (newValue) stats.value.checks_total--;
    else stats.value.checks_total++;
    
    if (window.TimeflowToast) window.TimeflowToast.error('Failed to log habit');
    console.warn('Habit toggle failed', error);
  } finally {
    pendingToggles.value.delete(toggleKey);
  }
};

function startOfWeek(date) {
  const base = new Date(date);
  const day = base.getDay();
  const diff = day === 0 ? -6 : 1 - day;
  base.setDate(base.getDate() + diff);
  base.setHours(0, 0, 0, 0);
  return base;
}

function formatDateInput(date) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

const createHabit = async () => {
  try {
    const payload = {
      title: habitForm.value.title,
      type: 'habit',
      target_value: habitForm.value.frequency === 'daily' ? 7 : (habitForm.value.frequency === 'weekdays' ? 5 : 3),
    };
    await axios.post('/api/goals', payload);
    showHabitModal.value = false;
    habitForm.value = { title: '', frequency: 'daily' };
    loadHabits();
    if (window.TimeflowToast) window.TimeflowToast.success('Habit created');
  } catch (error) {
    if (window.TimeflowToast) window.TimeflowToast.error('Failed to create habit');
  }
};
</script>

<template>
  <div class="habits-page">
    <AppShell :navigation="props.navigation">
      <div class="page-header">
        <div>
          <div class="page-title">Habit Tracker</div>
          <div class="page-subtitle">Keep streaks consistent all week.</div>
        </div>
        <button class="outline-btn" type="button" @click="showHabitModal = true">+ Add habit</button>
      </div>

      <div class="stats-row">
        <div class="tf-card stat-card">
          <div class="stat-label">Checks this week</div>
          <div class="stat-value">{{ checksThisWeek }}</div>
        </div>
        <div class="tf-card stat-card">
          <div class="stat-label">Active habits</div>
          <div class="stat-value">{{ activeHabits }}</div>
        </div>
        <div class="tf-card stat-card">
          <div class="stat-label">Longest streak</div>
          <div class="stat-value">{{ longestStreak }}</div>
        </div>
      </div>

      <div class="tf-card habit-grid">
        <div class="grid-header">
          <div>Habit</div>
          <div v-for="day in days" :key="day" class="day-cell">{{ day }}</div>
        </div>
        <div v-if="habits.length">
          <div v-for="habit in habits" :key="habit.id" class="habit-row">
            <div class="habit-name">
              <span class="habit-dot" :style="{ background: habit.color }"></span>
              <span>{{ habit.name }}</span>
              <span class="habit-streak">{{ habit.streak }}</span>
            </div>
            <div v-for="(check, index) in habit.checks" :key="index" class="check-cell" @click="toggleCheck(habit, index)">
              <span class="check-box" :style="{ background: check ? habit.color : 'transparent', borderColor: habit.color, cursor: 'pointer' }"></span>
            </div>
          </div>
        </div>
        <div v-else class="empty-state">No habits yet. Add one to start tracking.</div>
      </div>

      <div class="tf-card insight-card">
        <div class="tf-section-label">Insights</div>
        <div class="insight">Check back after 7 days for habit insights.</div>
      </div>

      <ModalBase :open="showHabitModal" title="Add Habit" @close="showHabitModal = false">
        <div class="field">
          <label class="field-label">Habit name</label>
          <input class="text-input" type="text" v-model="habitForm.title" placeholder="e.g. Read 30 min" />
        </div>
        <div class="field">
          <label class="field-label">Frequency</label>
          <select class="text-input" v-model="habitForm.frequency">
            <option value="daily">Daily</option>
            <option value="weekdays">Weekdays only</option>
            <option value="custom">Custom</option>
          </select>
        </div>
        <template #footer>
          <button class="outline-btn" type="button" @click="showHabitModal = false">Cancel</button>
          <button class="primary-btn" type="button" @click="createHabit">Add</button>
        </template>
      </ModalBase>
    </AppShell>
  </div>
</template>

<style scoped>
.habits-page {
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

.stats-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 13px;
  margin-top: 15px;
}

.stat-label {
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--tf-text-hint);
}

.stat-value {
  font-size: 20px;
  font-weight: 700;
  font-family: 'JetBrains Mono', 'Cascadia Code', monospace;
}

.habit-grid {
  margin-top: 15px;
}

.grid-header,
.habit-row {
  display: grid;
  grid-template-columns: 150px repeat(7, 1fr);
  align-items: center;
  gap: 8px;
}

.grid-header {
  font-size: 13px;
  color: var(--tf-text-hint);
  text-transform: uppercase;
  letter-spacing: 0.08em;
  margin-bottom: 10px;
}

.habit-name {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 15px;
  font-weight: 600;
}

.habit-dot {
  width: 13px;
  height: 13px;
  border-radius: 50%;
}

.habit-streak {
  font-size: 13px;
  color: var(--tf-amber);
  font-family: 'JetBrains Mono', 'Cascadia Code', monospace;
}

.day-cell,
.check-cell {
  display: flex;
  justify-content: center;
}

.check-box {
  width: 28px;
  height: 28px;
  border-radius: 8px;
  border: 1px solid;
}

.outline-btn {
  height: 45px;
  padding: 0 15px;
  border-radius: 13px;
  border: 1px solid var(--tf-border-default);
  background: transparent;
  color: var(--tf-text-secondary);
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
}

.insight-card {
  margin-top: 15px;
}

.insight {
  padding: 13px 15px;
  border-left: 3px solid var(--tf-violet);
  background: rgba(124, 92, 252, 0.08);
  font-size: 15px;
  color: var(--tf-text-secondary);
}

.empty-state {
  font-size: 15px;
  color: var(--tf-text-hint);
  padding: 15px 0 8px;
}
</style>
