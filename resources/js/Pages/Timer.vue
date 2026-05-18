<script setup>
import { computed, ref } from 'vue';
import AppShell from '../Layouts/AppShell.vue';

const props = defineProps({
  navigation: {
    type: Object,
    default: () => ({
      sections: [
        {
          label: 'Main',
          items: [
            { label: 'Dashboard', icon: 'ti-layout-dashboard', active: false },
            { label: 'Timer', icon: 'ti-player-play', active: true },
            { label: 'Analytics', icon: 'ti-chart-bar', active: false },
            { label: 'Projects', icon: 'ti-folder', active: false, count: 4 },
          ],
        },
        {
          label: 'Grow',
          items: [
            { label: 'Achievements', icon: 'ti-trophy', active: false },
            { label: 'Goals', icon: 'ti-target', active: false },
            { label: 'Leaderboard', icon: 'ti-podium', active: false },
          ],
        },
        {
          label: 'Export',
          items: [
            { label: 'Reports', icon: 'ti-file-analytics', active: false },
          ],
        },
      ],
    }),
  },
});

const activeTab = ref('active');
const mode = ref('timer');
const isRunning = ref(false);
const projectMenuOpen = ref(false);
const manualOpen = ref(false);
const selectedProjectId = ref('');

const projects = [
  { id: 'p1', name: 'Frontend build', category: 'Coding', color: 'violet' },
  { id: 'p2', name: 'DSA practice', category: 'Study', color: 'mint' },
  { id: 'p3', name: 'Client review', category: 'Meetings', color: 'amber' },
  { id: 'p4', name: 'Gym session', category: 'Personal', color: 'rose' },
];

const sessionGroups = [
  {
    label: 'Today',
    sessions: [
      { id: 1, project: 'Frontend build', category: 'Coding', color: 'violet', start: '09:40 AM', duration: '01:24', type: 'timer' },
      { id: 2, project: 'DSA practice', category: 'Study', color: 'mint', start: '08:20 AM', duration: '00:52', type: 'pomodoro' },
      { id: 3, project: 'Team standup', category: 'Meetings', color: 'amber', start: '07:40 AM', duration: '00:15', type: 'timer' },
    ],
  },
  {
    label: 'Yesterday',
    sessions: [
      { id: 4, project: 'Wireframes', category: 'Design', color: 'sky', start: '05:10 PM', duration: '01:10', type: 'timer' },
      { id: 5, project: 'Math revision', category: 'Study', color: 'mint', start: '03:00 PM', duration: '00:25', type: 'pomodoro' },
    ],
  },
];

const pomodoroDots = [
  { id: 1, state: 'done' },
  { id: 2, state: 'done' },
  { id: 3, state: 'active' },
  { id: 4, state: 'pending' },
];

const activeProject = computed(() =>
  projects.find((project) => project.id === selectedProjectId.value) || null
);

const projectGroups = computed(() => {
  const grouped = {};
  projects.forEach((project) => {
    if (!grouped[project.category]) {
      grouped[project.category] = [];
    }
    grouped[project.category].push(project);
  });

  return Object.entries(grouped).map(([label, items]) => ({ label, items }));
});

const statusLabel = computed(() => (isRunning.value ? 'Session in progress' : 'Ready to start'));
const startStopLabel = computed(() => (isRunning.value ? 'Stop Session' : 'Start Session'));

const setTab = (tab) => {
  activeTab.value = tab;
};

const setMode = (value) => {
  mode.value = value;
};

const toggleTimer = () => {
  isRunning.value = !isRunning.value;
};

const selectProject = (id) => {
  selectedProjectId.value = id;
  projectMenuOpen.value = false;
};
</script>

