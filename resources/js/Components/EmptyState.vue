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
  padding: 50px 25px;
  text-align: center;
}

.empty-icon {
  width: 60px;
  height: 60px;
  border-radius: 15px;
  background: rgba(124, 92, 252, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  color: var(--tf-violet);
  margin-bottom: 15px;
}

.empty-heading {
  font-size: 18px;
  font-weight: 700;
  color: var(--tf-text-primary);
}

.empty-desc {
  font-size: 15px;
  color: var(--tf-text-secondary);
  margin-top: 5px;
  max-width: 325px;
}

.empty-cta {
  margin-top: 18px;
  padding: 10px 23px;
  border-radius: 11px;
  border: none;
  background: var(--tf-violet);
  color: #fff;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: opacity 0.2s ease;
}

.empty-cta:hover {
  opacity: 0.9;
}
</style>
