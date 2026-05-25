<script setup>
import axios from 'axios';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import AppShell from '../Layouts/AppShell.vue';
import TfModal from '../Components/TfModal.vue';
import { useTime } from '../composables/useTime';
import { useTimer } from '../composables/useTimer';

const { format, formatTime, sessionGroupLabel, toTimestamp, dayjs } = useTime();
const { saveState, loadState, clearState } = useTimer();

const props = defineProps({
  navigation: {
    type: Object,
    default: () => ({ sections: [] }),
  },
});

const activeTab = ref('active');
const mode = ref('timer');
const isRunning = ref(false);
const projectMenuOpen = ref(false);
const mainCategory = ref('Project');
const mainCategoryMenuOpen = ref(false);
const activityTitle = ref('');
const otherTitle = ref('');
const manualOpen = ref(false);
const selectedProjectId = ref('');
const sessionLabel = ref('');
const sessionNotes = ref('');
const activeSessionId = ref(null);

watch(sessionNotes, (newVal) => {
  if (activeSessionId.value) {
    localStorage.setItem(`tf_notes_${activeSessionId.value}`, newVal);
  }
});
const timerSeconds = ref(0);
const pomodoroSeconds = ref(25 * 60);
const pomodoroPhase = ref('work');
const pomodoroCycle = ref(0);

const projects = ref([]);
const sessionGroups = ref([]);
const logPage = ref(1);
const hasMoreLogs = ref(false);

const manualForm = ref({ project_id: '', date: '', start: '', end: '' });

let timerInterval = null;

const activeProject = computed(() =>
  projects.value.find((p) => p.id === selectedProjectId.value) || null
);

const projectGroups = computed(() => {
  const showArchived = localStorage.getItem('tf_show_archived') === 'true';
  const grouped = {};
  projects.value.forEach((p) => {
    if (p.archived && !showArchived) return;
    const cat = p.category || 'Other';
    if (!grouped[cat]) grouped[cat] = [];
    grouped[cat].push(p);
  });
  return Object.entries(grouped).map(([label, items]) => ({ label, items }));
});

const timerDisplay = computed(() => {
  const total = mode.value === 'pomodoro' ? pomodoroSeconds.value : timerSeconds.value;
  const h = String(Math.floor(total / 3600)).padStart(2, '0');
  const m = String(Math.floor((total % 3600) / 60)).padStart(2, '0');
  const s = String(total % 60).padStart(2, '0');
  return mode.value === 'pomodoro' ? `${m}:${s}` : `${h}:${m}:${s}`;
});

const statusLabel = computed(() => (isRunning.value ? 'Session in progress' : 'Ready to start'));
const startStopLabel = computed(() => (isRunning.value ? 'Stop Session' : 'Start Session'));

const dateLabel = computed(() => format(undefined, 'ddd, MMM D'));

const pomodoroDots = computed(() => {
  const dots = [];
  for (let i = 0; i < 4; i++) {
    if (i < pomodoroCycle.value) dots.push({ id: i, state: 'done' });
    else if (i === pomodoroCycle.value && isRunning.value) dots.push({ id: i, state: 'active' });
    else dots.push({ id: i, state: 'pending' });
  }
  return dots;
});

const loadProjects = async () => {
  try {
    const res = await axios.get('/api/projects/summary');
    if (Array.isArray(res.data?.data)) {
      projects.value = res.data.data.map((p) => ({
        id: p.id,
        name: p.name,
        category: p.category || 'Other',
        color: p.color || 'violet',
        archived: p.archived,
      }));
    }
  } catch (e) {
    console.warn('Projects fetch failed', e);
  }
};

const initQuickStart = () => {
  if (selectedProjectId.value) return; // already set by active session
  const lastId = localStorage.getItem('tf_last_project_id');
  if (lastId && projects.value.some(p => p.id === Number(lastId))) {
    selectedProjectId.value = Number(lastId);
    mainCategory.value = 'Project';
  } else if (projects.value.length > 0) {
    selectedProjectId.value = projects.value[0].id;
    mainCategory.value = 'Project';
  }
};