<template>
  <div class="timer-page">
    <AppShell :navigation="props.navigation">
      <div class="page-header">
          <div>
            <div class="page-title">Timer</div>
            <div class="page-subtitle">Track a live session or log a past one.</div>
          </div>
          <div class="tf-date-badge">Thu, May 14</div>
        </div>

        <div class="tab-bar">
          <button
            class="tab-pill"
            :class="{ active: activeTab === 'active' }"
            type="button"
            @click="setTab('active')"
          >
            Active Timer
          </button>
          <button
            class="tab-pill"
            :class="{ active: activeTab === 'log' }"
            type="button"
            @click="setTab('log')"
          >
            Session Log
          </button>
        </div>

        <section v-if="activeTab === 'active'" class="tab-panel">
          <div class="tf-card select-card">
            <div class="tf-section-label">Project</div>
            <button class="select-trigger" type="button" @click="projectMenuOpen = !projectMenuOpen">
              <span v-if="activeProject" class="select-value">
                <span class="color-dot" :class="'dot-' + activeProject.color"></span>
                <span>{{ activeProject.name }}</span>
                <span class="select-meta">{{ activeProject.category }}</span>
              </span>
              <span v-else class="select-placeholder">Select project or category...</span>
              <i class="ti" :class="projectMenuOpen ? 'ti-chevron-up' : 'ti-chevron-down'" aria-hidden="true"></i>
            </button>
            <div v-if="projectMenuOpen" class="select-menu">
              <div v-for="group in projectGroups" :key="group.label" class="select-group">
                <div class="select-group-label">{{ group.label }}</div>
                <button
                  v-for="project in group.items"
                  :key="project.id"
                  class="select-item"
                  type="button"
                  @click="selectProject(project.id)"
                >
                  <span class="color-dot" :class="'dot-' + project.color"></span>
                  <span>{{ project.name }}</span>
                </button>
              </div>
              <button class="select-new" type="button">New project</button>
            </div>
          </div>

          <div class="mode-toggle">
            <button
              class="mode-pill"
              :class="{ active: mode === 'timer' }"
              type="button"
              @click="setMode('timer')"
            >
              Timer
            </button>
            <button
              class="mode-pill"
              :class="{ active: mode === 'pomodoro' }"
              type="button"
              @click="setMode('pomodoro')"
            >
              Pomodoro
            </button>
          </div>

          <div v-if="mode === 'timer'" class="tf-card timer-card">
            <div class="status-label" :class="{ running: isRunning }">{{ statusLabel }}</div>
            <div class="timer-display">00:42:19</div>
            <button class="primary-btn" :class="{ danger: isRunning }" type="button" @click="toggleTimer">
              <i class="ti" :class="isRunning ? 'ti-square' : 'ti-player-play'" aria-hidden="true"></i>
              {{ startStopLabel }}
            </button>
            <div v-if="activeProject" class="field">
              <input class="text-input" type="text" placeholder="Add a label (optional)..." />
            </div>
          </div>

          <div v-else class="tf-card pomodoro-card">
            <div class="pomodoro-ring">
              <svg viewBox="0 0 200 200" aria-hidden="true">
                <circle class="ring-track" cx="100" cy="100" r="88" />
                <circle class="ring-fill" cx="100" cy="100" r="88" />
              </svg>
              <div class="ring-time">12:34</div>
            </div>
            <div class="pomodoro-status">Work time</div>
            <div class="pomodoro-dots">
              <span
                v-for="dot in pomodoroDots"
                :key="dot.id"
                class="pomodoro-dot"
                :class="dot.state"
              ></span>
            </div>
            <div class="pomodoro-actions">
              <button class="primary-btn" type="button">Pause</button>
              <button class="secondary-btn" type="button">Skip break</button>
            </div>
            <a class="outline-btn" href="/timer?focus=1">Enter Focus Mode</a>
          </div>

          <div class="tf-card notes-card">
            <div class="tf-section-label">Session notes</div>
            <textarea class="notes-input" placeholder="Add session notes..."></textarea>
          </div>

          <div class="tf-card manual-card">
            <button class="manual-toggle" type="button" @click="manualOpen = !manualOpen">
              <span>Add past session</span>
              <i class="ti" :class="manualOpen ? 'ti-chevron-up' : 'ti-chevron-down'" aria-hidden="true"></i>
            </button>
            <div v-if="manualOpen" class="manual-fields">
              <div class="field">
                <label class="field-label">Project</label>
                <select class="text-input">
                  <option value="">Select project</option>
                  <option v-for="project in projects" :key="project.id" :value="project.id">
                    {{ project.name }}
                  </option>
                </select>
              </div>
              <div class="field-grid">
                <div class="field">
                  <label class="field-label">Date</label>
                  <input class="text-input" type="date" />
                </div>
                <div class="field">
                  <label class="field-label">Start</label>
                  <input class="text-input" type="time" />
                </div>
                <div class="field">
                  <label class="field-label">End</label>
                  <input class="text-input" type="time" />
                </div>
              </div>
              <button class="secondary-btn" type="button">Save session</button>
            </div>
          </div>
        </section>

        <section v-else class="tab-panel log-panel">
          <div v-for="group in sessionGroups" :key="group.label" class="log-group">
            <div class="log-header">{{ group.label }}</div>
            <div v-for="session in group.sessions" :key="session.id" class="log-row">
              <div class="log-left">
                <span class="color-dot" :class="'dot-' + session.color"></span>
                <div>
                  <div class="log-name">{{ session.project }}</div>
                  <div class="log-meta">
                    <span class="category-chip">{{ session.category }}</span>
                  </div>
                </div>
              </div>
              <div class="log-time">{{ session.start }}</div>
              <div class="log-duration">{{ session.duration }}</div>
              <div class="log-type">
                <i class="ti" :class="session.type === 'pomodoro' ? 'ti-alarm' : 'ti-clock'" aria-hidden="true"></i>
              </div>
              <div class="log-actions">
                <button class="tf-icon-button" type="button" aria-label="Edit session">
                  <i class="ti ti-edit" aria-hidden="true"></i>
                </button>
                <button class="tf-icon-button" type="button" aria-label="Delete session">
                  <i class="ti ti-trash" aria-hidden="true"></i>
                </button>
              </div>
            </div>
          </div>
          <button class="load-more" type="button">Load more</button>
        </section>
    </AppShell>
  </div>
