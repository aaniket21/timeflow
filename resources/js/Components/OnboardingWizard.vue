<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';
import TfModal from './TfModal.vue';

const props = defineProps({
  isOpen: Boolean,
});

const emit = defineEmits(['close', 'start-timer']);

const step = ref(1);

// Step 1
const detectedTimezone = ref(Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC');

// Step 2
const projectName = ref('');
const projectColor = ref('#7C5CFC');

const handleTimezoneConfirm = async () => {
  try {
    await axios.put('/api/settings/preferences', { timezone: detectedTimezone.value });
    step.value = 2;
  } catch (error) {
    console.warn('Failed to save timezone', error);
    step.value = 2;
  }
};

const handleProjectCreate = async () => {
  if (!projectName.value.trim()) return;
  try {
    const res = await axios.post('/api/projects', {
      name: projectName.value,
      color: projectColor.value,
    });
    localStorage.setItem('timeflow_onboarded', '1');
    emit('start-timer', res.data.data.id);
    emit('close');
  } catch (error) {
    console.warn('Failed to create project', error);
    localStorage.setItem('timeflow_onboarded', '1');
    emit('close');
  }
};

const skip = () => {
  localStorage.setItem('timeflow_onboarded', '1');
  emit('close');
};
</script>

<template>
  <TfModal :isOpen="isOpen" :title="'Welcome to Timeflow!'" @close="skip">
    <div v-if="step === 1" class="wizard-step">
      <h4>Step 1: Set your timezone</h4>
      <p>We detected your timezone as <strong>{{ detectedTimezone }}</strong>. Is this correct?</p>
      <div class="field">
        <label class="field-label">Timezone</label>
        <select class="text-input" v-model="detectedTimezone">
          <option :value="detectedTimezone">{{ detectedTimezone }}</option>
          <option value="UTC">UTC</option>
          <option value="America/New_York">America/New_York</option>
          <option value="Europe/London">Europe/London</option>
          <option value="Asia/Tokyo">Asia/Tokyo</option>
        </select>
      </div>
      <div class="wizard-actions">
        <button class="outline-btn" type="button" @click="skip">Skip</button>
        <button class="primary-btn" type="button" @click="handleTimezoneConfirm">Looks good</button>
      </div>
    </div>
    <div v-if="step === 2" class="wizard-step">
      <h4>Step 2: Create your first project</h4>
      <p>What are you working on right now?</p>
      <div class="field">
        <label class="field-label">Project Name</label>
        <input class="text-input" type="text" v-model="projectName" placeholder="e.g. Work, Study, Side Hustle" @keyup.enter="handleProjectCreate" />
      </div>
      <div class="field">
        <label class="field-label">Project Color</label>
        <input type="color" v-model="projectColor" class="color-picker" />
      </div>
      <div class="wizard-actions">
        <button class="outline-btn" type="button" @click="skip">Skip</button>
        <button class="primary-btn" type="button" @click="handleProjectCreate" :disabled="!projectName.trim()">Create & Start</button>
      </div>
    </div>
  </TfModal>
</template>

<style scoped>
.wizard-step {
  display: flex;
  flex-direction: column;
  gap: 15px;
}
.wizard-step h4 {
  margin: 0;
  font-size: 18px;
}
.wizard-step p {
  margin: 0;
  color: var(--tf-text-secondary);
  font-size: 15px;
}
.field {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.field-label {
  font-size: 14px;
  font-weight: 600;
}
.text-input {
  height: 44px;
  border-radius: 10px;
  border: 1px solid var(--tf-border-default);
  background: var(--tf-bg-surface);
  color: var(--tf-text-primary);
  padding: 0 15px;
  font-size: 15px;
  width: 100%;
}
.color-picker {
  height: 44px;
  width: 60px;
  border-radius: 10px;
  border: 1px solid var(--tf-border-default);
  padding: 2px;
  cursor: pointer;
}
.wizard-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 10px;
}
.outline-btn {
  height: 44px;
  padding: 0 15px;
  border-radius: 10px;
  border: 1px solid var(--tf-border-default);
  background: transparent;
  color: var(--tf-text-primary);
  font-weight: 600;
  cursor: pointer;
}
.primary-btn {
  height: 44px;
  padding: 0 20px;
  border-radius: 10px;
  border: none;
  background: var(--tf-violet);
  color: #fff;
  font-weight: 600;
  cursor: pointer;
}
.primary-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
