<script setup>
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';
import AppShell from '../Layouts/AppShell.vue';

const props = defineProps({
  navigation: {
    type: Object,
    default: () => ({ sections: [] }),
  },
});

const tasks = ref([]);
const planDate = ref(new Date());
const isSaving = ref(false);

const hasTasks = computed(() => tasks.value.length > 0);
const dateLabel = computed(() =>
  planDate.value.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' }),
);

const loadPlan = async () => {
  try {
    const response = await axios.get('/api/daily-plans/today', {
      params: { date: formatDateInput(planDate.value) },
    });
    const plan = response.data?.data;
    if (plan?.tasks?.length) {
      tasks.value = plan.tasks.map((task, index) => ({
        id: index + 1,
        text: task.text,
        done: task.done,
      }));
    } else {
      tasks.value = [];
    }
  } catch (error) {
    console.warn('Daily plan fetch failed', error);
  }
};

const savePlan = async () => {
  if (!tasks.value.length || isSaving.value) return;
  isSaving.value = true;

  try {
    await axios.post('/api/daily-plans', {
      date: formatDateInput(planDate.value),
      tasks: tasks.value.map((task) => ({ text: task.text, done: task.done })),
    });
  } catch (error) {
    console.warn('Daily plan save failed', error);
  } finally {
    isSaving.value = false;
  }
};

const toggleTask = (id) => {
  const task = tasks.value.find((item) => item.id === id);
  if (task) {
    task.done = !task.done;
    savePlan();
  }
};

onMounted(() => {
  loadPlan();
});

const formatDateInput = (date) => {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
};
</script>

<template>
  <div class="plans-page">
    <AppShell :navigation="props.navigation">
      <div class="page-header">
        <div>
          <div class="page-title">Daily Plan</div>
          <div class="page-subtitle">Focus on three priorities today.</div>
        </div>
        <div class="tf-date-badge">{{ dateLabel }}</div>
      </div>

      <div class="tf-card">
        <div class="plan-header">
          <div class="tf-section-label">Top 3 priorities</div>
          <div class="plan-xp">+30 XP</div>
        </div>
        <div v-if="hasTasks">
          <div v-for="task in tasks" :key="task.id" class="plan-row" @click="toggleTask(task.id)">
            <span class="plan-check" :class="{ done: task.done }"></span>
            <span class="plan-text" :class="{ done: task.done }">{{ task.text }}</span>
          </div>
        </div>
        <div v-else class="empty-state">No plan set for today yet.</div>
      </div>
    </AppShell>
  </div>
</template>

<style scoped>
.plans-page {
  min-height: 100vh;
  background: var(--tf-bg-page);
  padding: 14px;
  font-family: 'Plus Jakarta Sans', 'Segoe UI', sans-serif;
  color: var(--tf-text-primary);
}

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.page-title {
  font-size: 17px;
  font-weight: 800;
}

.page-subtitle {
  font-size: 12px;
  color: var(--tf-text-secondary);
}

.plan-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.plan-xp {
  font-size: 9px;
  font-weight: 700;
  color: #5b3fd4;
  background: rgba(124, 92, 252, 0.12);
  border: 1px solid rgba(124, 92, 252, 0.22);
  padding: 2px 6px;
  border-radius: 999px;
}

.plan-row {
  display: flex;
  gap: 8px;
  align-items: center;
  padding: 6px 0;
  border-bottom: 1px solid var(--tf-border-default);
}

.plan-row:last-child {
  border-bottom: none;
}

.plan-check {
  width: 16px;
  height: 16px;
  border-radius: 4px;
  border: 1.5px solid var(--tf-border-default);
}

.plan-check.done {
  background: var(--tf-mint);
  border-color: var(--tf-mint);
}

.plan-text {
  font-size: 12px;
}

.plan-text.done {
  text-decoration: line-through;
  color: var(--tf-text-hint);
}

.empty-state {
  font-size: 12px;
  color: var(--tf-text-hint);
  padding: 12px 0;
}
</style>