</template>

<style>
.timer-page {
  min-height: 100vh;
  background: var(--tf-bg-page);
  padding: 14px;
  color: var(--tf-text-primary);
  font-family: 'Plus Jakarta Sans', 'Segoe UI', sans-serif;
}

.timer-page * {
  box-sizing: border-box;
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
  letter-spacing: -0.3px;
}

.page-subtitle {
  font-size: 12px;
  color: var(--tf-text-secondary);
}

.tab-bar {
  display: inline-flex;
  gap: 8px;
  padding: 4px;
  background: var(--tf-bg-card-alt);
  border-radius: 999px;
  align-self: flex-start;
}

.tab-pill {
  font-size: 12px;
  font-weight: 600;
  padding: 6px 16px;
  border-radius: 999px;
  border: none;
  background: transparent;
  color: var(--tf-text-secondary);
  cursor: pointer;
}

.tab-pill.active {
  background: var(--tf-violet);
  color: #fff;
}

.tab-panel {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.select-card {
  position: relative;
}

.select-trigger {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 10px 12px;
  border-radius: 10px;
  border: 1px solid var(--tf-border-default);
  background: var(--tf-bg-card-alt);
  font-size: 13px;
  color: var(--tf-text-secondary);
  cursor: pointer;
}

.select-value {
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--tf-text-primary);
}

.select-meta {
  font-size: 11px;
  color: var(--tf-text-hint);
}

.select-placeholder {
  color: var(--tf-text-hint);
}

.select-menu {
  margin-top: 10px;
  border-radius: 10px;
  padding: 10px;
  border: 1px solid var(--tf-border-default);
  background: var(--tf-bg-card);
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.select-group-label {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--tf-text-hint);
  margin-bottom: 4px;
}

.select-item {
  display: flex;
  align-items: center;
  gap: 8px;
  width: 100%;
  padding: 6px 8px;
  border-radius: 8px;
  border: 1px solid transparent;
  background: transparent;
  font-size: 12px;
  color: var(--tf-text-secondary);
  cursor: pointer;
}

.select-item:hover {
  background: var(--tf-bg-hover);
  color: var(--tf-text-primary);
}

.select-new {
  padding: 6px 10px;
  border-radius: 8px;
  border: 1px dashed var(--tf-border-emphasis);
  background: transparent;
  font-size: 11px;
  color: var(--tf-text-hint);
  cursor: pointer;
}

.mode-toggle {
  display: flex;
  justify-content: center;
  gap: 10px;
}

.mode-pill {
  padding: 6px 18px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 600;
  border: 1px solid var(--tf-border-default);
  background: var(--tf-bg-card-alt);
  color: var(--tf-text-secondary);
  cursor: pointer;
}

.mode-pill.active {
  background: rgba(124, 92, 252, 0.12);
  border-color: rgba(124, 92, 252, 0.28);
  color: #5b3fd4;
}

.timer-card,
.pomodoro-card {
  display: flex;
  flex-direction: column;
  gap: 12px;
  text-align: center;
}

.status-label {
  font-size: 12px;
  color: var(--tf-text-secondary);
}

.status-label.running {
  color: var(--tf-mint);
}

.timer-display {
  font-size: 44px;
  font-weight: 600;
  font-family: 'JetBrains Mono', 'Cascadia Code', monospace;
  color: var(--tf-text-primary);
}

.primary-btn {
  height: 56px;
  border-radius: 12px;
  border: none;
  background: var(--tf-violet);
  color: #fff;
  font-size: 14px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  cursor: pointer;
}

.primary-btn.danger {
  background: var(--tf-red);
}

.secondary-btn {
  height: 42px;
  border-radius: 10px;
  border: 1px solid var(--tf-border-default);
  background: var(--tf-bg-card-alt);
  color: var(--tf-text-secondary);
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
}

