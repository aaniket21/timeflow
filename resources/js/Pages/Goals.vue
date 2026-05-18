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

const goals = ref([]);
const exams = ref([]);
const showGoalModal = ref(false);
const showExamModal = ref(false);
const goalForm = ref({ title: '', type: 'daily_hours', target_value: '' });
const examForm = ref({ subject: '', exam_date: '' });

const loadGoals = async () => {
  try {
    const response = await axios.get('/api/goals/summary');
    const data = response.data?.data?.goals || [];
    goals.value = data.map((goal) => ({
      ...goal,
      targetLabel: formatGoalTarget(goal.type, goal.target_value),
    }));
  } catch (error) {
    console.warn('Goal summary fetch failed', error);
  }
};

const loadExams = async () => {
  try {
    const response = await axios.get('/api/exams');
    if (Array.isArray(response.data?.data)) {
      exams.value = response.data.data.map((exam) => {
        const daysRemaining = Math.max(0, Math.ceil((new Date(exam.exam_date) - new Date()) / 86400000));
        const urgency = daysRemaining <= 7 ? 'var(--tf-red)' : daysRemaining <= 14 ? 'var(--tf-amber)' : 'var(--tf-sky)';
        return {
          id: exam.id,
          subject: exam.subject,
          dateLabel: formatExamDate(exam.exam_date),
          daysLabel: daysRemaining === 1 ? '1 day' : `${daysRemaining} days`,
          urgency,
        };
      });
    }
  } catch (error) {
    console.warn('Exam fetch failed', error);
  }
};

const hasGoals = computed(() => goals.value.length > 0);
const hasExams = computed(() => exams.value.length > 0);

onMounted(() => {
  loadGoals();
  loadExams();
});

const formatGoalTarget = (type, value) => {
  const hours = Number(value || 0);
  if (type === 'weekly_hours') {
    return `${hours}h/week`;
  }

  if (type === 'focus_hours') {
    return `${hours}h focus`;
  }

  return `${hours}h/day`;
};

const formatExamDate = (dateString) => {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
};

const createGoal = async () => {
  try {
    await axios.post('/api/goals', goalForm.value);
    showGoalModal.value = false;
    goalForm.value = { title: '', type: 'daily_hours', target_value: '' };
    loadGoals();
    if (window.TimeflowToast) window.TimeflowToast.success('Goal created');
  } catch (error) {
    if (window.TimeflowToast) window.TimeflowToast.error('Failed to create goal');
  }
};

const createExam = async () => {
  try {
    await axios.post('/api/exams', examForm.value);
    showExamModal.value = false;
    examForm.value = { subject: '', exam_date: '' };
    loadExams();
    if (window.TimeflowToast) window.TimeflowToast.success('Exam added');
  } catch (error) {
    if (window.TimeflowToast) window.TimeflowToast.error('Failed to add exam');
  }
};

const deleteExam = async (id) => {
  try {
    await axios.delete(`/api/exams/${id}`);
    loadExams();
    if (window.TimeflowToast) window.TimeflowToast.success('Exam deleted');
  } catch (error) {
    console.warn('Delete exam failed', error);
  }
};
</script>

