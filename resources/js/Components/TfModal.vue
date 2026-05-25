<script setup>
const props = defineProps({
  isOpen: Boolean,
  title: String,
});

const emit = defineEmits(['close']);
</script>

<template>
  <Teleport to="body">
    <div v-if="isOpen" class="tf-modal-overlay" @click.self="emit('close')">
      <div class="tf-modal-content">
        <div class="tf-modal-header">
          <h3 class="tf-modal-title">{{ title }}</h3>
          <button class="tf-icon-button" type="button" aria-label="Close modal" @click="emit('close')">
            <i class="ti ti-x" aria-hidden="true"></i>
          </button>
        </div>
        <div class="tf-modal-body">
          <slot />
        </div>
        <div v-if="$slots.footer" class="tf-modal-footer">
          <slot name="footer" />
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.tf-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
  backdrop-filter: blur(2px);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
  padding: 20px;
  animation: tf-fade-in 0.2s ease;
}

.tf-modal-content {
  background: var(--tf-bg-card);
  border-radius: 18px;
  width: 100%;
  max-width: 500px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
  animation: tf-scale-in 0.2s ease;
}

.tf-modal-header {
  padding: 18px 20px;
  border-bottom: 1px solid var(--tf-border-default);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.tf-modal-title {
  font-size: 18px;
  font-weight: 700;
  margin: 0;
}

.tf-modal-body {
  padding: 20px;
  overflow-y: auto;
}

.tf-modal-footer {
  padding: 15px 20px;
  border-top: 1px solid var(--tf-border-default);
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.tf-icon-button {
  width: 44px;
  height: 44px;
  border: none;
  background: transparent;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--tf-text-secondary);
  transition: background 0.2s, color 0.2s;
}

.tf-icon-button:hover {
  background: var(--tf-bg-hover);
  color: var(--tf-text-primary);
}

@media (max-width: 768px) {
  .tf-modal-overlay {
    align-items: flex-end;
    padding: 0;
  }
  .tf-modal-content {
    border-radius: 20px 20px 0 0;
    max-height: 85vh;
    animation: tf-slide-up 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  }
}

@keyframes tf-fade-in {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes tf-scale-in {
  from { opacity: 0; transform: scale(0.95); }
  to { opacity: 1; transform: scale(1); }
}

@keyframes tf-slide-up {
  from { transform: translateY(100%); }
  to { transform: translateY(0); }
}
</style>
