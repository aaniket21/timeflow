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

const reports = ref([]);

const formatRange = (start, end) => {
  if (!start || !end) return 'Range unavailable';
  const startDate = new Date(start);
  const endDate = new Date(end);
  const startLabel = startDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
  const endLabel = endDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
  return `${startLabel} - ${endLabel}`;
};

const loadReports = async () => {
  try {
    const response = await axios.get('/api/settings/export');
    const data = response.data?.data?.reports;
    if (Array.isArray(data)) {
      reports.value = data.map((report) => ({
        id: report.id,
        title: report.title || `Report #${report.id}`,
        range: formatRange(report.date_from, report.date_to),
        status: report.file_path ? 'Ready' : 'Generating',
        statusColor: report.file_path ? 'var(--tf-mint)' : 'var(--tf-amber)',
        shareToken: report.share_token,
      }));
    }
  } catch (error) {
    console.warn('Reports fetch failed', error);
  }
};

const downloadReport = (reportId) => {
  if (!reportId) return;
  window.location.href = `/api/reports/${reportId}/download`;
};

const shareReport = (report) => {
  if (!report?.shareToken) return;
  const link = `${window.location.origin}/reports/share/${report.shareToken}`;
  if (navigator.clipboard?.writeText) {
    navigator.clipboard.writeText(link);
  } else {
    window.prompt('Copy report link', link);
  }
};

onMounted(() => {
  loadReports();
});
</script>

<template>
  <div class="reports-page">
    <AppShell :navigation="props.navigation">
      <div class="page-header">
        <div>
          <div class="page-title">Reports</div>
          <div class="page-subtitle">Generate shareable summaries.</div>
        </div>
        <button class="primary-btn" type="button">Generate report</button>
      </div>

      <div class="report-list">
        <div v-if="reports.length === 0" class="tf-card report-card">
          <div>
            <div class="report-title">No reports yet</div>
            <div class="report-range">Generate a report to get started.</div>
          </div>
        </div>
        <div v-for="report in reports" :key="report.id" class="tf-card report-card">
          <div>
            <div class="report-title">{{ report.title }}</div>
            <div class="report-range">{{ report.range }}</div>
          </div>
          <div class="report-status" :style="{ color: report.statusColor }">{{ report.status }}</div>
          <div class="report-actions">
            <button class="outline-btn" type="button" @click="downloadReport(report.id)">
              <i class="ti ti-download" aria-hidden="true"></i> Download
            </button>
            <button class="outline-btn" type="button" @click="shareReport(report)">
              <i class="ti ti-share" aria-hidden="true"></i> Share
            </button>
            <button class="tf-icon-button" type="button" aria-label="Delete report"><i class="ti ti-trash" aria-hidden="true"></i></button>
          </div>
        </div>
      </div>
    </AppShell>
  </div>
</template>

<style>
.reports-page {
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

.report-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-top: 12px;
}

.report-card {
  display: grid;
  grid-template-columns: 1fr auto auto;
  gap: 12px;
  align-items: center;
}

.report-title {
  font-size: 14px;
  font-weight: 700;
}

.report-range {
  font-size: 12px;
  color: var(--tf-text-secondary);
}

.report-status {
  font-size: 11px;
  font-weight: 700;
}

.report-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}

.outline-btn {
  height: 32px;
  padding: 0 10px;
  border-radius: 8px;
  border: 1px solid var(--tf-border-default);
  background: transparent;
  color: var(--tf-text-secondary);
  font-size: 11px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  cursor: pointer;
}
</style>
