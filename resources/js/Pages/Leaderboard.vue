<script setup>
import axios from 'axios';
import { onMounted, ref } from 'vue';
import AppShell from '../Layouts/AppShell.vue';

const props = defineProps({
  navigation: {
    type: Object,
    default: () => ({ sections: [] }),
  },
});

const unlocked = ref(false);
const leaders = ref([]);
const xpTotal = ref(0);
const streakCurrent = ref(0);

const loadLeaderboard = async () => {
  try {
    const [profileResult, leadersResult] = await Promise.allSettled([
      axios.get('/api/gamification/profile'),
      axios.get('/api/gamification/leaderboard'),
    ]);

    if (profileResult.status === 'fulfilled') {
      const data = profileResult.value.data?.data;
      if (data) {
        xpTotal.value = Number(data.xp_total || 0);
        streakCurrent.value = Number(data.streak_current || 0);
        unlocked.value = Number(data.level || 1) >= 5;
      }
    }

    if (leadersResult.status === 'fulfilled') {
      const data = leadersResult.value.data?.data;
      if (Array.isArray(data)) {
        leaders.value = data.map((leader, index) => ({
          id: leader.user_id ?? index,
          rank: leader.rank ?? index + 1,
          name: leader.display_name ?? leader.name,
          xp: leader.xp ?? 0,
        }));
      }
    }
  } catch (error) {
    console.warn('Leaderboard fetch failed', error);
  }
};

onMounted(() => {
  loadLeaderboard();
});
</script>

<template>
  <div class="leaderboard-page">
    <AppShell
      :navigation="props.navigation"
      :xp-total="xpTotal"
      :streak-current="streakCurrent"
    >
      <div class="page-header">
        <div>
          <div class="page-title">Weekly Leaderboard</div>
          <div class="page-subtitle">Ranked by XP earned this week.</div>
        </div>
      </div>

      <div v-if="!unlocked" class="tf-card lock-card">
        <div class="lock-icon"><i class="ti ti-lock" aria-hidden="true"></i></div>
        <div class="lock-text">Unlock at Level 5 to join the leaderboard.</div>
      </div>

      <div v-else class="tf-card board-card">
        <div class="board-row" v-for="leader in leaders" :key="leader.id">
          <div class="rank">#{{ leader.rank }}</div>
          <div class="alias">{{ leader.name }}</div>
          <div class="xp">{{ leader.xp }} XP</div>
        </div>
        <div class="board-footer">Last updated 8 min ago</div>
      </div>
    </AppShell>
  </div>
</template>

<style>
.leaderboard-page {
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

.lock-card {
  margin-top: 12px;
  text-align: center;
  padding: 24px;
}

.lock-icon {
  font-size: 32px;
  color: var(--tf-text-hint);
}

.lock-text {
  margin-top: 8px;
  font-size: 12px;
  color: var(--tf-text-secondary);
}

.board-card {
  margin-top: 12px;
}

.board-row {
  display: grid;
  grid-template-columns: 60px 1fr auto;
  align-items: center;
  gap: 12px;
  padding: 10px 0;
  border-bottom: 1px solid var(--tf-border-default);
}

.board-row:last-child {
  border-bottom: none;
}

.rank {
  font-size: 18px;
  font-weight: 700;
  font-family: 'JetBrains Mono', 'Cascadia Code', monospace;
  color: var(--tf-text-hint);
}

.alias {
  font-size: 14px;
  font-weight: 600;
}

.xp {
  font-size: 12px;
  font-family: 'JetBrains Mono', 'Cascadia Code', monospace;
  color: var(--tf-violet);
}

.board-footer {
  font-size: 10px;
  color: var(--tf-text-hint);
  margin-top: 8px;
}
</style>