const loadSessionLog = async (reset = false) => {
  if (reset) { logPage.value = 1; sessionGroups.value = []; }
  try {
    const res = await axios.get('/api/sessions', { params: { page: logPage.value, per_page: 10 } });
    const data = res.data?.data;
    if (Array.isArray(data)) {
      const grouped = {};
      data.forEach((s) => {
        const label = sessionGroupLabel(s.started_at);

        if (!grouped[label]) grouped[label] = [];
        grouped[label].push({
          id: s.id,
          project: s.label || s.project?.name || s.category?.name || 'Untitled',
          category: s.category?.name || s.project?.category?.name || '',
          color: s.color || s.project?.color || s.category?.color || 'violet',
          start: formatTime(s.started_at),
          duration: formatDuration(s.duration_seconds),
          notes: s.notes || '',
          type: s.type || 'timer',
        });
      });
      sessionGroups.value = Object.entries(grouped).map(([label, sessions]) => ({ label, sessions }));
      hasMoreLogs.value = (res.data?.meta?.last_page || 1) > logPage.value;
    }
  } catch (e) {
    console.warn('Session log fetch failed', e);
  }
};

const startSession = async () => {
  isRunning.value = true;
  timerSeconds.value = 0;
  startTicking();

  let finalLabel = sessionLabel.value || null;
  let finalProjectId = selectedProjectId.value || null;

  if (mainCategory.value === 'Activity') {
    finalLabel = activityTitle.value ? `activity-${activityTitle.value}` : 'activity-untitled';
    finalProjectId = null;
  } else if (mainCategory.value === 'Other') {
    finalLabel = otherTitle.value ? `other-${otherTitle.value}` : 'other-untitled';
    finalProjectId = null;
  } else if (mainCategory.value === 'Focus Mode') {
    finalLabel = 'focus-mode';
    finalProjectId = null;
  } else if (mainCategory.value === 'Project') {
    if (!finalProjectId) finalLabel = 'project-untitled';
  }

  try {
    const res = await axios.post('/api/sessions/start', {
      project_id: finalProjectId,
      label: finalLabel,
      type: mode.value,
    });
    activeSessionId.value = res.data?.data?.session?.id || null;
    if (window.TimeflowToast) window.TimeflowToast.success('Session started');
  } catch (e) {
    console.warn('Start session API failed (timer running locally)', e);
    // Offline mode: generate a temporary ID if failed
    if (!activeSessionId.value) activeSessionId.value = 'offline_' + Date.now();
  }

  // Save to IndexedDB
  await saveState({
    activeSessionId: activeSessionId.value,
    selectedProjectId: selectedProjectId.value,
    mainCategory: mainCategory.value,
    sessionLabel: sessionLabel.value,
    mode: mode.value,
    startedAt: Date.now()
  });
};

const stopSession = async () => {
  stopTicking();
  isRunning.value = false;
  if (activeSessionId.value) {
    try {
      await axios.post(`/api/sessions/${activeSessionId.value}/stop`, {
        notes: sessionNotes.value || null,
      });
      localStorage.removeItem(`tf_notes_${activeSessionId.value}`);
    } catch (e) {
      console.warn('Stop session failed', e);
      if (window.TimeflowToast) window.TimeflowToast.info('Offline: Session saved, will sync later');
    }
  }
  activeSessionId.value = null;
  timerSeconds.value = 0;
  sessionNotes.value = '';
  await clearState();
  loadSessionLog(true);
};

const toggleTimer = () => {
  if (isRunning.value) { stopSession(); } else { startSession(); }
};


const loadActiveSession = async () => {
  try {
    const res = await axios.get('/api/sessions/active');
    const session = res.data?.data?.session;
    if (session) {
      await restoreSessionFromData(session, toTimestamp(session.started_at));
      return;
    }
  } catch (e) {
    console.warn('Load active session API failed', e);
  }

  // Fallback to IndexedDB (offline mode)
  const offlineState = await loadState();
  if (offlineState && offlineState.activeSessionId) {
    console.log('Restored offline session state');
    await restoreSessionFromData({
      id: offlineState.activeSessionId,
      project_id: offlineState.selectedProjectId,
      label: offlineState.sessionLabel,
      type: offlineState.mode
    }, offlineState.startedAt);
    mainCategory.value = offlineState.mainCategory || 'Project';
  }
};

