<script setup>
defineProps({
  icon: { type: String, default: 'ti-inbox' },
  heading: { type: String, default: 'Nothing here yet' },
  description: { type: String, default: '' },
  ctaLabel: { type: String, default: '' },
});

const emit = defineEmits(['action']);
</script>

<template>
  <div class="empty-state">
    <div class="empty-icon"><i class="ti" :class="icon" aria-hidden="true"></i></div>
    <div class="empty-heading">{{ heading }}</div>
    <div v-if="description" class="empty-desc">{{ description }}</div>
    <button v-if="ctaLabel" class="empty-cta" type="button" @click="emit('action')">{{ ctaLabel }}</button>
  </div>
</template>

<style>
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 40px 20px;
  text-align: center;
}

.empty-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: rgba(124, 92, 252, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  color: var(--tf-violet);
  margin-bottom: 12px;
}

.empty-heading {
  font-size: 14px;
  font-weight: 700;
  color: var(--tf-text-primary);
}

.empty-desc {
  font-size: 12px;
  color: var(--tf-text-secondary);
  margin-top: 4px;
  max-width: 260px;
}

.empty-cta {
  margin-top: 14px;
  padding: 8px 18px;
  border-radius: 9px;
  border: none;
  background: var(--tf-violet);
  color: #fff;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: opacity 0.2s ease;
}

.empty-cta:hover {
  opacity: 0.9;
}
</style>
