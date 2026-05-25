<script setup>
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';
import AppShell from '../Layouts/AppShell.vue';
import { useTime } from '../composables/useTime';

const { formatDate } = useTime();

const props = defineProps({
  navigation: {
    type: Object,
    default: () => ({ sections: [] }),
  },
});

const profile = ref({
  xp_total: 0,
  level: 1,
  next_level_xp: 0,
  level_progress: 0,
  streak_current: 0,
  streak_longest: 0,
  badge_count: 0,
  last_active_date: null,
});

const badges = ref([]);
const xpHistory = ref([]);
const calendarData = ref([]);

const levelTitles = {
  1: 'Starter',
  2: 'Focused',
  3: 'Consistent',
  4: 'Dedicated',
  5: 'Relentless',
  6: 'Flow State',
  7: 'Deep Worker',
  8: 'TimeFlow Master',
};

const badgeCount = computed(() => {
  if (Number(profile.value.badge_count) > 0) return Number(profile.value.badge_count);
  return badges.value.filter((badge) => badge.earned).length;
});

const xpTotal = computed(() => Number(profile.value.xp_total || 0));
const levelTitle = computed(() => levelTitles[profile.value.level] || 'Focused');
const levelProgress = computed(() => {
  const progress = Number(profile.value.level_progress);
  if (!Number.isNaN(progress) && progress > 0) {
    return Math.min(1, Math.max(0, progress));
  }
  const nextLevel = Number(profile.value.next_level_xp || 0);
  if (nextLevel <= 0) return 0;
  return Math.min(1, Math.max(0, xpTotal.value / nextLevel));
});
const nextLevelLabel = computed(() => {
  if (!profile.value.next_level_xp) return 'Max level reached';
  return `Next: Level ${Number(profile.value.level || 1) + 1} at ${profile.value.next_level_xp} XP`;
});

const formatCategory = (category) => {
  if (!category) return 'General';
  return String(category).replace(/_/g, ' ');
};

const loadGamification = async () => {
  try {
    const [profileResult, badgesResult] = await Promise.allSettled([
      axios.get('/api/gamification/profile'),
      axios.get('/api/gamification/badges'),
    ]);

    if (profileResult.status === 'fulfilled') {
      const data = profileResult.value.data?.data;
      if (data) {
        profile.value = { ...profile.value, ...data };
      }
    }

    if (badgesResult.status === 'fulfilled') {
      const data = badgesResult.value.data?.data;
      if (Array.isArray(data)) {
        badges.value = data;
      }
    }

    // Load XP history
    try {
      const xpRes = await axios.get('/api/gamification/xp-history');
      if (Array.isArray(xpRes.data?.data)) {
        xpHistory.value = xpRes.data.data.map((entry) => ({
          id: entry.id,
          reason: entry.reason || 'Session logged',
          xp: entry.xp || 0,
          date: formatDate(entry.created_at, 'MMM D'),
        }));
      }
    } catch {
      // XP history is optional
    }

    try {
      const calRes = await axios.get('/api/gamification/calendar');
      if (Array.isArray(calRes.data?.data)) {
        calendarData.value = calRes.data.data;
      }
    } catch {
      // Calendar is optional
    }
  } catch (error) {
    console.warn('Gamification fetch failed', error);
  }
};

onMounted(() => {
  loadGamification();
});
</script>

<template>
  <div class="achievements-page">
    <AppShell
      :navigation="props.navigation"
      :xp-total="xpTotal"
      :streak-current="profile.streak_current"
    >
      <div class="page-header">
        <div>
          <div class="page-title">Achievements</div>
          <div class="page-subtitle">Badges earned through streaks and focus.</div>
        </div>
      </div>

      <div class="stats-row">
        <div class="tf-card stat-card">
          <div class="stat-label">Badges earned</div>
          <div class="stat-value">{{ badgeCount }}</div>
        </div>
        <div class="tf-card stat-card">
          <div class="stat-label">XP total</div>
          <div class="stat-value">{{ xpTotal }}</div>
        </div>
        <div class="tf-card stat-card">
          <div class="stat-label">Level</div>
          <div class="stat-value">{{ profile.level }}</div>
        </div>
      </div>

      <div class="tf-card level-card">
        <div class="level-title">Level {{ profile.level }} - {{ levelTitle }}</div>
        <div class="level-bar"><span class="level-fill" :style="{ width: (levelProgress * 100).toFixed(0) + '%' }"></span></div>
        <div class="level-meta">{{ nextLevelLabel }}</div>
      </div>

      <div class="tf-card perks-card" v-if="profile.perks">
        <div class="xp-header">Level Perks</div>
        <div class="perks-grid">
          <div class="perk-item" :class="{ locked: !profile.perks.custom_colors }">
            <i :class="profile.perks.custom_colors ? 'ti ti-check' : 'ti ti-lock'"></i> Custom Colors (Lvl 2)
          </div>
          <div class="perk-item" :class="{ locked: !profile.perks.streak_shield }">
            <i :class="profile.perks.streak_shield ? 'ti ti-check' : 'ti ti-lock'"></i> Streak Shield (Lvl 3)
          </div>
          <div class="perk-item" :class="{ locked: !profile.perks.weekly_digest }">
            <i :class="profile.perks.weekly_digest ? 'ti ti-check' : 'ti ti-lock'"></i> Weekly Digest (Lvl 4)
          </div>
          <div class="perk-item" :class="{ locked: !profile.perks.advanced_analytics }">
            <i :class="profile.perks.advanced_analytics ? 'ti ti-check' : 'ti ti-lock'"></i> Advanced Analytics (Lvl 5)
          </div>
          <div class="perk-item" :class="{ locked: !profile.perks.data_export }">
            <i :class="profile.perks.data_export ? 'ti ti-check' : 'ti ti-lock'"></i> Data Export (Lvl 6)
          </div>
          <div class="perk-item" :class="{ locked: !profile.perks.public_profile }">
            <i :class="profile.perks.public_profile ? 'ti ti-check' : 'ti ti-lock'"></i> Public Profile (Lvl 7)
          </div>
          <div class="perk-item" :class="{ locked: !profile.perks.legend_badge }">
            <i :class="profile.perks.legend_badge ? 'ti ti-check' : 'ti ti-lock'"></i> Legend Badge (Lvl 8)
          </div>
        </div>
      </div>

      <div class="badge-group">
        <div class="tf-section-label">Badge Gallery</div>
        <div class="badge-grid">
          <div v-for="badge in badges" :key="badge.id" class="badge-card" :class="{ locked: !badge.earned }">
            <div class="badge-icon">{{ badge.icon }}</div>
            <div class="badge-name">{{ badge.name }}</div>
            <div class="badge-category">{{ formatCategory(badge.category) }}</div>
          </div>
        </div>
      </div>

      <div class="tf-card streak-calendar" v-if="calendarData.length">
        <div class="xp-header">Streak Calendar</div>
        <div class="calendar-wrapper">
          <div class="calendar-grid">
            <div v-for="(day, index) in calendarData" :key="index" class="cal-day" :class="'cal-lvl-' + day.level" :title="day.date + ' - ' + Math.floor(day.total_seconds/60) + ' min'"></div>
          </div>
        </div>
      </div>

      <div class="tf-card xp-history">
        <div class="xp-header">XP History</div>
        <div v-if="xpHistory.length">
          <div class="xp-row" v-for="entry in xpHistory" :key="entry.id">
            <span>{{ entry.reason }}</span>
            <span class="xp-amount">+{{ entry.xp }} XP</span>
            <span class="xp-date">{{ entry.date }}</span>
          </div>
        </div>
        <div v-else class="xp-row"><span>No XP events yet. Start tracking to earn XP.</span></div>
      </div>
    </AppShell>
  </div>