const restoreSessionFromData = async (session, startedAtTimestamp) => {
  activeSessionId.value = session.id;
  selectedProjectId.value = session.project_id || '';
  sessionLabel.value = session.label || '';
  mode.value = session.type || 'timer';
  
  if (session.label === 'focus-untitled' || session.type === 'pomodoro') {
    mainCategory.value = 'Focus Mode';
  } else if (session.project_id || session.label === 'project-untitled') {
    mainCategory.value = 'Project';
  } else if (session.label && session.label.startsWith('activity-')) {
    mainCategory.value = 'Activity';
    const name = session.label.replace('activity-', '');
    activityTitle.value = name === 'untitled' ? '' : name;
  } else if (session.label && session.label.startsWith('other-')) {
    mainCategory.value = 'Other';
    const name = session.label.replace('other-', '');
    otherTitle.value = name === 'untitled' ? '' : name;
  } else {
    mainCategory.value = 'Project';
  }

  sessionNotes.value = localStorage.getItem(`tf_notes_${session.id}`) || session.notes || '';

  timerSeconds.value = Math.max(0, Math.floor((Date.now() - startedAtTimestamp) / 1000));
  isRunning.value = true;
  startTicking();
};

const startTicking = () => {
  stopTicking();
  timerInterval = setInterval(() => {
    if (mode.value === 'pomodoro') {
      pomodoroSeconds.value = Math.max(0, pomodoroSeconds.value - 1);
      if (pomodoroSeconds.value === 0) {
        if (pomodoroPhase.value === 'work') {
          pomodoroPhase.value = 'break';
          pomodoroSeconds.value = 5 * 60;
          pomodoroCycle.value += 1;
        } else {
          pomodoroPhase.value = 'work';
          pomodoroSeconds.value = 25 * 60;
        }
      }
    } else {
      timerSeconds.value += 1;
    }
  }, 1000);
};

const stopTicking = () => {
  if (timerInterval) { clearInterval(timerInterval); timerInterval = null; }
};

const selectProject = (id) => {
  selectedProjectId.value = id;
  projectMenuOpen.value = false;
  localStorage.setItem('tf_last_project_id', id);
};

const setTab = (tab) => {
  activeTab.value = tab;
  if (tab === 'log') loadSessionLog(true);
};

const setMode = (v) => { mode.value = v; };

const loadMoreLogs = () => {
  logPage.value += 1;
  loadSessionLog();
};


const editingSession = ref(null);
const editNotes = ref('');
const isEditModalOpen = ref(false);

const openEditModal = (session) => {
  editingSession.value = session;
  editNotes.value = session.notes || '';
  isEditModalOpen.value = true;
};

const closeEditModal = () => {
  isEditModalOpen.value = false;
  editingSession.value = null;
  editNotes.value = '';
};

const saveSessionEdit = async () => {
  if (!editingSession.value) return;
  try {
    await axios.put('/api/sessions/' + editingSession.value.id, {
      notes: editNotes.value,
    });
    closeEditModal();
    loadSessionLog(true);
  } catch (e) {
    console.warn('Edit session failed', e);
  }
};

const saveManualEntry = async () => {
  try {
    await axios.post('/api/sessions/manual', manualForm.value);
    manualOpen.value = false;
    manualForm.value = { project_id: '', date: '', start: '', end: '' };
    loadSessionLog(true);
  } catch (e) {
    console.warn('Manual entry failed', e);
  }
};

const deleteSession = async (id) => {
  try {
    await axios.delete(`/api/sessions/${id}`);
    loadSessionLog(true);
  } catch (e) {
    console.warn('Delete session failed', e);
  }
};

function formatDuration(sec) {
  const t = Math.max(0, Number(sec || 0));
  const h = String(Math.floor(t / 3600)).padStart(2, '0');
  const m = String(Math.floor((t % 3600) / 60)).padStart(2, '0');
  return `${h}:${m}`;
}

onMounted(async () => {
  await loadProjects();
  await loadActiveSession();
  initQuickStart();
  const savedTab = localStorage.getItem('tf_timer_tab');
  if (savedTab) {
    setTab(savedTab);
  }
});

onUnmounted(() => { stopTicking(); });
</script>

