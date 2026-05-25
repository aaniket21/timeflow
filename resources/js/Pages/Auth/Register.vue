<script setup>
import axios from 'axios';
import { ref } from 'vue';

const form = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
});

const isSubmitting = ref(false);
const errorMessage = ref('');

const submitRegister = async () => {
  errorMessage.value = '';
  if (!form.value.name || !form.value.email || !form.value.password || !form.value.password_confirmation) {
    errorMessage.value = 'Please fill out all fields.';
    return;
  }

  if (form.value.password !== form.value.password_confirmation) {
    errorMessage.value = 'Passwords do not match.';
    return;
  }

  isSubmitting.value = true;
  try {
    let detectedTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC';
    
    // Map deprecated timezone aliases to modern equivalents
    const deprecatedTimezones = {
      'Asia/Calcutta': 'Asia/Kolkata',
      'Asia/Katmandu': 'Asia/Kathmandu',
      'Asia/Rangoon': 'Asia/Yangon',
      'Asia/Saigon': 'Asia/Ho_Chi_Minh',
      'Asia/Macao': 'Asia/Macau',
      'Africa/Asmera': 'Africa/Asmara'
    };
    detectedTimezone = deprecatedTimezones[detectedTimezone] || detectedTimezone;

    await axios.get('/sanctum/csrf-cookie');
    await axios.post('/register', {
      name: form.value.name,
      email: form.value.email,
      password: form.value.password,
      password_confirmation: form.value.password_confirmation,
      timezone: detectedTimezone,
    });
    if (import.meta.env?.MODE !== 'test') {
      window.location.assign('/dashboard');
    }
  } catch (error) {
    errorMessage.value = 'Unable to create account. Please try again.';
  } finally {
    isSubmitting.value = false;
  }
};
</script>

<template>
  <div class="auth-page">
    <div class="auth-card">
      <div class="auth-logo">
        <span class="logo-orb"><i class="ti ti-clock" aria-hidden="true"></i></span>
        TimeFlow
      </div>
      <div class="auth-title">Create Account</div>
      <form class="auth-form" @submit.prevent="submitRegister">
        <label class="field">
          <span>Full name</span>
          <input type="text" data-testid="register-name" v-model="form.name" />
        </label>
        <label class="field">
          <span>Email</span>
          <input type="email" data-testid="register-email" v-model="form.email" />
        </label>
        <label class="field">
          <span>Password</span>
          <input type="password" data-testid="register-password" v-model="form.password" />
        </label>
        <label class="field">
          <span>Confirm password</span>
          <input type="password" data-testid="register-confirm" v-model="form.password_confirmation" />
        </label>
        <div v-if="errorMessage" class="auth-error">{{ errorMessage }}</div>
        <button class="primary-btn" type="submit" :disabled="isSubmitting">
          {{ isSubmitting ? 'Creating...' : 'Create Account' }}
        </button>
      </form>
      <div class="auth-foot">Already have an account? <a href="/login">Sign in</a></div>
    </div>
  </div>
</template>

<style scoped>
.auth-page {
  min-height: 100vh;
  background: var(--tf-bg-page);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 25px;
  font-family: 'Plus Jakarta Sans', 'Segoe UI', sans-serif;
}

.auth-card {
  width: 525px;
  max-width: 100%;
  background: var(--tf-bg-card);
  border: 1px solid var(--tf-border-default);
  border-radius: 18px;
  padding: 35px;
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.auth-logo {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  font-size: 23px;
  font-weight: 800;
}

.logo-orb {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: var(--tf-violet);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #fff;
}

.auth-title {
  font-size: 20px;
  font-weight: 700;
  text-align: center;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 8px;
  font-size: 15px;
  color: var(--tf-text-secondary);
}

.field input {
  height: 50px;
  border-radius: 10px;
  border: 1px solid var(--tf-border-default);
  padding: 0 13px;
}

.auth-form {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.primary-btn {
  height: 55px;
  border-radius: 15px;
  border: none;
  background: var(--tf-violet);
  color: #fff;
  font-size: 18px;
  font-weight: 600;
  cursor: pointer;
}

.primary-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.auth-error {
  font-size: 15px;
  color: var(--tf-red);
  background: rgba(248, 113, 113, 0.12);
  border: 1px solid rgba(248, 113, 113, 0.3);
  border-radius: 10px;
  padding: 10px 13px;
}

.auth-foot {
  font-size: 15px;
  color: var(--tf-text-secondary);
  text-align: center;
}

.auth-foot a {
  color: #5b3fd4;
}
</style>
