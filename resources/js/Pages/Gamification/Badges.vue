<script setup>
const props = defineProps({
  badges: {
    type: Array,
    default: () => [
      { id: 1, name: 'First Flame', icon: 'FL', description: '3-day streak', earned: true, category: 'consistency' },
      { id: 2, name: 'Week Warrior', icon: 'WW', description: '7-day streak', earned: true, category: 'consistency' },
      { id: 3, name: 'Mountain Climber', icon: 'MC', description: '30-day streak', earned: false, category: 'consistency' },
      { id: 4, name: 'Tomato Head', icon: 'TH', description: 'First Pomodoro', earned: true, category: 'focus' },
      { id: 5, name: 'Deep Diver', icon: 'DD', description: '4-hour focus session', earned: false, category: 'focus' },
      { id: 6, name: 'First Hour', icon: 'FH', description: 'Log your first hour', earned: true, category: 'volume' },
      { id: 7, name: '10 Hour Club', icon: '10', description: 'Log 10h in a week', earned: false, category: 'volume' },
      { id: 8, name: 'Time Lord', icon: 'TL', description: 'Log 1000h', earned: false, category: 'volume' },
    ],
  },
});
</script>

<template>
  <div class="badge-shell">
    <header class="badge-head">
      <div>
        <div class="overline">Achievements</div>
        <h1>Badge Gallery</h1>
        <p>Every streak and milestone earns a place here.</p>
      </div>
      <div class="badge-counter">{{ props.badges.filter((badge) => badge.earned).length }} unlocked</div>
    </header>

    <section class="badge-grid">
      <div
        v-for="badge in props.badges"
        :key="badge.id"
        class="badge-card"
        :class="{ locked: !badge.earned }"
      >
        <div class="badge-icon">{{ badge.icon }}</div>
        <div class="badge-name">{{ badge.name }}</div>
        <div class="badge-desc">{{ badge.description }}</div>
        <div class="badge-chip">{{ badge.category }}</div>
      </div>
    </section>
  </div>
</template>

<style>
.badge-shell {
  min-height: 100vh;
  padding: 32px 26px 60px;
  background: linear-gradient(140deg, rgba(14, 116, 144, 0.08), transparent 45%),
    linear-gradient(45deg, rgba(251, 191, 36, 0.08), transparent 60%),
    #f8fafc;
  font-family: 'Plus Jakarta Sans', sans-serif;
  color: #0f172a;
}

.badge-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 18px;
  margin-bottom: 24px;
}

.overline {
  text-transform: uppercase;
  letter-spacing: 0.18em;
  font-size: 11px;
  color: #64748b;
  margin-bottom: 8px;
}

.badge-head h1 {
  font-size: 30px;
  margin: 0 0 6px;
}

.badge-head p {
  margin: 0;
  color: #475569;
}

.badge-counter {
  padding: 10px 18px;
  border-radius: 999px;
  background: #0f172a;
  color: #e2e8f0;
  font-size: 12px;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.badge-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
  gap: 16px;
}

.badge-card {
  background: #ffffff;
  border-radius: 16px;
  padding: 16px 14px;
  border: 1px solid rgba(15, 23, 42, 0.08);
  box-shadow: 0 14px 30px rgba(15, 23, 42, 0.06);
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.badge-card.locked {
  background: rgba(148, 163, 184, 0.14);
  border-style: dashed;
  color: rgba(15, 23, 42, 0.5);
}

.badge-icon {
  font-size: 26px;
}

.badge-name {
  font-weight: 700;
  font-size: 16px;
}

.badge-desc {
  font-size: 13px;
  color: inherit;
}

.badge-chip {
  align-self: flex-start;
  font-size: 11px;
  padding: 4px 10px;
  border-radius: 999px;
  background: rgba(14, 116, 144, 0.12);
  color: #0e7490;
  text-transform: uppercase;
  letter-spacing: 0.12em;
}

.badge-card.locked .badge-chip {
  background: rgba(100, 116, 139, 0.18);
  color: rgba(15, 23, 42, 0.5);
}
</style>