<template>
  <div class="timer-page">
    <AppShell :navigation="props.navigation">
      <div class="page-header">
          <div>
            <div class="page-title">Timer</div>
            <div class="page-subtitle">Track a live session or log a past one.</div>
          </div>
          <div class="tf-date-badge">{{ dateLabel }}</div>
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
            <div class="tf-section-label">Category</div>
            <button class="select-trigger" type="button" @click="mainCategoryMenuOpen = true">
              <span class="select-value">
                <span>{{ mainCategory }}</span>
              </span>
              <i class="ti ti-chevron-down" aria-hidden="true"></i>
            </button>
            <TfModal :isOpen="mainCategoryMenuOpen" title="Select Category" @close="mainCategoryMenuOpen = false">
              <div class="modal-list">
                <button class="modal-list-item" type="button" @click="mainCategory = 'Focus Mode'; mainCategoryMenuOpen = false">Focus Mode</button>
                <button class="modal-list-item" type="button" @click="mainCategory = 'Project'; mainCategoryMenuOpen = false">Project</button>
                <button class="modal-list-item" type="button" @click="mainCategory = 'Activity'; mainCategoryMenuOpen = false">Activity</button>
                <button class="modal-list-item" type="button" @click="mainCategory = 'Other'; mainCategoryMenuOpen = false">Other</button>
              </div>
            </TfModal>
          </div>

          <div v-if="mainCategory === 'Project'" class="tf-card select-card" style="margin-top: 16px;">
            <div class="tf-section-label">Select Project</div>
            <button class="select-trigger" type="button" @click="projectMenuOpen = true">
              <span v-if="activeProject" class="select-value">
                <span class="color-dot" :class="'dot-' + activeProject.color"></span>
                <span>{{ activeProject.name }}</span>
                <span class="select-meta">{{ activeProject.category }}</span>
              </span>
              <span v-else class="select-placeholder">Select project or category...</span>
              <i class="ti ti-chevron-down" aria-hidden="true"></i>
            </button>
            <TfModal :isOpen="projectMenuOpen" title="Select Project" @close="projectMenuOpen = false">
              <div class="modal-list">
                <div v-for="group in projectGroups" :key="group.label" class="modal-group">
                  <div class="modal-group-label">{{ group.label }}</div>
                  <button
                    v-for="project in group.items"
                    :key="project.id"
                    class="modal-list-item"
                    type="button"
                    @click="selectProject(project.id); projectMenuOpen = false"
                  >
                    <span class="color-dot" :class="'dot-' + project.color"></span>
                    <span>{{ project.name }}</span>
                  </button>
                </div>
                <a href="/projects" class="modal-new-btn">New project</a>
              </div>
            </TfModal>
          </div>

          <div v-if="mainCategory === 'Activity'" class="tf-card select-card" style="margin-top: 16px;">
            <div class="tf-section-label">Activity Title</div>
            <input class="text-input" type="text" placeholder="Enter activity name..." v-model="activityTitle" />
          </div>

          <div v-if="mainCategory === 'Other'" class="tf-card select-card" style="margin-top: 16px;">
            <div class="tf-section-label">Other Title</div>
            <input class="text-input" type="text" placeholder="Enter title..." v-model="otherTitle" />
          </div>

          <div class="tf-card timer-card">
            <div class="status-label" :class="{ running: isRunning }">{{ statusLabel }}</div>
            <div class="timer-display">{{ timerDisplay }}</div>
            <div class="fab-container">
              <button class="fab-btn" :class="{ danger: isRunning }" type="button" @click="toggleTimer">
                <i class="ti" :class="isRunning ? 'ti-square' : 'ti-player-play'" aria-hidden="true"></i>
              </button>
              <div class="fab-label">{{ startStopLabel }}</div>
            </div>
            <div v-if="activeProject" class="field">
              <input class="text-input" type="text" placeholder="Add a label (optional)..." v-model="sessionLabel" />
            </div>
          </div>

          <div class="tf-card notes-card">
            <div class="tf-section-label">Session notes</div>
            <textarea class="notes-input" placeholder="Add session notes..." v-model="sessionNotes"></textarea>
          </div>

          </section>

        
        <section v-else class="tab-panel log-panel">
          <div class="tf-card history-card">
            <div class="history-table-wrapper">
              <table class="history-table">
                <thead>
                  <tr>
                    <th>Date & Time</th>
                    <th>Project / Category</th>
                    <th>Duration</th>
                    <th>Notes</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <template v-for="group in sessionGroups" :key="group.label">
                    <tr class="group-row"><td colspan="5">{{ group.label }}</td></tr>
                    <tr v-for="session in group.sessions" :key="session.id">
                      <td>
                        <div class="log-time">{{ session.start }}</div>
                      </td>
                      <td>
                        <div class="log-left">
                          <span class="color-dot" :class="'dot-' + session.color"></span>
                          <div>
                            <div class="log-name">{{ session.project }}</div>
                            <div class="log-meta"><span class="category-chip">{{ session.category }}</span></div>
                          </div>
                        </div>
                      </td>
                      <td><div class="log-duration">{{ session.duration }}</div></td>
                      <td><div class="log-notes" :title="session.notes">{{ session.notes || '-' }}</div></td>
                      <td>
                        <div class="log-actions-table">
                          <button class="tf-icon-button" type="button" @click="openEditModal(session)" title="Edit Notes">
                            <i class="ti ti-edit"></i>
                          </button>
                          <button class="tf-icon-button text-danger" type="button" @click="deleteSession(session.id)" title="Delete Session">
                            <i class="ti ti-trash"></i>
                          </button>
                        </div>
                      </td>
                    </tr>
                  </template>
                  <tr v-if="sessionGroups.length === 0">
                    <td colspan="5" style="text-align:center; padding: 20px; color: var(--tf-text-secondary);">No past sessions found.</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <button v-if="hasMoreLogs" class="load-more" style="margin-top: 15px;" type="button" @click="loadMoreLogs">Load more</button>
          </div>
        </section>

    
      <div v-if="isEditModalOpen" class="modal-overlay" @click.self="closeEditModal">
        <div class="modal-content">
          <div class="modal-header">
            <h3>Edit Session Notes</h3>
            <button class="tf-icon-button" type="button" @click="closeEditModal"><i class="ti ti-x"></i></button>
          </div>
          <div class="modal-body">
            <textarea class="text-input notes-input" style="min-height: 100px; width: 100%; resize: vertical;" v-model="editNotes" placeholder="Enter session notes..."></textarea>
          </div>
          <div class="modal-footer" style="display:flex; justify-content: flex-end; gap: 10px; margin-top: 15px;">
            <button class="outline-btn" type="button" @click="closeEditModal">Cancel</button>
            <button class="primary-btn" type="button" @click="saveSessionEdit">Save Changes</button>
          </div>
        </div>
      </div>
    </AppShell>

  </div>
