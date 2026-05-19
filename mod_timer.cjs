const fs = require('fs');

let content = fs.readFileSync('resources/js/Pages/Timer.vue', 'utf8');

// 1. Add script setup logic
const scriptLogic = `
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
`;

content = content.replace('const saveManualEntry = async () => {', scriptLogic + '\nconst saveManualEntry = async () => {');

// Fix loadSessionLog to include notes
content = content.replace(/duration:\s*formatDuration\(s\.duration_seconds\),/g, "duration: formatDuration(s.duration_seconds),\n          notes: s.notes || '',");

// 2. Remove manual-card
content = content.replace(/<div class="tf-card manual-card">[\s\S]*?<\/div>\s*<\/section>/, '</section>');

// 3. Replace log-panel with table
const newLogPanel = `
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
`;

content = content.replace(/<section v-else class="tab-panel log-panel">[\s\S]*?<\/section>/, newLogPanel);

// 4. Add Edit Modal
const modalHtml = `
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
`;
content = content.replace('</AppShell>', modalHtml);

// 5. Add CSS
const css = `
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
`;
content = content.replace('</style>', css);

fs.writeFileSync('resources/js/Pages/Timer.vue', content, 'utf8');
console.log('Timer.vue updated successfully.');
