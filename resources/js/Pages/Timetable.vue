<script setup>
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';
import AppShell from '../Layouts/AppShell.vue';
import ModalBase from '../Components/ModalBase.vue';

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
const insightText = ref('Insights will appear after more sessions are logged.');
const showBlockModal = ref(false);
const blockForm = ref({ title: '', type: 'study', start_time: '09:00', end_time: '10:00', days_of_week: [], color: '#7C5CFC' });

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
  loadInsight();
});

const loadInsight = async () => {
  try {
    const res = await axios.get('/api/analytics/insights');
    const data = res.data?.data;
    if (Array.isArray(data) && data.length > 0) {
      insightText.value = data[0].message;
    }
  } catch {
    // Insights are optional
  }
};

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

const prevWeek = () => {
  startDate.value = addDays(startDate.value, -7);
  loadBlocks();
};

const nextWeek = () => {
  startDate.value = addDays(startDate.value, 7);
  loadBlocks();
};

const startDate = ref(startOfWeek(new Date()));

const createBlock = async () => {
  try {
    await axios.post('/api/timetable/blocks', blockForm.value);
    showBlockModal.value = false;
    blockForm.value = { title: '', type: 'study', start_time: '09:00', end_time: '10:00', days_of_week: [], color: '#7C5CFC' };
    loadBlocks();
    if (window.TimeflowToast) window.TimeflowToast.success('Block added');
  } catch (error) {
    if (window.TimeflowToast) window.TimeflowToast.error('Failed to add block');
  }
};

const toggleDay = (dayNum) => {
  const idx = blockForm.value.days_of_week.indexOf(dayNum);
  if (idx >= 0) blockForm.value.days_of_week.splice(idx, 1);
  else blockForm.value.days_of_week.push(dayNum);
};
</script>

<template>
  <div class="timetable-page">
    <AppShell :navigation="props.navigation">
      <div class="page-header">
        <div>
          <div class="page-title">Timetable</div>
          <div class="page-subtitle">Plan the week with smart blocks.</div>
        </div>
        <button class="outline-btn" type="button" @click="showBlockModal = true">+ Add block</button>
      </div>

      <div class="week-nav">
        <button class="nav-btn" type="button" @click="prevWeek">&lt; Week</button>
        <div class="tf-date-badge">{{ weekLabel }}</div>
        <button class="nav-btn" type="button" @click="nextWeek">Week &gt;</button>
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
              left: `calc(55px + (${block.dayIndex}) * ((100% - 55px) / 7))`,
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
        <div class="insight">{{ insightText }}</div>
      </div>

      <ModalBase :open="showBlockModal" title="Add Timetable Block" @close="showBlockModal = false">
        <div class="field">
          <label class="field-label">Title</label>
          <input class="text-input" type="text" v-model="blockForm.title" placeholder="e.g. Physics Revision" />
        </div>
        <div class="field">
          <label class="field-label">Type</label>
          <select class="text-input" v-model="blockForm.type">
            <option value="study">Study</option>
            <option value="break">Break</option>
            <option value="class">Class</option>
            <option value="other">Other</option>
          </select>
        </div>
        <div class="field">
          <label class="field-label">Start time</label>
          <input class="text-input" type="time" v-model="blockForm.start_time" />
        </div>
        <div class="field">
          <label class="field-label">End time</label>
          <input class="text-input" type="time" v-model="blockForm.end_time" />
        </div>
        <div class="field">
          <label class="field-label">Days</label>
          <div style="display:flex;gap:8px;flex-wrap:wrap">
            <button v-for="(d, i) in ['Mon','Tue','Wed','Thu','Fri','Sat','Sun']" :key="i" type="button" class="outline-btn" :style="{ background: blockForm.days_of_week.includes(i+1) ? 'var(--tf-violet)' : 'transparent', color: blockForm.days_of_week.includes(i+1) ? '#fff' : 'var(--tf-text-secondary)' }" @click="toggleDay(i+1)">{{ d }}</button>
          </div>
        </div>
        <template #footer>
          <button class="outline-btn" type="button" @click="showBlockModal = false">Cancel</button>
          <button class="primary-btn" type="button" @click="createBlock">Add</button>
        </template>
      </ModalBase>
    </AppShell>
  </div>
</template>

<style scoped>
.timetable-page {
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

.week-nav {
  display: flex;
  align-items: center;
  gap: 10px;
  margin: 15px 0;
}

.nav-btn,
.outline-btn {
  height: 45px;
  border-radius: 13px;
  border: 1px solid var(--tf-border-default);
  background: transparent;
  color: var(--tf-text-secondary);
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  padding: 0 15px;
}

.timetable-grid {
  padding: 0;
  overflow: hidden;
}

.grid-header {
  display: grid;
  grid-template-columns: 55px repeat(7, 1fr);
  background: var(--tf-bg-card-alt);
  border-bottom: 1px solid var(--tf-border-default);
}

.day-header {
  text-align: center;
  padding: 9px 5px;
  font-size: 13px;
  font-weight: 700;
  color: var(--tf-text-secondary);
}

.grid-body {
  position: relative;
}

.time-row {
  display: grid;
  grid-template-columns: 55px repeat(7, 1fr);
  height: 45px;
  border-bottom: 1px solid var(--tf-border-default);
}

.time-label {
  font-size: 11px;
  font-family: 'JetBrains Mono', 'Cascadia Code', monospace;
  text-align: right;
  padding-right: 8px;
  color: var(--tf-text-hint);
}

.time-cell {
  border-left: 1px solid var(--tf-border-default);
}

.block {
  position: absolute;
  left: 75px;
  width: calc((100% - 55px) / 7);
  height: 90px;
  border-radius: 6px;
  padding: 5px 8px;
  color: #fff;
}

.block-title {
  font-size: 13px;
  font-weight: 700;
}

.block-time {
  font-size: 11px;
  opacity: 0.75;
}

.insight-card {
  margin-top: 15px;
}

.insight {
  padding: 13px 15px;
  border-left: 3px solid var(--tf-violet);
  background: rgba(124, 92, 252, 0.08);
  font-size: 15px;
  color: var(--tf-text-secondary);
}
</style>