</template>

<style scoped>
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

.fab-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  margin: 20px 0;
}

.fab-btn {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  border: none;
  background: var(--tf-violet);
  color: #fff;
  font-size: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  box-shadow: 0 8px 24px rgba(124, 92, 252, 0.4);
  transition: transform 0.2s, box-shadow 0.2s;
}

.fab-btn:active {
  transform: scale(0.95);
  box-shadow: 0 4px 12px rgba(124, 92, 252, 0.4);
}

.fab-btn.danger {
  background: var(--tf-red);
  box-shadow: 0 8px 24px rgba(239, 68, 68, 0.4);
}

.fab-label {
  font-size: 13px;
  font-weight: 600;
  color: var(--tf-text-secondary);
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

.modal-list {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.modal-list-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 15px;
  background: transparent;
  border: 1px solid transparent;
  border-radius: 10px;
  font-size: 15px;
  color: var(--tf-text-primary);
  cursor: pointer;
  text-align: left;
  transition: background 0.2s;
}

.modal-list-item:hover {
  background: var(--tf-bg-hover);
}

.modal-group-label {
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  color: var(--tf-text-hint);
  padding: 10px 15px 5px;
}

.modal-new-btn {
  display: block;
  text-align: center;
  padding: 12px;
  margin-top: 10px;
  border-top: 1px solid var(--tf-border-default);
  color: #5b3fd4;
  text-decoration: none;
  font-weight: 600;
}

@media (max-width: 768px) {
  .timer-card .primary-btn {
    position: fixed;
    bottom: calc(85px + env(safe-area-inset-bottom));
    right: 20px;
    width: 65px;
    height: 65px;
    border-radius: 50%;
    font-size: 0;
    box-shadow: 0 5px 25px rgba(124, 92, 252, 0.5);
    z-index: 40;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  
  .timer-card .primary-btn i {
    font-size: 28px;
    margin: 0;
  }
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

.history-table-wrapper {
  overflow-x: auto;
}
.history-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
  font-size: 15px;
}
.history-table th {
  padding: 13px;
  border-bottom: 2px solid var(--tf-border-default);
  color: var(--tf-text-secondary);
  font-weight: 600;
}
.history-table td {
  padding: 15px 13px;
  border-bottom: 1px solid var(--tf-border-default);
  vertical-align: middle;
}
.group-row td {
  background: var(--tf-bg-card-alt);
  font-weight: 700;
  color: var(--tf-text-secondary);
  padding: 10px 13px;
  font-size: 14px;
}
.log-actions-table {
  display: flex;
  gap: 10px;
}
.log-notes {
  max-width: 250px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  color: var(--tf-text-secondary);
}
.text-danger {
  color: var(--tf-red) !important;
}
.modal-overlay {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}
.modal-content {
  background: var(--tf-bg-card);
  padding: 25px;
  border-radius: 15px;
  width: 90%;
  max-width: 500px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
}
.modal-header h3 {
  margin: 0;
  font-size: 18px;
}
</style>

