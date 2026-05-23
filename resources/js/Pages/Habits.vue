<script setup>
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';
import AppShell from '../Layouts/AppShell.vue';
import ModalBase from '../Components/ModalBase.vue';
import { useTime } from '../composables/useTime';

const time = useTime();

const props = defineProps({
  navigation: {
    type: Object,
    default: () => ({ sections: [] }),
  },
});

const palette = ['var(--tf-violet)', 'var(--tf-mint)', 'var(--tf-rose)', 'var(--tf-amber)', 'var(--tf-sky)', 'var(--tf-red)'];

const startDate = ref(time.startOfWeek());
const habits = ref([]);
const stats = ref({ checks_total: 0, active_habits: 0, longest_streak: 0 });
const showHabitModal = ref(false);
const habitForm = ref({ title: '', frequency: 'daily' });

const days = computed(() => {
  const labels = [];
  let cursor = time.parse(startDate.value);
  for (let i = 0; i < 7; i += 1) {
    labels.push(cursor.format('dd').charAt(0));
    cursor = cursor.add(1, 'day');
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

  const targetDate = time.parse(startDate.value).add(dayIndex, 'day');

  if (time.isFuture(targetDate) && !habit.checks[dayIndex]) {
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

  const dateStr = time.parse(startDate.value).add(dayIndex, 'day').format('YYYY-MM-DD');

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
  return time.startOfWeek(date);
}

function formatDateInput(date) {
  return time.parse(date).format('YYYY-MM-DD');
}

const editHabit = (habit) => {
  habitForm.value = { id: habit.id, title: habit.name, frequency: 'daily' };
  showHabitModal.value = true;
};

const deleteHabit = async (habit) => {
  if (!confirm(`Delete habit "${habit.name}"?`)) return;
  try {
    await axios.delete(`/api/goals/${habit.id}`);
    loadHabits();
    if (window.TimeflowToast) window.TimeflowToast.success('Habit deleted');
  } catch (error) {
    if (window.TimeflowToast) window.TimeflowToast.error('Failed to delete habit');
  }
};

const createHabit = async () => {
  try {
    const payload = {
      title: habitForm.value.title,
      type: 'habit',
      target_value: habitForm.value.frequency === 'daily' ? 7 : (habitForm.value.frequency === 'weekdays' ? 5 : 3),
    };
    
    if (habitForm.value.id) {
      await axios.put(`/api/goals/${habitForm.value.id}`, payload);
    } else {
      await axios.post('/api/goals', payload);
    }
    
    showHabitModal.value = false;
    habitForm.value = { title: '', frequency: 'daily' };
    loadHabits();
    if (window.TimeflowToast) window.TimeflowToast.success(habitForm.value.id ? 'Habit updated' : 'Habit created');
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
        <button class="outline-btn" type="button" @click="() => { habitForm = { title: '', frequency: 'daily' }; showHabitModal = true; }">+ Add habit</button>
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
          <div></div>
        </div>
        <div v-if="habits.length" class="habit-list">
          <div v-for="habit in habits" :key="habit.id" class="habit-row">
            <div class="habit-name">
              <span class="habit-dot" :style="{ background: habit.color }"></span>
              <span>{{ habit.name }}</span>
              <span class="habit-streak">{{ habit.streak }}</span>
            </div>
            <div v-for="(check, index) in habit.checks" :key="index" class="check-cell" @click="toggleCheck(habit, index)">
              <span class="check-box" :style="{ background: check ? habit.color : 'transparent', borderColor: habit.color, cursor: 'pointer' }"></span>
            </div>
            <div class="habit-actions">
              <button class="action-btn edit-btn" @click="editHabit(habit)">Edit</button>
              <button class="action-btn del-btn" @click="deleteHabit(habit)">Del</button>
            </div>
          </div>
        </div>
        <div v-else class="empty-state">No habits yet. Add one to start tracking.</div>
      </div>

      <div class="tf-card insight-card">
        <div class="tf-section-label">Insights</div>
        <div class="insight">Check back after 7 days for habit insights.</div>
      </div>

      <ModalBase :open="showHabitModal" :title="habitForm.id ? 'Edit Habit' : 'Add Habit'" @close="showHabitModal = false">
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
          <button class="primary-btn" type="button" @click="createHabit">{{ habitForm.id ? 'Save' : 'Add' }}</button>
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

.habit-list {
  display: flex;
  flex-direction: column;
}

.grid-header,
.habit-row {
  display: grid;
  grid-template-columns: 200px repeat(7, 1fr) 80px;
  align-items: center;
  gap: 8px;
}

.grid-header {
  font-size: 13px;
  color: var(--tf-text-hint);
  text-transform: uppercase;
  letter-spacing: 0.08em;
  padding-bottom: 12px;
  border-bottom: 1px solid var(--tf-border-default);
}

.habit-row {
  padding: 15px 0;
  border-bottom: 1px solid var(--tf-border-default);
}

.habit-row:last-child {
  border-bottom: none;
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

.habit-actions {
  display: flex;
  gap: 5px;
  justify-content: flex-end;
}

.action-btn {
  background: transparent;
  border: none;
  font-size: 13px;
  cursor: pointer;
  padding: 6px 10px;
  border-radius: 6px;
  font-weight: 600;
}

.edit-btn { 
  color: var(--tf-text-secondary); 
}
.edit-btn:hover { 
  background: var(--tf-border-default); 
  color: var(--tf-text-primary); 
}

.del-btn { 
  color: var(--tf-red); 
  opacity: 0.8; 
}
.del-btn:hover { 
  opacity: 1; 
  background: rgba(239, 68, 68, 0.1); 
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
