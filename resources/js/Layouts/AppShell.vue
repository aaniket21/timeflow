<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { onMounted, ref, computed } from 'vue';
import { useTime } from '../composables/useTime';
import TopBar from '../Components/TopBar.vue';
import BottomNav from '../Components/BottomNav.vue';

const { dayOfWeekIndex, formatTime } = useTime();

const props = defineProps({
  navigation: {
    type: Object,
    default: () => ({ sections: [] }),
  },
});

const xpTotal = ref(0);
const streakCurrent = ref(0);
const streakWeekDays = ref([false, false, false, false, false, false, false]);
const userInitials = ref('TF');
const notifications = ref(0);
const notifOpen = ref(false);
const notifList = ref([]);
const todayDayIndex = ref(dayOfWeekIndex());

const routeMap = {
  Dashboard: '/dashboard',
  Timer: '/timer',
  Analytics: '/analytics',
  Projects: '/projects',
  Timetable: '/timetable',
  Habits: '/habits',
  Achievements: '/achievements',
  Goals: '/goals',
  Leaderboard: '/leaderboard',
  Reports: '/reports',
  Settings: '/settings',
};

const getRoute = (label) => routeMap[label] || '/';

const toggleDarkMode = () => {
  const html = document.documentElement;
  const isDark = html.classList.toggle('dark');
  localStorage.setItem('theme', isDark ? 'dark' : 'light');
};

const loadShellData = async () => {
  // Load user data from Inertia shared props (synced from DB on every page load)
  const page = usePage();
  const user = page.props?.auth?.user;
  if (user) {
    xpTotal.value = Number(user.xp_total || 0);
    streakCurrent.value = Number(user.streak_current || 0);

    if (user.name) {
      const parts = user.name.trim().split(/\s+/);
      userInitials.value = parts.length >= 2
        ? (parts[0][0] + parts[1][0]).toUpperCase()
        : user.name.slice(0, 2).toUpperCase();
    }
  }

  // Fetch week_days from gamification API (not in shared props)
  try {
    const res = await axios.get('/api/gamification/profile');
    const data = res.data?.data;
    if (data && Array.isArray(data.week_days)) {
      streakWeekDays.value = data.week_days;
    }
  } catch {
    // Gamification data is non-critical
  }
};

const toggleNotif = async () => {
  notifOpen.value = !notifOpen.value;
  if (notifOpen.value && notifList.value.length === 0) {
    try {
      const res = await axios.get('/api/notifications');
      if (Array.isArray(res.data?.data)) {
        notifList.value = res.data.data.map((n) => ({
          id: n.id,
          message: n.message || n.data?.message || 'New notification',
          time: n.created_at ? formatTime(n.created_at) : '',
          read: Boolean(n.read_at),
        }));
      }
    } catch {
      notifList.value = [{ id: 0, message: 'No notifications yet', time: '', read: true }];
    }
  }
};

const handleLogout = async () => {
  try {
    await axios.post('/logout');
    window.location.assign('/login');
  } catch (error) {
    console.warn('Logout failed', error);
    window.location.assign('/login');
  }
};

onMounted(() => {
  loadShellData();
});
</script>

<template>
  <div class="tf-shell">
    <TopBar 
      :xpTotal="xpTotal"
      :userInitials="userInitials"
      :notifications="notifications"
      :notifOpen="notifOpen"
      :notifList="notifList"
      @toggle-dark-mode="toggleDarkMode"
      @toggle-notif="toggleNotif"
    />

    <aside class="tf-sidebar">
      <div v-for="section in props.navigation.sections" :key="section.label" class="tf-nav-group">
        <div class="tf-nav-section">{{ section.label }}</div>
        <Link
          v-for="item in section.items"
          :key="item.label"
          :href="getRoute(item.label)"
          class="tf-nav-item"
          :class="{ active: item.active }"
        >
          <i class="ti" :class="item.icon" aria-hidden="true"></i>
          <span>{{ item.label }}</span>
          <span v-if="item.count" class="tf-nav-count">{{ item.count }}</span>
        </Link>
      </div>

      <div class="tf-streak-box">
        <div class="tf-streak-icon">🔥</div>
        <div class="tf-streak-value">{{ streakCurrent }}</div>
        <div class="tf-streak-label">day streak</div>
        <div class="tf-streak-dots">
          <span
            v-for="(done, index) in streakWeekDays"
            :key="index"
            class="tf-streak-dot"
            :class="{ on: done, now: index === todayDayIndex && !done }"
          ></span>
        </div>
      </div>

      <button class="tf-logout-btn" type="button" @click="handleLogout">
        <i class="ti ti-logout" aria-hidden="true"></i>
        <span>Sign out</span>
      </button>
    </aside>

    <main class="tf-main">
      <slot />
    </main>

    <BottomNav 
      :navigation="props.navigation"
      :routeMap="getRoute"
      :liveTimer="true" 
    />
  </div>
</template>
