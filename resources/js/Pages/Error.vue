<script setup>
import { computed } from 'vue';

const props = defineProps({
  status: Number,
});

const title = computed(() => {
  return {
    503: '503: Service Unavailable',
    500: '500: Server Error',
    404: '404: Page Not Found',
    403: '403: Forbidden',
  }[props.status] || 'Error';
});

const description = computed(() => {
  return {
    503: 'Sorry, we are doing some maintenance. Please check back soon.',
    500: 'Whoops, something went wrong on our servers.',
    404: 'Sorry, the page you are looking for could not be found.',
    403: 'Sorry, you are forbidden from accessing this page.',
  }[props.status] || 'An unexpected error occurred.';
});
</script>

<template>
  <div class="error-page">
    <div class="error-content">
      <div class="error-code">{{ status }}</div>
      <div class="error-title">{{ title }}</div>
      <div class="error-desc">{{ description }}</div>
      <a href="/" class="primary-btn">Return Home</a>
    </div>
  </div>
</template>

<style scoped>
.error-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--tf-bg-page, #0A0A0A);
  color: var(--tf-text-primary, #FFFFFF);
  font-family: 'Plus Jakarta Sans', 'Segoe UI', sans-serif;
  padding: 20px;
}
.error-content {
  text-align: center;
  max-width: 400px;
}
.error-code {
  font-size: 80px;
  font-weight: 800;
  line-height: 1;
  background: linear-gradient(135deg, #7C5CFC, #45E6B0);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  margin-bottom: 20px;
}
.error-title {
  font-size: 24px;
  font-weight: 700;
  margin-bottom: 10px;
}
.error-desc {
  font-size: 15px;
  color: var(--tf-text-secondary, #A1A1AA);
  margin-bottom: 30px;
}
.primary-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 44px;
  padding: 0 20px;
  border-radius: 12px;
  background: var(--tf-violet, #7C5CFC);
  color: #fff;
  font-weight: 600;
  text-decoration: none;
  font-size: 15px;
  transition: opacity 0.2s;
}
.primary-btn:hover {
  opacity: 0.9;
}
</style>
