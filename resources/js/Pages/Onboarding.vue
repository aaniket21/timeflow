<script setup>
import { ref } from 'vue';

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
    completed_at: new Date().toISOString(),
  };
  localStorage.setItem('timeflow_onboarding', JSON.stringify(payload));
  if (import.meta.env?.MODE !== 'test') {
    window.location.assign('/');
  }
};

const skipOnboarding = () => {
  localStorage.setItem('timeflow_onboarding', JSON.stringify({ skipped: true, completed_at: new Date().toISOString() }));
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
  padding: 20px;
  font-family: 'Plus Jakarta Sans', 'Segoe UI', sans-serif;
  color: var(--tf-text-primary);
}

.onboard-card {
  width: 480px;
  max-width: 100%;
  background: var(--tf-bg-card);
  border: 1px solid var(--tf-border-default);
  border-radius: 16px;
  padding: 32px;
}

.progress-dots {
  display: flex;
  gap: 8px;
  margin-bottom: 20px;
}

.progress-dots .dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  border: 1px solid var(--tf-border-default);
}

.progress-dots .dot.active {
  background: var(--tf-violet);
  border-color: var(--tf-violet);
}

.title {
  font-size: 20px;
  font-weight: 800;
}

.subtitle {
  font-size: 13px;
  color: var(--tf-text-secondary);
  margin-top: 6px;
}

.role-list {
  display: grid;
  gap: 10px;
  margin-top: 16px;
}

.role-card {
  padding: 14px;
  border-radius: 10px;
  border: 1px solid var(--tf-border-default);
  background: transparent;
  font-size: 13px;
  text-align: left;
  cursor: pointer;
}

.primary-btn {
  margin-top: 16px;
  width: 100%;
  height: 44px;
  border-radius: 12px;
  border: none;
  background: var(--tf-violet);
  color: #fff;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
}

.goal-value {
  font-size: 44px;
  font-weight: 700;
  font-family: 'JetBrains Mono', 'Cascadia Code', monospace;
  color: var(--tf-violet);
  margin-top: 16px;
}

.goal-slider {
  width: 100%;
  margin-top: 12px;
}

.mini-form {
  display: grid;
  gap: 10px;
  margin-top: 16px;
}

.text-input {
  width: 100%;
  padding: 10px 12px;
  border-radius: 8px;
  border: 1px solid var(--tf-border-default);
  background: var(--tf-bg-card-alt);
  font-size: 13px;
}

.link-btn {
  margin-top: 10px;
  border: none;
  background: transparent;
  color: var(--tf-text-secondary);
  font-size: 12px;
  cursor: pointer;
}
</style>
