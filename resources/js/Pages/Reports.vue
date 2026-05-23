<script setup>
import axios from 'axios';
import { onMounted, ref } from 'vue';
import AppShell from '../Layouts/AppShell.vue';
import ModalBase from '../Components/ModalBase.vue';
import { useTime } from '../composables/useTime';

const { formatDate } = useTime();

const props = defineProps({
  navigation: {
    type: Object,
    default: () => ({ sections: [] }),
  },
});

const reports = ref([]);
const showGenerateModal = ref(false);
const reportForm = ref({ title: '', date_from: '', date_to: '' });

const formatRange = (start, end) => {
  if (!start || !end) return 'Range unavailable';
  return `${formatDate(start, 'MMM D')} - ${formatDate(end, 'MMM D')}`;
};

const loadReports = async () => {
  try {
    let data = null;

    // Try dedicated reports endpoint first
    try {
      const res = await axios.get('/api/reports');
      data = res.data?.data;
    } catch {
      // Fallback to export endpoint
      const res = await axios.get('/api/settings/export');
      data = res.data?.data?.reports;
    }

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

const generateReport = async () => {
  try {
    await axios.post('/api/reports', reportForm.value);
    showGenerateModal.value = false;
    reportForm.value = { title: '', date_from: '', date_to: '' };
    loadReports();
    if (window.TimeflowToast) window.TimeflowToast.success('Report generation started');
  } catch (error) {
    if (window.TimeflowToast) window.TimeflowToast.error('Failed to generate report');
  }
};

const deleteReport = async (id) => {
  try {
    await axios.delete(`/api/reports/${id}`);
    loadReports();
    if (window.TimeflowToast) window.TimeflowToast.success('Report deleted');
  } catch (error) {
    console.warn('Delete report failed', error);
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
        <button class="primary-btn" type="button" @click="showGenerateModal = true">Generate report</button>
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
            <button class="tf-icon-button" type="button" aria-label="Delete report" @click="deleteReport(report.id)"><i class="ti ti-trash" aria-hidden="true"></i></button>
          </div>
        </div>
      </div>

      <ModalBase :open="showGenerateModal" title="Generate Report" @close="showGenerateModal = false">
        <div class="field">
          <label class="field-label">Report title</label>
          <input class="text-input" type="text" v-model="reportForm.title" placeholder="e.g. Weekly Summary" />
        </div>
        <div class="field">
          <label class="field-label">From date</label>
          <input class="text-input" type="date" v-model="reportForm.date_from" />
        </div>
        <div class="field">
          <label class="field-label">To date</label>
          <input class="text-input" type="date" v-model="reportForm.date_to" />
        </div>
        <template #footer>
          <button class="outline-btn" type="button" @click="showGenerateModal = false">Cancel</button>
          <button class="primary-btn" type="button" @click="generateReport">Generate</button>
        </template>
      </ModalBase>
    </AppShell>
  </div>
</template>

<style scoped>
.reports-page {
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

.primary-btn {
  height: 50px;
  padding: 0 20px;
  border-radius: 13px;
  border: none;
  background: var(--tf-violet);
  color: #fff;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
}

.report-list {
  display: flex;
  flex-direction: column;
  gap: 13px;
  margin-top: 15px;
}

.report-card {
  display: grid;
  grid-template-columns: 1fr auto auto;
  gap: 15px;
  align-items: center;
}

.report-title {
  font-size: 18px;
  font-weight: 700;
}

.report-range {
  font-size: 15px;
  color: var(--tf-text-secondary);
}

.report-status {
  font-size: 14px;
  font-weight: 700;
}

.report-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

.outline-btn {
  height: 40px;
  padding: 0 13px;
  border-radius: 10px;
  border: 1px solid var(--tf-border-default);
  background: transparent;
  color: var(--tf-text-secondary);
  font-size: 14px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
}
</style>
