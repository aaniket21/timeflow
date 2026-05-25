<script setup>
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';
import AppShell from '../Layouts/AppShell.vue';
import TfModal from '../Components/TfModal.vue';
import { useTime } from '../composables/useTime';

const { daysUntil, formatDate } = useTime();

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
        const daysRemaining = daysUntil(exam.exam_date);
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
  return formatDate(dateString, 'MMM D');
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

      <TfModal :isOpen="showGoalModal" title="Create Goal" @close="showGoalModal = false">
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
      </TfModal>

      <TfModal :isOpen="showExamModal" title="Add Exam" @close="showExamModal = false">
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
      </TfModal>
    </AppShell>
  </div>
</template>

<style scoped>
.goals-page {
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

.primary-btn {
  height: 50px;
  padding: 0 20px;
  border-radius: 13px;
  border: none;
  background: var(--tf-violet);
  color: #fff;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
}

.section-title {
  margin-top: 18px;
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.09em;
  color: var(--tf-text-hint);
}

.goal-list,
.exam-list {
  display: flex;
  flex-direction: column;
  gap: 13px;
  margin-top: 13px;
}

.goal-card {
  display: grid;
  grid-template-columns: 40px 1fr 75px;
  align-items: center;
  gap: 15px;
}

.goal-card.hit {
  border-color: rgba(14, 207, 164, 0.4);
}

.goal-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: rgba(124, 92, 252, 0.12);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--tf-violet);
}

.goal-title {
  font-size: 16px;
  font-weight: 700;
}

.goal-target {
  font-size: 14px;
  color: var(--tf-text-secondary);
}

.goal-bar {
  height: 5px;
  border-radius: 1249px;
  background: rgba(80, 60, 20, 0.08);
  margin-top: 8px;
  overflow: hidden;
}

.goal-fill {
  display: block;
  height: 100%;
  background: var(--tf-violet);
}

.goal-ring {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  border: 3px solid rgba(124, 92, 252, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
}

.goal-percent {
  font-size: 14px;
  font-weight: 700;
  font-family: 'JetBrains Mono', 'Cascadia Code', monospace;
}

.exam-card {
  display: grid;
  grid-template-columns: 5px 1fr auto;
  gap: 15px;
  align-items: center;
}

.exam-accent {
  width: 5px;
  border-radius: 3px;
  height: 100%;
}

.exam-title {
  font-size: 18px;
  font-weight: 700;
}

.exam-date {
  font-size: 15px;
  color: var(--tf-text-secondary);
}

.exam-days {
  font-size: 13px;
  font-weight: 700;
}

.exam-actions {
  display: flex;
  gap: 8px;
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
  margin-top: 10px;
}

.empty-state {
  font-size: 15px;
  color: var(--tf-text-hint);
  padding: 15px 5px;
}
</style>