<template>
  <div class="goals-page">
    <AppShell :navigation="props.navigation">
      <div class="page-header">
        <div>
          <div class="page-title">Goals</div>
          <div class="page-subtitle">Track progress across goals and exams.</div>
        </div>
        <button class="primary-btn" type="button" @click="showGoalModal = true">Create goal</button>
      </div>

      <div class="section-title">Goals</div>
      <div class="goal-list">
        <div v-if="hasGoals">
          <div v-for="goal in goals" :key="goal.id" class="tf-card goal-card" :class="{ hit: goal.hit }">
            <div class="goal-icon"><i class="ti ti-target" aria-hidden="true"></i></div>
            <div class="goal-body">
              <div class="goal-title">{{ goal.title }}</div>
              <div class="goal-target">{{ goal.targetLabel }}</div>
              <div class="goal-bar">
                <span class="goal-fill" :style="{ width: Math.min(goal.progress_percent, 100) + '%' }"></span>
              </div>
            </div>
            <div class="goal-ring">
              <div class="goal-percent">{{ goal.progress_percent }}%</div>
            </div>
          </div>
        </div>
        <div v-else class="empty-state">No goals yet. Create your first one.</div>
      </div>

      <div class="section-title">Upcoming Exams</div>
      <div class="exam-actions">
        <button class="outline-btn" type="button" @click="showExamModal = true">+ Add exam</button>
      </div>
      <div class="exam-list">
        <div v-if="hasExams">
          <div v-for="exam in exams" :key="exam.id" class="tf-card exam-card">
            <div class="exam-accent" :style="{ background: exam.urgency }"></div>
            <div class="exam-body">
              <div class="exam-title">{{ exam.subject }}</div>
              <div class="exam-date">{{ exam.dateLabel }}</div>
              <div class="exam-days" :style="{ color: exam.urgency }">{{ exam.daysLabel }}</div>
            </div>
            <div class="exam-actions">
              <button class="tf-icon-button" type="button" aria-label="Edit exam"><i class="ti ti-edit" aria-hidden="true"></i></button>
              <button class="tf-icon-button" type="button" aria-label="Delete exam" @click="deleteExam(exam.id)"><i class="ti ti-trash" aria-hidden="true"></i></button>
            </div>
          </div>
        </div>
        <div v-else class="empty-state">No upcoming exams.</div>
      </div>

      <ModalBase :open="showGoalModal" title="Create Goal" @close="showGoalModal = false">
        <div class="field">
          <label class="field-label">Goal title</label>
          <input class="text-input" type="text" v-model="goalForm.title" placeholder="e.g. Study 6h daily" />
        </div>
        <div class="field">
          <label class="field-label">Type</label>
          <select class="text-input" v-model="goalForm.type">
            <option value="daily_hours">Daily hours</option>
            <option value="weekly_hours">Weekly hours</option>
            <option value="focus_hours">Focus hours</option>
          </select>
        </div>
        <div class="field">
          <label class="field-label">Target (hours)</label>
          <input class="text-input" type="number" v-model="goalForm.target_value" placeholder="e.g. 6" />
        </div>
        <template #footer>
          <button class="outline-btn" type="button" @click="showGoalModal = false">Cancel</button>
          <button class="primary-btn" type="button" @click="createGoal">Create</button>
        </template>
      </ModalBase>

      <ModalBase :open="showExamModal" title="Add Exam" @close="showExamModal = false">
        <div class="field">
          <label class="field-label">Subject</label>
          <input class="text-input" type="text" v-model="examForm.subject" placeholder="e.g. Mathematics" />
        </div>
        <div class="field">
          <label class="field-label">Exam date</label>
          <input class="text-input" type="date" v-model="examForm.exam_date" />
        </div>
        <template #footer>
          <button class="outline-btn" type="button" @click="showExamModal = false">Cancel</button>
          <button class="primary-btn" type="button" @click="createExam">Add</button>
        </template>
      </ModalBase>
    </AppShell>
  </div>
</template>

<style scoped>
.goals-page {
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

.primary-btn {
  height: 40px;
  padding: 0 16px;
  border-radius: 10px;
  border: none;
  background: var(--tf-violet);
  color: #fff;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
}

.section-title {
  margin-top: 14px;
  font-size: 10px;
  text-transform: uppercase;
  letter-spacing: 0.09em;
  color: var(--tf-text-hint);
}

.goal-list,
.exam-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-top: 10px;
}

.goal-card {
  display: grid;
  grid-template-columns: 32px 1fr 60px;
  align-items: center;
  gap: 12px;
}

.goal-card.hit {
  border-color: rgba(14, 207, 164, 0.4);
}

.goal-icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: rgba(124, 92, 252, 0.12);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--tf-violet);
}

.goal-title {
  font-size: 13px;
  font-weight: 700;
}

.goal-target {
  font-size: 11px;
  color: var(--tf-text-secondary);
}

.goal-bar {
  height: 4px;
  border-radius: 999px;
  background: rgba(80, 60, 20, 0.08);
  margin-top: 6px;
  overflow: hidden;
}

.goal-fill {
  display: block;
  height: 100%;
  background: var(--tf-violet);
}

.goal-ring {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: 2px solid rgba(124, 92, 252, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
}

.goal-percent {
  font-size: 11px;
  font-weight: 700;
  font-family: 'JetBrains Mono', 'Cascadia Code', monospace;
}

.exam-card {
  display: grid;
  grid-template-columns: 4px 1fr auto;
  gap: 12px;
  align-items: center;
}

.exam-accent {
  width: 4px;
  border-radius: 2px;
  height: 100%;
}

.exam-title {
  font-size: 14px;
  font-weight: 700;
}

.exam-date {
  font-size: 12px;
  color: var(--tf-text-secondary);
}

.exam-days {
  font-size: 10px;
  font-weight: 700;
}

.exam-actions {
  display: flex;
  gap: 6px;
}

.outline-btn {
  height: 36px;
  padding: 0 12px;
  border-radius: 10px;
  border: 1px solid var(--tf-border-default);
  background: transparent;
  color: var(--tf-text-secondary);
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  margin-top: 8px;
}

.empty-state {
  font-size: 12px;
  color: var(--tf-text-hint);
  padding: 12px 4px;
}
</style>
