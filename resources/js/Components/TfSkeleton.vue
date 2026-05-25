<script setup>
defineProps({
  width: {
    type: String,
    default: '100%',
  },
  height: {
    type: String,
    default: '20px',
  },
  borderRadius: {
    type: String,
    default: '8px',
  }
});
</script>

<template>
  <div 
    class="tf-skeleton" 
    :style="{ width, height, borderRadius }"
  ></div>
</template>

<style scoped>
.tf-skeleton {
  background: var(--tf-border-default);
  animation: pulse 1.5s infinite ease-in-out;
}

@keyframes pulse {
  0% { opacity: 1; }
  50% { opacity: 0.4; }
  100% { opacity: 1; }
}
</style>
