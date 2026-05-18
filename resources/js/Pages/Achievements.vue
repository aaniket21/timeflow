<script setup>
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';
import AppShell from '../Layouts/AppShell.vue';

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
          date: new Date(entry.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }),
        }));
      }
    } catch {
      // XP history is optional
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
  padding: 14px;
  font-family: 'Plus Jakarta Sans', 'Segoe UI', sans-serif;
  color: var(--tf-text-primary);
}

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.page-title {
  font-size: 17px;
  font-weight: 800;
}

.page-subtitle {
  font-size: 12px;
  color: var(--tf-text-secondary);
}

.stats-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 10px;
  margin-top: 12px;
}

.stat-label {
  font-size: 10px;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--tf-text-hint);
}

.stat-value {
  font-size: 16px;
  font-weight: 700;
  font-family: 'JetBrains Mono', 'Cascadia Code', monospace;
}

.level-card {
  margin-top: 12px;
}

.level-title {
  font-size: 14px;
  font-weight: 700;
}

.level-bar {
  height: 4px;
  background: rgba(124, 92, 252, 0.15);
  border-radius: 999px;
  margin-top: 8px;
  overflow: hidden;
}

.level-fill {
  display: block;
  height: 100%;
  width: 40%;
  background: var(--tf-violet);
}

.level-meta {
  font-size: 11px;
  color: var(--tf-text-secondary);
  margin-top: 6px;
}

.badge-group {
  margin-top: 12px;
}

.badge-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  gap: 10px;
  margin-top: 8px;
}

.badge-card {
  text-align: center;
  padding: 12px;
  border-radius: 10px;
  border: 1px solid var(--tf-border-default);
  background: var(--tf-bg-card);
}

.badge-card.locked {
  opacity: 0.5;
}

.badge-icon {
  font-size: 18px;
  font-weight: 700;
}

.badge-name {
  font-size: 11px;
  font-weight: 600;
  margin-top: 4px;
}

.badge-category {
  font-size: 9px;
  color: var(--tf-text-hint);
  text-transform: uppercase;
  letter-spacing: 0.08em;
  margin-top: 2px;
}

.xp-history {
  margin-top: 12px;
}

.xp-header {
  font-size: 12px;
  font-weight: 700;
  margin-bottom: 8px;
}

.xp-row {
  display: grid;
  grid-template-columns: 1fr auto auto;
  gap: 10px;
  font-size: 11px;
  color: var(--tf-text-secondary);
  padding: 6px 0;
}

.xp-amount {
  color: var(--tf-violet);
  font-weight: 700;
}

.xp-date {
  color: var(--tf-text-hint);
}
</style>
