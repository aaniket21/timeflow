<script setup>
import { onMounted, ref } from 'vue';

const toasts = ref([]);
let nextId = 0;

const addToast = (message, type = 'info', duration = 4000) => {
  const id = nextId++;
  toasts.value.push({ id, message, type, leaving: false });

  if (duration > 0) {
    setTimeout(() => removeToast(id), duration);
  }
};

const removeToast = (id) => {
  const toast = toasts.value.find((t) => t.id === id);
  if (!toast) return;
  toast.leaving = true;
  setTimeout(() => {
    toasts.value = toasts.value.filter((t) => t.id !== id);
  }, 300);
};

// Expose globally so any component can call window.TimeflowToast.show()
onMounted(() => {
  window.TimeflowToast = {
    show: addToast,
    success: (msg) => addToast(msg, 'success'),
    error: (msg) => addToast(msg, 'error'),
    warning: (msg) => addToast(msg, 'warning'),
    info: (msg) => addToast(msg, 'info'),
  };
});

const typeColors = {
  success: 'var(--tf-mint)',
  error: 'var(--tf-red)',
  warning: 'var(--tf-amber)',
  info: 'var(--tf-violet)',
};
</script>

<template>
  <Teleport to="body">
    <div class="toast-container" aria-live="polite">
      <div
        v-for="toast in toasts"
        :key="toast.id"
        class="toast"
        :class="{ leaving: toast.leaving }"
        role="alert"
      >
        <span class="toast-strip" :style="{ background: typeColors[toast.type] || typeColors.info }"></span>
        <span class="toast-message">{{ toast.message }}</span>
        <button class="toast-close" type="button" @click="removeToast(toast.id)" aria-label="Dismiss">
          <i class="ti ti-x" aria-hidden="true"></i>
        </button>
      </div>
    </div>
  </Teleport>
</template>

<style>
.toast-container {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  gap: 10px;
  width: 375px;
  pointer-events: none;
}

.toast {
  display: flex;
  align-items: center;
  gap: 13px;
  padding: 13px 15px;
  border-radius: 13px;
  background: var(--tf-bg-card);
  border: 1px solid var(--tf-border-default);
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
  pointer-events: all;
  animation: toast-in 0.3s ease forwards;
}

.toast.leaving {
  animation: toast-out 0.3s ease forwards;
}

.toast-strip {
  width: 4px;
  height: 30px;
  border-radius: 3px;
  flex-shrink: 0;
}

.toast-message {
  flex: 1;
  font-size: 15px;
  font-weight: 500;
  color: var(--tf-text-primary);
  font-family: 'Plus Jakarta Sans', sans-serif;
}

.toast-close {
  width: 25px;
  height: 25px;
  border: none;
  background: transparent;
  color: var(--tf-text-hint);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 5px;
  flex-shrink: 0;
}

.toast-close:hover {
  color: var(--tf-text-primary);
  background: var(--tf-bg-hover);
}

@keyframes toast-in {
  from {
    opacity: 0;
    transform: translateX(25px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes toast-out {
  from {
    opacity: 1;
    transform: translateX(0);
  }
  to {
    opacity: 0;
    transform: translateX(25px);
  }
}
</style>
