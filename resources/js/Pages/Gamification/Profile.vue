<script setup>
const props = defineProps({
  profile: {
    type: Object,
    default: () => ({
      xp_total: 1240,
      level: 4,
      level_title: 'Dedicated',
      next_level_xp: 1600,
      streak_current: 14,
      streak_longest: 21,
      badge_count: 12,
      last_active_date: '2026-05-18',
    }),
  },
  celebration: {
    type: Object,
    default: () => ({
      title: 'Level up!',
      detail: 'You are 360 XP away from Focused 5.',
      action: 'Keep the streak alive',
    }),
  },
});
</script>

<template>
  <div class="profile-shell">
    <header class="hero">
      <div>
        <div class="overline">Gamification Profile</div>
        <h1>Growth Snapshot</h1>
        <p>Track your momentum, streak health, and unlocked rewards.</p>
      </div>
      <div class="level-card">
        <div class="level-title">{{ props.profile.level_title }}</div>
        <div class="level-xp">Lv. {{ props.profile.level }}</div>
        <div class="level-bar">
          <div class="level-fill" :style="{ width: ((props.profile.xp_total / props.profile.next_level_xp) * 100).toFixed(0) + '%' }"></div>
        </div>
        <div class="level-meta">
          <span>{{ props.profile.xp_total }} XP</span>
          <span>{{ props.profile.next_level_xp }} next</span>
        </div>
      </div>
    </header>

    <section class="stats-grid">
      <div class="stat-card">
        <div class="stat-label">Current streak</div>
        <div class="stat-value">{{ props.profile.streak_current }} days</div>
        <div class="stat-sub">Longest: {{ props.profile.streak_longest }} days</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Badges unlocked</div>
        <div class="stat-value">{{ props.profile.badge_count }}</div>
        <div class="stat-sub">Keep collecting milestones</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Last active</div>
        <div class="stat-value">{{ props.profile.last_active_date }}</div>
        <div class="stat-sub">Stay in the loop</div>
      </div>
    </section>

    <section class="celebration">
      <div class="burst"></div>
      <div>
        <div class="celebration-title">{{ props.celebration.title }}</div>
        <div class="celebration-detail">{{ props.celebration.detail }}</div>
        <div class="celebration-action">{{ props.celebration.action }}</div>
      </div>
    </section>
  </div>
</template>

<style>
.profile-shell {
  min-height: 100vh;
  padding: 32px 28px 60px;
  background: radial-gradient(circle at top left, rgba(15, 118, 110, 0.12), transparent 55%),
    radial-gradient(circle at 40% 10%, rgba(234, 179, 8, 0.12), transparent 50%),
    #f9f8f4;
  font-family: 'Plus Jakarta Sans', sans-serif;
  color: #1f2937;
}

.hero {
  display: flex;
  gap: 24px;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 28px;
}

.overline {
  text-transform: uppercase;
  letter-spacing: 0.2em;
  font-size: 11px;
  color: #64748b;
  margin-bottom: 8px;
}

.hero h1 {
  font-size: 32px;
  margin: 0 0 8px;
}

.hero p {
  margin: 0;
  color: #475569;
  max-width: 360px;
}

.level-card {
  background: #0f172a;
  color: #e2e8f0;
  padding: 18px 20px;
  border-radius: 16px;
  min-width: 240px;
  box-shadow: 0 18px 40px rgba(15, 23, 42, 0.2);
}

.level-title {
  font-size: 14px;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  color: #94a3b8;
}

.level-xp {
  font-size: 28px;
  font-weight: 700;
  margin: 8px 0 12px;
}

.level-bar {
  height: 6px;
  border-radius: 999px;
  background: rgba(148, 163, 184, 0.2);
  overflow: hidden;
}

.level-fill {
  height: 100%;
  background: linear-gradient(90deg, #14b8a6, #eab308);
}

.level-meta {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
  margin-top: 10px;
  color: #cbd5f5;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 16px;
  margin-bottom: 28px;
}

.stat-card {
  padding: 16px 18px;
  background: #ffffff;
  border-radius: 14px;
  border: 1px solid rgba(15, 23, 42, 0.08);
  box-shadow: 0 12px 28px rgba(15, 23, 42, 0.04);
}

.stat-label {
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.16em;
  color: #64748b;
}

.stat-value {
  font-size: 22px;
  font-weight: 700;
  margin: 10px 0 4px;
}

.stat-sub {
  font-size: 13px;
  color: #475569;
}

.celebration {
  position: relative;
  overflow: hidden;
  padding: 18px 20px;
  border-radius: 16px;
  background: #0f766e;
  color: #ecfeff;
  display: flex;
  align-items: center;
  gap: 18px;
  box-shadow: 0 16px 30px rgba(15, 118, 110, 0.25);
}

.burst {
  width: 42px;
  height: 42px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(255, 255, 255, 0.9), transparent 70%);
  box-shadow: 0 0 0 6px rgba(234, 179, 8, 0.25), 0 0 0 14px rgba(234, 179, 8, 0.18);
  animation: pulse 2.8s ease-in-out infinite;
}

.celebration-title {
  font-size: 18px;
  font-weight: 700;
}

.celebration-detail {
  font-size: 14px;
  margin-top: 4px;
}

.celebration-action {
  font-size: 12px;
  margin-top: 8px;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.7);
}

@keyframes pulse {
  0%,
  100% {
    transform: scale(1);
    opacity: 0.85;
  }
  50% {
    transform: scale(1.08);
    opacity: 1;
  }
}
</style>
