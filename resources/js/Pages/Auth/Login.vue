<script setup>
import axios from 'axios';
import { ref } from 'vue';

const form = ref({
  email: '',
  password: '',
});

const isSubmitting = ref(false);
const errorMessage = ref('');

const submitLogin = async () => {
  errorMessage.value = '';
  if (!form.value.email || !form.value.password) {
    errorMessage.value = 'Email and password are required.';
    return;
  }

  isSubmitting.value = true;
  try {
    await axios.get('/sanctum/csrf-cookie');
    await axios.post('/login', {
      email: form.value.email,
      password: form.value.password,
    });
    if (import.meta.env?.MODE !== 'test') {
      window.location.assign('/dashboard');
    }
  } catch (error) {
    errorMessage.value = 'Unable to sign in. Check your credentials and try again.';
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
      <div class="auth-title">Sign In</div>
      <form class="auth-form" @submit.prevent="submitLogin">
        <label class="field">
          <span>Email</span>
          <input type="email" data-testid="login-email" v-model="form.email" />
        </label>
        <label class="field">
          <span>Password</span>
          <input type="password" data-testid="login-password" v-model="form.password" />
        </label>
        <a class="auth-link" href="/forgot-password">Forgot password?</a>
        <div v-if="errorMessage" class="auth-error">{{ errorMessage }}</div>
        <button class="primary-btn" type="submit" :disabled="isSubmitting">
          {{ isSubmitting ? 'Signing in...' : 'Sign In' }}
        </button>
      </form>
      <div class="auth-foot">Do not have an account? <a href="/register">Sign up</a></div>
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

.auth-link {
  font-size: 12px;
  color: #5b3fd4;
  text-align: right;
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
