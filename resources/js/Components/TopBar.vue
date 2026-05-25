<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  xpTotal: { type: Number, default: 0 },
  userInitials: { type: String, default: 'TF' },
  notifications: { type: Number, default: 0 },
  notifOpen: { type: Boolean, default: false },
  notifList: { type: Array, default: () => [] },
});

const emit = defineEmits(['toggle-dark-mode', 'toggle-notif']);
</script>

<template>
  <header class="tf-topbar">
    <Link href="/dashboard" class="tf-logo">
      <span class="tf-logo-orb"><i class="ti ti-clock" aria-hidden="true"></i></span>
      <span class="tf-logo-text">TimeFlow</span>
    </Link>
    <div class="tf-topbar-right">
      <div class="tf-xp-chip"><i class="ti ti-bolt" aria-hidden="true"></i> <span>{{ xpTotal }} XP</span></div>
      <button class="tf-icon-button" type="button" aria-label="Toggle dark mode" @click="emit('toggle-dark-mode')">
        <i class="ti ti-moon" aria-hidden="true"></i>
      </button>
      <div class="tf-notif-wrapper">
        <button class="tf-icon-button" type="button" aria-label="Notifications" @click="emit('toggle-notif')">
          <i class="ti ti-bell" aria-hidden="true"></i>
          <span v-if="notifications" class="tf-badge-dot" aria-hidden="true"></span>
        </button>
        <div v-if="notifOpen" class="tf-notif-panel">
          <div class="tf-notif-header">Notifications</div>
          <div v-if="notifList.length === 0" class="tf-notif-empty">No notifications</div>
          <div v-for="notif in notifList" :key="notif.id" class="tf-notif-item" :class="{ unread: !notif.read }">
            <div class="tf-notif-msg">{{ notif.message }}</div>
            <div v-if="notif.time" class="tf-notif-time">{{ notif.time }}</div>
          </div>
        </div>
      </div>
      <Link href="/settings" class="tf-avatar">{{ userInitials }}</Link>
    </div>
  </header>
</template>

<style scoped>
/* These styles can remain in app.css or be scoped here. 
   Since they are currently in app.css we can rely on them, but we might want to hide logo text on very small screens. */
@media (max-width: 400px) {
  .tf-logo-text {
    display: none;
  }
  .tf-xp-chip span {
    display: none;
  }
  .tf-xp-chip {
    padding: 5px 8px;
  }
}
</style>
