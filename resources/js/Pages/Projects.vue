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

const showArchived = ref(false);
const projects = ref([]);

const filteredProjects = computed(() => {
  if (showArchived.value) return projects.value;
  return projects.value.filter((project) => !project.archived);
});

const loadProjects = async () => {
  try {
    const response = await axios.get('/api/projects/summary');
    if (Array.isArray(response.data?.data)) {
      projects.value = response.data.data.map((project) => ({
        ...project,
        hoursLabel: formatHours(project.total_seconds),
        progress: project.progress_percent ?? 0,
        lastSessionLabel: formatLastSession(project.last_session_at),
      }));
    }
  } catch (error) {
    console.warn('Project summary fetch failed', error);
  }
};

onMounted(() => {
  loadProjects();
});

const formatHours = (seconds) => {
  const value = Number(seconds || 0) / 3600;
  return `${value.toFixed(1)}h`;
};

const formatLastSession = (isoString) => {
  if (!isoString) return 'No sessions yet';
  const date = new Date(isoString);
  const diffDays = Math.floor((Date.now() - date.getTime()) / 86400000);
  if (diffDays <= 0) return 'Last session today';
  if (diffDays === 1) return 'Last session yesterday';
  return `Last session ${diffDays} days ago`;
};
</script>

<template>
  <div class="projects-page">
    <AppShell :navigation="props.navigation">
      <div class="page-header">
        <div>
          <div class="page-title">Projects</div>
          <div class="page-subtitle">Keep budgets and progress visible.</div>
        </div>
        <button class="primary-btn" type="button">New project</button>
      </div>

      <label class="toggle-row">
        <input type="checkbox" v-model="showArchived" />
        Show archived
      </label>

      <div class="projects-grid">
        <div
          v-for="project in filteredProjects"
          :key="project.id"
          class="tf-card project-card"
          :class="{ archived: project.archived }"
        >
          <div class="accent" :style="{ background: project.color }"></div>
          <div class="project-content">
            <div class="project-name">{{ project.name }}</div>
            <div class="project-client" v-if="project.client_name">{{ project.client_name }}</div>
            <div class="project-chip">{{ project.category || 'Uncategorized' }}</div>
            <div class="project-stats">
              <span>{{ project.hoursLabel }} logged</span>
              <span v-if="project.budget_hours">Budget {{ project.budget_hours }}h</span>
            </div>
            <div class="budget-bar">
              <span
                class="budget-fill"
                :style="{
                  width: Math.min(project.progress, 100) + '%',
                  background: project.progress >= 100 ? 'var(--tf-red)' : project.progress >= 80 ? 'var(--tf-amber)' : 'var(--tf-mint)'
                }"
              ></span>
            </div>
            <div class="project-footer">{{ project.lastSessionLabel }}</div>
          </div>
          <div class="project-actions">
            <button class="tf-icon-button" type="button" aria-label="Edit project"><i class="ti ti-edit" aria-hidden="true"></i></button>
            <button class="tf-icon-button" type="button" aria-label="Archive project"><i class="ti ti-archive" aria-hidden="true"></i></button>
          </div>
        </div>
      </div>
    </AppShell>
  </div>
</template>

<style>
.projects-page {
  min-height: 100vh;
  background: var(--tf-bg-page);
  padding: 14px;
  color: var(--tf-text-primary);
  font-family: 'Plus Jakarta Sans', 'Segoe UI', sans-serif;
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

.primary-btn {
  height: 40px;
  padding: 0 16px;
  border-radius: 10px;
  border: none;
  background: var(--tf-violet);
  color: #fff;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
}

.toggle-row {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  color: var(--tf-text-secondary);
  margin: 12px 0;
}

.projects-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 12px;
}

.project-card {
  display: flex;
  gap: 12px;
  position: relative;
}

.project-card.archived {
  opacity: 0.6;
}

.accent {
  width: 3px;
  border-radius: 2px;
}

.project-content {
  display: flex;
  flex-direction: column;
  gap: 6px;
  flex: 1;
}

.project-name {
  font-size: 14px;
  font-weight: 700;
}

.project-client {
  font-size: 11px;
  color: var(--tf-text-secondary);
}

.project-chip {
  font-size: 9px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  align-self: flex-start;
  padding: 2px 8px;
  border-radius: 999px;
  background: var(--tf-bg-card-alt);
  color: var(--tf-text-secondary);
}

.project-stats {
  font-size: 11px;
  color: var(--tf-text-secondary);
  display: flex;
  gap: 10px;
}

.budget-bar {
  height: 4px;
  border-radius: 999px;
  background: rgba(80, 60, 20, 0.08);
  overflow: hidden;
}

.budget-fill {
  display: block;
  height: 100%;
}

.project-footer {
  font-size: 10px;
  color: var(--tf-text-hint);
}

.project-actions {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
</style>
