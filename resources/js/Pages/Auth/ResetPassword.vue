<script setup>
import axios from 'axios';
import { computed, ref } from 'vue';

const form = ref({
  password: '',
  password_confirmation: '',
});

const isSubmitting = ref(false);
const statusMessage = ref('');
const errorMessage = ref('');

const urlParams = computed(() => {
  if (typeof window === 'undefined') return new URL('http://localhost');
  return new URL(window.location.href);
});

const resetToken = computed(() => {
  const token = urlParams.value.searchParams.get('token');
  if (token) return token;
  const segments = urlParams.value.pathname.split('/').filter(Boolean);
  return segments.length > 1 ? segments[segments.length - 1] : '';
});

const emailAddress = computed(() => urlParams.value.searchParams.get('email') || '');

const submitReset = async () => {
  errorMessage.value = '';
  statusMessage.value = '';
  if (!form.value.password || !form.value.password_confirmation) {
    errorMessage.value = 'Please enter and confirm your password.';
    return;
  }
  if (form.value.password !== form.value.password_confirmation) {
    errorMessage.value = 'Passwords do not match.';
    return;
  }
  if (!resetToken.value || !emailAddress.value) {
    errorMessage.value = 'Reset link is missing required data.';
    return;
  }

  isSubmitting.value = true;
  try {
    await axios.get('/sanctum/csrf-cookie');
    await axios.post('/reset-password', {
      email: emailAddress.value,
      token: resetToken.value,
      password: form.value.password,
      password_confirmation: form.value.password_confirmation,
    });
    statusMessage.value = 'Password updated. You can sign in now.';
    if (import.meta.env?.MODE !== 'test') {
      window.location.assign('/login');
    }
  } catch (error) {
    errorMessage.value = 'Unable to reset password. Try again.';
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
      <div class="auth-title">Reset Password</div>
      <form class="auth-form" @submit.prevent="submitReset">
        <label class="field">
          <span>New password</span>
          <input type="password" data-testid="reset-password" v-model="form.password" />
        </label>
        <label class="field">
          <span>Confirm password</span>
          <input type="password" data-testid="reset-confirm" v-model="form.password_confirmation" />
        </label>
        <div v-if="statusMessage" class="auth-status">{{ statusMessage }}</div>
        <div v-if="errorMessage" class="auth-error">{{ errorMessage }}</div>
        <button class="primary-btn" type="submit" :disabled="isSubmitting">
          {{ isSubmitting ? 'Resetting...' : 'Reset password' }}
        </button>
      </form>
      <div class="auth-foot"><a href="/login">Back to login</a></div>
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
  padding: 20px;
  font-family: 'Plus Jakarta Sans', 'Segoe UI', sans-serif;
}

.auth-card {
  width: 420px;
  max-width: 100%;
  background: var(--tf-bg-card);
  border: 1px solid var(--tf-border-default);
  border-radius: 14px;
  padding: 28px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.auth-logo {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-size: 18px;
  font-weight: 800;
}

.logo-orb {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: var(--tf-violet);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #fff;
}

.auth-title {
  font-size: 16px;
  font-weight: 700;
  text-align: center;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 6px;
  font-size: 12px;
  color: var(--tf-text-secondary);
}

.field input {
  height: 40px;
  border-radius: 8px;
  border: 1px solid var(--tf-border-default);
  padding: 0 10px;
}

.auth-form {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.primary-btn {
  height: 44px;
  border-radius: 12px;
  border: none;
  background: var(--tf-violet);
  color: #fff;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
}

.primary-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.auth-status {
  font-size: 12px;
  color: var(--tf-mint);
  background: rgba(16, 185, 129, 0.12);
  border: 1px solid rgba(16, 185, 129, 0.3);
  border-radius: 8px;
  padding: 8px 10px;
}

.auth-error {
  font-size: 12px;
  color: var(--tf-red);
  background: rgba(248, 113, 113, 0.12);
  border: 1px solid rgba(248, 113, 113, 0.3);
  border-radius: 8px;
  padding: 8px 10px;
}

.auth-foot {
  font-size: 12px;
  color: var(--tf-text-secondary);
  text-align: center;
}

.auth-foot a {
  color: #5b3fd4;
}
</style>