.outline-btn {
  height: 40px;
  border-radius: 10px;
  border: 1px solid rgba(124, 92, 252, 0.28);
  background: transparent;
  color: #5b3fd4;
  font-size: 12px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.field-label {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--tf-text-hint);
}

.text-input,
.notes-input {
  width: 100%;
  padding: 8px 10px;
  border-radius: 8px;
  border: 1px solid var(--tf-border-default);
  background: var(--tf-bg-card-alt);
  font-size: 13px;
  color: var(--tf-text-primary);
}

.notes-input {
  min-height: 80px;
  resize: vertical;
}

.manual-toggle {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 12px;
  font-weight: 600;
  border: none;
  background: transparent;
  cursor: pointer;
  color: var(--tf-text-secondary);
}

.manual-fields {
  margin-top: 12px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.field-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
}

.pomodoro-ring {
  width: 200px;
  height: 200px;
  margin: 0 auto;
  position: relative;
}

.pomodoro-ring svg {
  width: 100%;
  height: 100%;
  transform: rotate(-90deg);
}

.ring-track {
  fill: none;
  stroke: rgba(80, 60, 20, 0.12);
  stroke-width: 8;
}

.ring-fill {
  fill: none;
  stroke: var(--tf-violet);
  stroke-width: 8;
  stroke-linecap: round;
  stroke-dasharray: 553;
  stroke-dashoffset: 170;
}

.ring-time {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  font-weight: 600;
  font-family: 'JetBrains Mono', 'Cascadia Code', monospace;
}

.pomodoro-status {
  font-size: 12px;
  font-weight: 600;
  color: var(--tf-mint);
}

.pomodoro-dots {
  display: flex;
  justify-content: center;
  gap: 8px;
}

.pomodoro-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  border: 1px solid rgba(124, 92, 252, 0.35);
}

.pomodoro-dot.done {
  background: var(--tf-violet);
  border-color: var(--tf-violet);
}

.pomodoro-dot.active {
  background: var(--tf-mint);
  border-color: var(--tf-mint);
  box-shadow: 0 0 6px rgba(14, 207, 164, 0.5);
}

.pomodoro-actions {
  display: flex;
  gap: 10px;
  justify-content: center;
}

.notes-card {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.log-panel {
  gap: 16px;
}

.log-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.log-header {
  position: sticky;
  top: 0;
  background: var(--tf-bg-page);
  padding: 8px 0;
  font-size: 12px;
  font-weight: 600;
  color: var(--tf-text-secondary);
}

.log-row {
  display: grid;
  grid-template-columns: 1.2fr 0.5fr 0.4fr 0.2fr auto;
  align-items: center;
  gap: 12px;
  padding: 10px 0;
  border-bottom: 1px solid var(--tf-border-default);
}

.log-row:last-child {
  border-bottom: none;
}

.log-left {
  display: flex;
  align-items: center;
  gap: 10px;
}

.log-name {
  font-size: 13px;
  font-weight: 600;
}

.log-meta {
  margin-top: 2px;
}

.category-chip {
  display: inline-flex;
  align-items: center;
  padding: 2px 8px;
  border-radius: 999px;
  background: var(--tf-bg-card-alt);
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--tf-text-secondary);
}

.log-time {
  font-size: 10px;
  font-family: 'JetBrains Mono', 'Cascadia Code', monospace;
  color: var(--tf-text-hint);
}

.log-duration {
  font-size: 12px;
  font-weight: 600;
  font-family: 'JetBrains Mono', 'Cascadia Code', monospace;
  color: var(--tf-text-secondary);
}

.log-type {
  color: var(--tf-text-hint);
}

.log-actions {
  display: flex;
  gap: 6px;
  opacity: 0;
  transition: opacity 0.2s ease;
}

.log-row:hover .log-actions {
  opacity: 1;
}

.load-more {
  height: 40px;
  border-radius: 10px;
  border: 1px solid var(--tf-border-default);
  background: transparent;
  font-size: 12px;
  font-weight: 600;
  color: var(--tf-text-secondary);
  cursor: pointer;
}

.color-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  display: inline-block;
}

.dot-violet { background: var(--tf-violet); }
.dot-mint { background: var(--tf-mint); }
.dot-amber { background: var(--tf-amber); }
.dot-rose { background: var(--tf-rose); }
.dot-sky { background: var(--tf-sky); }
.dot-red { background: var(--tf-red); }

@media (max-width: 980px) {
  .timer-page {
    padding: 10px;
  }

  .field-grid {
    grid-template-columns: 1fr;
  }

  .log-row {
    grid-template-columns: 1fr;
    align-items: flex-start;
  }

  .log-actions {
    opacity: 1;
  }
}
</style>
