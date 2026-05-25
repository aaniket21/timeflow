<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
  navigation: {
    type: Object,
    default: () => ({ sections: [] }),
  },
  routeMap: {
    type: Function,
    required: true
  },
  currentUrl: {
    type: String,
    default: ''
  },
  liveTimer: {
    type: Boolean,
    default: false
  }
});

const mobileNavItems = computed(() => {
  // Extract essential items for bottom nav (e.g., Dashboard, Timer, Habits, Analytics)
  const items = [];
  props.navigation.sections.forEach(section => {
    section.items.forEach(item => {
      if (['Dashboard', 'Timer', 'Habits', 'Analytics', 'Settings'].includes(item.label)) {
        items.push({
          label: item.label,
          icon: item.icon,
          href: props.routeMap(item.label),
          active: item.active
        });
      }
    });
  });
  return items;
});
</script>

<template>
  <nav class="tf-bottom-nav">
    <Link 
      v-for="item in mobileNavItems" 
      :key="item.label" 
      :href="item.href" 
      class="tf-bottom-item" 
      :class="{ active: item.active }"
    >
      <div class="tf-bottom-icon-wrapper">
        <i class="ti" :class="item.icon" aria-hidden="true"></i>
        <span v-if="liveTimer && item.label === 'Timer'" class="tf-badge-pulse" aria-hidden="true"></span>
      </div>
      <span class="tf-bottom-label">{{ item.label }}</span>
    </Link>
  </nav>
</template>

<style scoped>
.tf-bottom-nav {
  display: none;
  background: var(--tf-bg-sidebar);
  border-top: 1px solid var(--tf-border-default);
  padding: 8px 15px calc(8px + env(safe-area-inset-bottom));
  justify-content: space-around;
  align-items: center;
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 50;
}

.tf-bottom-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  color: var(--tf-text-hint);
  text-decoration: none;
  transition: color 0.2s ease;
  flex: 1;
}

.tf-bottom-item:hover, .tf-bottom-item.active {
  color: var(--tf-violet);
}

.tf-bottom-icon-wrapper {
  position: relative;
  font-size: 24px;
}

.tf-bottom-label {
  font-size: 11px;
  font-weight: 600;
}

.tf-badge-pulse {
  position: absolute;
  top: 0;
  right: -2px;
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--tf-rose);
  animation: tf-pulse 1.5s ease infinite;
}

@media (max-width: 768px) {
  .tf-bottom-nav {
    display: flex;
  }
}
</style>
