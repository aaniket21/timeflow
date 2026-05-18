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

const gridStartHour = 6;
const slotHeight = 36;

const hours = Array.from({ length: 18 }, (_, index) => gridStartHour + index);
const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

const blocks = ref([]);

const displayBlocks = computed(() => {
  const items = [];

  blocks.value.forEach((block) => {
    const startHour = parseTime(block.start_time);
    const endHour = parseTime(block.end_time);
    const duration = endHour - startHour;

    if (duration <= 0) return;

    block.days_of_week.forEach((day) => {
      const dayIndex = day - 1;
      if (dayIndex < 0 || dayIndex > 6) return;

      items.push({
        id: `${block.id}-${dayIndex}`,
        dayIndex,
        start: startHour,
        duration,
        title: block.title,
        type: block.type,
        color: block.color,
      });
    });
  });

  return items;
});

const weekLabel = computed(() => {
  const now = new Date();
  const start = startOfWeek(now);
  const end = addDays(start, 6);
  return `${formatDateLabel(start)} - ${formatDateLabel(end)}`;
});

const loadBlocks = async () => {
  try {
    const response = await axios.get('/api/timetable/blocks');
    if (Array.isArray(response.data?.data)) {
      blocks.value = response.data.data;
    }
  } catch (error) {
    console.warn('Timetable blocks fetch failed', error);
  }
};

onMounted(() => {
  loadBlocks();
});

function parseTime(value) {
  const [hoursValue, minutesValue] = value.split(':').map((part) => Number(part));
  return hoursValue + minutesValue / 60;
}

function startOfWeek(date) {
  const base = new Date(date);
  const day = base.getDay();
  const diff = day === 0 ? -6 : 1 - day;
  base.setDate(base.getDate() + diff);
  base.setHours(0, 0, 0, 0);
  return base;
}

function addDays(date, days) {
  const next = new Date(date);
  next.setDate(next.getDate() + days);
  return next;
}

function formatDateLabel(date) {
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}
</script>

<template>
  <div class="timetable-page">
    <AppShell :navigation="props.navigation">
      <div class="page-header">
        <div>
          <div class="page-title">Timetable</div>
          <div class="page-subtitle">Plan the week with smart blocks.</div>
        </div>
        <button class="outline-btn" type="button">+ Add block</button>
      </div>

      <div class="week-nav">
        <button class="nav-btn" type="button">&lt; Week</button>
        <div class="tf-date-badge">{{ weekLabel }}</div>
        <button class="nav-btn" type="button">Week &gt;</button>
      </div>

      <div class="tf-card timetable-grid">
        <div class="grid-header">
          <div></div>
          <div v-for="day in days" :key="day" class="day-header">{{ day }}</div>
        </div>
        <div class="grid-body">
          <div v-for="hour in hours" :key="hour" class="time-row">
            <div class="time-label">{{ hour }}:00</div>
            <div v-for="day in days" :key="day" class="time-cell"></div>
          </div>
          <div
            v-for="block in displayBlocks"
            :key="block.id"
            class="block"
            :style="{
              background: block.color,
              top: (block.start - gridStartHour) * slotHeight + 'px',
              left: `calc(44px + (${block.dayIndex}) * ((100% - 44px) / 7))`,
              height: block.duration * slotHeight + 'px'
            }"
          >
            <div class="block-title">{{ block.title }}</div>
            <div class="block-time">{{ block.type }}</div>
          </div>
        </div>
      </div>

      <div class="tf-card insight-card">
        <div class="tf-section-label">Smart insights</div>
        <div class="insight">Your most consistent study block is Thursday at 1 PM.</div>
      </div>
    </AppShell>
  </div>
</template>

<style>
.timetable-page {
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

.week-nav {
  display: flex;
  align-items: center;
  gap: 8px;
  margin: 12px 0;
}

.nav-btn,
.outline-btn {
  height: 36px;
  border-radius: 10px;
  border: 1px solid var(--tf-border-default);
  background: transparent;
  color: var(--tf-text-secondary);
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  padding: 0 12px;
}

.timetable-grid {
  padding: 0;
  overflow: hidden;
}

.grid-header {
  display: grid;
  grid-template-columns: 44px repeat(7, 1fr);
  background: var(--tf-bg-card-alt);
  border-bottom: 1px solid var(--tf-border-default);
}

.day-header {
  text-align: center;
  padding: 7px 4px;
  font-size: 10px;
  font-weight: 700;
  color: var(--tf-text-secondary);
}

.grid-body {
  position: relative;
}

.time-row {
  display: grid;
  grid-template-columns: 44px repeat(7, 1fr);
  height: 36px;
  border-bottom: 1px solid var(--tf-border-default);
}

.time-label {
  font-size: 9px;
  font-family: 'JetBrains Mono', 'Cascadia Code', monospace;
  text-align: right;
  padding-right: 6px;
  color: var(--tf-text-hint);
}

.time-cell {
  border-left: 1px solid var(--tf-border-default);
}

.block {
  position: absolute;
  left: 60px;
  width: calc((100% - 44px) / 7);
  height: 72px;
  border-radius: 5px;
  padding: 4px 6px;
  color: #fff;
}

.block-title {
  font-size: 10px;
  font-weight: 700;
}

.block-time {
  font-size: 8.5px;
  opacity: 0.75;
}

.insight-card {
  margin-top: 12px;
}

.insight {
  padding: 10px 12px;
  border-left: 2px solid var(--tf-violet);
  background: rgba(124, 92, 252, 0.08);
  font-size: 12px;
  color: var(--tf-text-secondary);
}
</style>
