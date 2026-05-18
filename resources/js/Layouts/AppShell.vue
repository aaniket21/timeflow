<script setup>
const props = defineProps({
  navigation: {
    type: Object,
    default: () => ({ sections: [] }),
  },
  xpTotal: {
    type: [Number, String],
    default: 1240,
  },
  notifications: {
    type: Number,
    default: 1,
  },
  userInitials: {
    type: String,
    default: 'AK',
  },
  streakCurrent: {
    type: Number,
    default: 14,
  },
});
</script>

<template>
  <div class="tf-shell">
    <header class="tf-topbar">
      <div class="tf-logo">
        <span class="tf-logo-orb"><i class="ti ti-clock" aria-hidden="true"></i></span>
        TimeFlow
      </div>
      <div class="tf-topbar-right">
        <div class="tf-xp-chip"><i class="ti ti-bolt" aria-hidden="true"></i> {{ props.xpTotal }} XP</div>
        <button class="tf-icon-button" type="button" aria-label="Notifications">
          <i class="ti ti-bell" aria-hidden="true"></i>
          <span v-if="props.notifications" class="tf-badge-dot" aria-hidden="true"></span>
        </button>
        <div class="tf-avatar">{{ props.userInitials }}</div>
      </div>
    </header>

    <aside class="tf-sidebar">
      <div v-for="section in props.navigation.sections" :key="section.label" class="tf-nav-group">
        <div class="tf-nav-section">{{ section.label }}</div>
        <button
          v-for="item in section.items"
          :key="item.label"
          class="tf-nav-item"
          :class="{ active: item.active }"
          type="button"
        >
          <i class="ti" :class="item.icon" aria-hidden="true"></i>
          <span>{{ item.label }}</span>
          <span v-if="item.count" class="tf-nav-count">{{ item.count }}</span>
        </button>
      </div>

      <div class="tf-streak-box">
        <div class="tf-streak-icon"><i class="ti ti-flame" aria-hidden="true"></i></div>
        <div class="tf-streak-value">{{ props.streakCurrent }}</div>
        <div class="tf-streak-label">day streak</div>
        <div class="tf-streak-dots">
          <span class="tf-streak-dot on"></span>
          <span class="tf-streak-dot on"></span>
          <span class="tf-streak-dot on"></span>
          <span class="tf-streak-dot on"></span>
          <span class="tf-streak-dot on"></span>
          <span class="tf-streak-dot on"></span>
          <span class="tf-streak-dot now"></span>
        </div>
      </div>
    </aside>

    <main class="tf-main">
      <slot />
    </main>
  </div>
</template>
