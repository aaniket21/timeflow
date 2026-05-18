<script setup>
const props = defineProps({
  leaders: {
    type: Array,
    default: () => [
      { rank: 1, name: 'Alpha', xp: 420 },
      { rank: 2, name: 'Bravo', xp: 360 },
      { rank: 3, name: 'Nova', xp: 330 },
      { rank: 4, name: 'Pulse', xp: 280 },
      { rank: 5, name: 'Quill', xp: 240 },
    ],
  },
});
</script>

<template>
  <div class="leader-shell">
    <header class="leader-head">
      <div>
        <div class="overline">Weekly Arena</div>
        <h1>Leaderboard</h1>
        <p>Opt-in ranking based on XP earned this week.</p>
      </div>
      <div class="leader-tag">Season 18</div>
    </header>

    <section class="leader-table">
      <div class="leader-row head">
        <div>Rank</div>
        <div>Alias</div>
        <div class="xp">XP</div>
      </div>
      <div v-for="leader in props.leaders" :key="leader.rank" class="leader-row">
        <div class="rank">#{{ leader.rank }}</div>
        <div class="name">{{ leader.name }}</div>
        <div class="xp">{{ leader.xp }} XP</div>
      </div>
    </section>

    <section class="callout">
      <div class="callout-title">Your streak fuels your ranking</div>
      <div class="callout-desc">Stay consistent to climb the weekly board without burnout.</div>
    </section>
  </div>
</template>

<style>
.leader-shell {
  min-height: 100vh;
  padding: 32px 26px 60px;
  background: radial-gradient(circle at 20% 10%, rgba(56, 189, 248, 0.12), transparent 50%),
    radial-gradient(circle at 80% 0%, rgba(59, 130, 246, 0.12), transparent 55%),
    #0f172a;
  font-family: 'Plus Jakarta Sans', sans-serif;
  color: #e2e8f0;
}

.leader-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 16px;
  margin-bottom: 24px;
}

.overline {
  text-transform: uppercase;
  letter-spacing: 0.18em;
  font-size: 11px;
  color: rgba(226, 232, 240, 0.6);
  margin-bottom: 8px;
}

.leader-head h1 {
  font-size: 30px;
  margin: 0 0 6px;
}

.leader-head p {
  margin: 0;
  color: rgba(226, 232, 240, 0.7);
}

.leader-tag {
  padding: 8px 16px;
  border-radius: 999px;
  background: rgba(15, 23, 42, 0.8);
  border: 1px solid rgba(148, 163, 184, 0.3);
  font-size: 12px;
  letter-spacing: 0.18em;
  text-transform: uppercase;
}

.leader-table {
  background: rgba(15, 23, 42, 0.7);
  border-radius: 16px;
  border: 1px solid rgba(148, 163, 184, 0.2);
  overflow: hidden;
}

.leader-row {
  display: grid;
  grid-template-columns: 0.6fr 2fr 1fr;
  gap: 16px;
  padding: 14px 18px;
  border-bottom: 1px solid rgba(148, 163, 184, 0.12);
}

.leader-row.head {
  text-transform: uppercase;
  font-size: 11px;
  letter-spacing: 0.18em;
  background: rgba(15, 23, 42, 0.85);
  color: rgba(226, 232, 240, 0.6);
}

.leader-row:last-child {
  border-bottom: none;
}

.rank {
  font-weight: 700;
  color: #38bdf8;
}

.xp {
  text-align: right;
  font-family: 'JetBrains Mono', monospace;
}

.callout {
  margin-top: 24px;
  padding: 18px 20px;
  border-radius: 16px;
  background: linear-gradient(90deg, rgba(14, 116, 144, 0.4), rgba(15, 23, 42, 0.6));
  border: 1px solid rgba(56, 189, 248, 0.3);
}

.callout-title {
  font-size: 16px;
  font-weight: 700;
}

.callout-desc {
  margin-top: 6px;
  font-size: 13px;
  color: rgba(226, 232, 240, 0.7);
}
</style>
