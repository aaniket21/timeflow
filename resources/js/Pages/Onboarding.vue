<script setup>
import { ref } from 'vue';
import { useTime } from '../composables/useTime';

const { now } = useTime();

const step = ref(1);
const selectedRole = ref('');
const dailyGoal = ref(6);
const firstItem = ref('');

const nextStep = () => {
  if (step.value === 1 && !selectedRole.value) return;
  if (step.value < 3) step.value += 1;
};

const selectRole = (role) => {
  selectedRole.value = role;
};

const completeOnboarding = () => {
  const payload = {
    role: selectedRole.value,
    daily_goal_hours: Number(dailyGoal.value || 0),
    first_item: firstItem.value,
    completed_at: now().toISOString(),
  };
  localStorage.setItem('timeflow_onboarding', JSON.stringify(payload));
  if (import.meta.env?.MODE !== 'test') {
    window.location.assign('/');
  }
};

const skipOnboarding = () => {
  localStorage.setItem('timeflow_onboarding', JSON.stringify({ skipped: true, completed_at: now().toISOString() }));
  if (import.meta.env?.MODE !== 'test') {
    window.location.assign('/');
  }
};
</script>

<template>
  <div class="onboarding-page">
    <div class="onboard-card">
      <div class="progress-dots">
        <span class="dot" :class="{ active: step >= 1 }"></span>
        <span class="dot" :class="{ active: step >= 2 }"></span>
        <span class="dot" :class="{ active: step >= 3 }"></span>
      </div>

      <div v-if="step === 1">
        <div class="title">Who best describes you?</div>
        <div class="subtitle">We will tune TimeFlow for your workflow.</div>
        <div class="role-list">
          <button class="role-card" type="button" data-testid="role-student" @click="selectRole('student')">Student</button>
          <button class="role-card" type="button" data-testid="role-freelancer" @click="selectRole('freelancer')">Freelancer</button>
          <button class="role-card" type="button" data-testid="role-remote" @click="selectRole('remote')">Remote worker</button>
        </div>
        <button class="primary-btn" type="button" data-testid="onboarding-next" @click="nextStep">Next</button>
      </div>

      <div v-else-if="step === 2">
        <div class="title">Daily goal</div>
        <div class="subtitle">How many hours do you want to log per day?</div>
        <div class="goal-value">{{ dailyGoal }}</div>
        <input class="goal-slider" type="range" min="1" max="12" v-model="dailyGoal" data-testid="goal-slider" />
        <button class="primary-btn" type="button" data-testid="onboarding-next" @click="nextStep">Next</button>
      </div>

      <div v-else>
        <div class="title">Set up your first item</div>
        <div class="subtitle">Add your first block, project, or category.</div>
        <div class="mini-form">
          <input class="text-input" type="text" placeholder="Name" data-testid="first-item" v-model="firstItem" />
          <button class="primary-btn" type="button" data-testid="onboarding-finish" @click="completeOnboarding">Finish setup</button>
        </div>
        <button class="link-btn" type="button" data-testid="onboarding-skip" @click="skipOnboarding">Skip for now</button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.onboarding-page {
  min-height: 100vh;
  background: var(--tf-bg-page);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 25px;
  font-family: 'Plus Jakarta Sans', 'Segoe UI', sans-serif;
  color: var(--tf-text-primary);
}

.onboard-card {
  width: 600px;
  max-width: 100%;
  background: var(--tf-bg-card);
  border: 1px solid var(--tf-border-default);
  border-radius: 20px;
  padding: 40px;
}

.progress-dots {
  display: flex;
  gap: 10px;
  margin-bottom: 25px;
}

.progress-dots .dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  border: 1px solid var(--tf-border-default);
}

.progress-dots .dot.active {
  background: var(--tf-violet);
  border-color: var(--tf-violet);
}

.title {
  font-size: 25px;
  font-weight: 800;
}

.subtitle {
  font-size: 16px;
  color: var(--tf-text-secondary);
  margin-top: 8px;
}

.role-list {
  display: grid;
  gap: 13px;
  margin-top: 20px;
}

.role-card {
  padding: 18px;
  border-radius: 13px;
  border: 1px solid var(--tf-border-default);
  background: transparent;
  font-size: 16px;
  text-align: left;
  cursor: pointer;
}

.primary-btn {
  margin-top: 20px;
  width: 100%;
  height: 55px;
  border-radius: 15px;
  border: none;
  background: var(--tf-violet);
  color: #fff;
  font-size: 18px;
  font-weight: 600;
  cursor: pointer;
}

.goal-value {
  font-size: 55px;
  font-weight: 700;
  font-family: 'JetBrains Mono', 'Cascadia Code', monospace;
  color: var(--tf-violet);
  margin-top: 20px;
}

.goal-slider {
  width: 100%;
  margin-top: 15px;
}

.mini-form {
  display: grid;
  gap: 13px;
  margin-top: 20px;
}

.text-input {
  width: 100%;
  padding: 13px 15px;
  border-radius: 10px;
  border: 1px solid var(--tf-border-default);
  background: var(--tf-bg-card-alt);
  font-size: 16px;
}

.link-btn {
  margin-top: 13px;
  border: none;
  background: transparent;
  color: var(--tf-text-secondary);
  font-size: 15px;
  cursor: pointer;
}
</style>
