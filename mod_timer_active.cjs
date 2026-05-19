const fs = require('fs');
let content = fs.readFileSync('resources/js/Pages/Timer.vue', 'utf8');

const injectLoadActive = `
const loadActiveSession = async () => {
  try {
    const res = await axios.get('/api/sessions/active');
    const session = res.data?.data?.session;
    if (session) {
      activeSessionId.value = session.id;
      selectedProjectId.value = session.project_id || '';
      sessionLabel.value = session.label || '';
      mode.value = session.type || 'timer';
      
      const startedAt = new Date(session.started_at).getTime();
      timerSeconds.value = Math.max(0, Math.floor((Date.now() - startedAt) / 1000));
      isRunning.value = true;
      startTicking();
    }
  } catch (e) {
    console.warn('Load active session failed', e);
  }
};

const startTicking = () => {`;

content = content.replace('const startTicking = () => {', injectLoadActive);

const injectOnMounted = `onMounted(() => {
  loadProjects();
  loadSessionLog(true);
  loadActiveSession();
});`;

content = content.replace(/onMounted\(\(\) => \{\s*loadProjects\(\);\s*loadSessionLog\(true\);\s*\}\);/, injectOnMounted);

fs.writeFileSync('resources/js/Pages/Timer.vue', content, 'utf8');
console.log('Timer.vue active sync updated successfully.');
