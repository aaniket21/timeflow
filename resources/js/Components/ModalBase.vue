<script setup>
defineProps({
  open: { type: Boolean, default: false },
  width: { type: String, default: '550px' },
  title: { type: String, default: '' },
});

const emit = defineEmits(['close']);

const close = () => emit('close');
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="open" class="modal-overlay" @click.self="close">
        <div class="modal-panel" :style="{ maxWidth: width }" role="dialog" aria-modal="true">
          <div class="modal-header">
            <div class="modal-title">{{ title }}</div>
            <button class="modal-close" type="button" @click="close" aria-label="Close">
              <i class="ti ti-x" aria-hidden="true"></i>
            </button>
          </div>
          <div class="modal-body">
            <slot />
          </div>
          <div v-if="$slots.footer" class="modal-footer">
            <slot name="footer" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style>
.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 9000;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.4);
  backdrop-filter: blur(5px);
  padding: 25px;
}

.modal-panel {
  width: 100%;
  background: var(--tf-bg-card);
  border: 1px solid var(--tf-border-default);
  border-radius: 18px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.18);
  overflow: hidden;
  font-family: 'Plus Jakarta Sans', sans-serif;
  color: var(--tf-text-primary);
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 23px 15px;
  border-bottom: 1px solid var(--tf-border-default);
}

.modal-title {
  font-size: 19px;
  font-weight: 700;
}

.modal-close {
  width: 35px;
  height: 35px;
  border: none;
  background: transparent;
  color: var(--tf-text-hint);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
}

.modal-close:hover {
  background: var(--tf-bg-hover);
  color: var(--tf-text-primary);
}

.modal-body {
  padding: 20px 23px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 15px 23px 20px;
  border-top: 1px solid var(--tf-border-default);
}

/* Transition */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
}

.modal-enter-active .modal-panel,
.modal-leave-active .modal-panel {
  transition: transform 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-from .modal-panel {
  transform: scale(0.95) translateY(10px);
}

.modal-leave-to .modal-panel {
  transform: scale(0.95) translateY(10px);
}
</style>