</template>

<style scoped>
.achievements-page {
  min-height: 100vh;
  background: var(--tf-bg-page);

  font-family: 'Plus Jakarta Sans', 'Segoe UI', sans-serif;
  color: var(--tf-text-primary);
}

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 15px;
}

.page-title {
  font-size: 21px;
  font-weight: 800;
}

.page-subtitle {
  font-size: 15px;
  color: var(--tf-text-secondary);
}

.stats-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 13px;
  margin-top: 15px;
}

.stat-label {
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--tf-text-hint);
}

.stat-value {
  font-size: 20px;
  font-weight: 700;
  font-family: 'JetBrains Mono', 'Cascadia Code', monospace;
}

.level-card {
  margin-top: 15px;
}

.level-title {
  font-size: 18px;
  font-weight: 700;
}

.level-bar {
  height: 5px;
  background: rgba(124, 92, 252, 0.15);
  border-radius: 1249px;
  margin-top: 10px;
  overflow: hidden;
}

.level-fill {
  display: block;
  height: 100%;
  width: 40%;
  background: var(--tf-violet);
}

.level-meta {
  font-size: 14px;
  color: var(--tf-text-secondary);
  margin-top: 8px;
}

.badge-group {
  margin-top: 15px;
}

.badge-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 13px;
  margin-top: 10px;
}

.badge-card {
  text-align: center;
  padding: 15px;
  border-radius: 13px;
  border: 1px solid var(--tf-border-default);
  background: var(--tf-bg-card);
}

.badge-card.locked {
  opacity: 0.5;
}

.badge-icon {
  font-size: 23px;
  font-weight: 700;
}

.badge-name {
  font-size: 14px;
  font-weight: 600;
  margin-top: 5px;
}

.badge-category {
  font-size: 11px;
  color: var(--tf-text-hint);
  text-transform: uppercase;
  letter-spacing: 0.08em;
  margin-top: 3px;
}

.xp-history {
  margin-top: 15px;
}

.xp-header {
  font-size: 15px;
  font-weight: 700;
  margin-bottom: 10px;
}

.xp-row {
  display: grid;
  grid-template-columns: 1fr auto auto;
  gap: 13px;
  font-size: 14px;
  color: var(--tf-text-secondary);
  padding: 8px 0;
}

.xp-amount {
  color: var(--tf-violet);
  font-weight: 700;
}

.xp-date {
  color: var(--tf-text-hint);
}

.streak-calendar {
  margin-top: 15px;
}

.calendar-wrapper {
  overflow-x: auto;
  padding-bottom: 10px;
}

.calendar-grid {
  display: flex;
  flex-direction: column;
  flex-wrap: wrap;
  height: 110px; /* 7 rows of 12px + gaps */
  gap: 3px;
  align-content: flex-start;
}

.cal-day {
  width: 12px;
  height: 12px;
  border-radius: 2px;
  background: var(--tf-bg-card-alt);
}

.cal-day.cal-lvl-0 { background: var(--tf-border-default); opacity: 0.5; }
.cal-day.cal-lvl-1 { background: rgba(124, 92, 252, 0.3); }
.cal-day.cal-lvl-2 { background: rgba(124, 92, 252, 0.5); }
.cal-day.cal-lvl-3 { background: rgba(124, 92, 252, 0.8); }
.cal-day.cal-lvl-4 { background: var(--tf-violet); }
</style>
