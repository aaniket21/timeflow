<script setup>
import axios from 'axios';
import { onMounted, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppShell from '../Layouts/AppShell.vue';

const props = defineProps({
  navigation: {
    type: Object,
    default: () => ({ sections: [] }),
  },
});

const activeSection = ref('Profile');
const sections = ['Profile', 'Preferences', 'Appearance', 'Notifications', 'Pomodoro', 'Goals', 'Leaderboard', 'Account'];

const darkMode = ref(document.documentElement.classList.contains('dark'));

const toggleDarkMode = () => {
  darkMode.value = !darkMode.value;
  if (darkMode.value) {
    document.documentElement.classList.add('dark');
    localStorage.setItem('theme', 'dark');
  } else {
    document.documentElement.classList.remove('dark');
    localStorage.setItem('theme', 'light');
  }
  if (window.TimeflowToast) {
    window.TimeflowToast.success(darkMode.value ? 'Dark mode enabled' : 'Light mode enabled');
  }
};

const profileForm = ref({
  name: '',
  email: '',
  timezone: '',
  avatar_url: '',
});

const preferencesForm = ref({
  plan_auto_rollover: false,
});

const notificationsForm = ref({
  notifications_enabled: true,
  email_digest_enabled: true,
});

const pomodoroForm = ref({
  pomodoro_work_min: 25,
  pomodoro_break_min: 5,
  pomodoro_long_break_min: 15,
});

const goalsForm = ref({
  daily_goal_hours: 6,
});

const leaderboardForm = ref({
  opt_in: false,
  alias: '',
});

const loadSettings = () => {
  const page = usePage();
  const user = page.props?.auth?.user;
  if (!user) return;

  profileForm.value = {
    name: user.name || '',
    email: user.email || '',
    timezone: user.timezone || 'Asia/Kolkata',
    avatar_url: user.avatar_url || '',
  };
  preferencesForm.value = {
    plan_auto_rollover: Boolean(user.plan_auto_rollover),
  };
  notificationsForm.value = {
    notifications_enabled: Boolean(user.notifications_enabled),
    email_digest_enabled: Boolean(user.email_digest_enabled),
  };
  pomodoroForm.value = {
    pomodoro_work_min: Number(user.pomodoro_work_min || 25),
    pomodoro_break_min: Number(user.pomodoro_break_min || 5),
    pomodoro_long_break_min: 15,
  };
  goalsForm.value = {
    daily_goal_hours: Number(user.daily_goal_hours || 6),
  };
  leaderboardForm.value = {
    opt_in: Boolean(user.leaderboard_opt_in),
    alias: user.leaderboard_alias || '',
  };
};

const saveProfile = async () => {
  try {
    await axios.put('/api/settings/profile', profileForm.value);
    if (window.TimeflowToast) window.TimeflowToast.success('Profile saved');
  } catch (error) {
    if (window.TimeflowToast) window.TimeflowToast.error('Failed to save profile');
    console.warn('Profile update failed', error);
  }
};

const urlBase64ToUint8Array = (base64String) => {
  const padding = '='.repeat((4 - base64String.length % 4) % 4);
  const base64 = (base64String + padding)
    .replace(/\-/g, '+')
    .replace(/_/g, '/');

  const rawData = window.atob(base64);
  const outputArray = new Uint8Array(rawData.length);

  for (let i = 0; i < rawData.length; ++i) {
    outputArray[i] = rawData.charCodeAt(i);
  }
  return outputArray;
};

const savePreferences = async () => {
  try {
    await axios.put('/api/settings/preferences', preferencesForm.value);
    if (window.TimeflowToast) window.TimeflowToast.success('Preferences saved');
  } catch (error) {
    if (window.TimeflowToast) window.TimeflowToast.error('Failed to save preferences');
    console.warn('Preferences update failed', error);
  }
};

const saveNotifications = async () => {
  try {
    await axios.put('/api/settings/notifications', notificationsForm.value);
    
    if (notificationsForm.value.notifications_enabled && 'serviceWorker' in navigator && 'PushManager' in window) {
      const registration = await navigator.serviceWorker.ready;
      let subscription = await registration.pushManager.getSubscription();
      
      if (!subscription && window.__VAPID_PUBLIC_KEY) {
        subscription = await registration.pushManager.subscribe({
          userVisibleOnly: true,
          applicationServerKey: urlBase64ToUint8Array(window.__VAPID_PUBLIC_KEY)
        });
      }

      if (subscription) {
        await axios.post('/api/push-subscriptions', subscription.toJSON());
      }
    } else if (!notificationsForm.value.notifications_enabled && 'serviceWorker' in navigator) {
      const registration = await navigator.serviceWorker.ready;
      const subscription = await registration.pushManager.getSubscription();
      if (subscription) {
        await subscription.unsubscribe();
        await axios.delete('/api/push-subscriptions', { data: { endpoint: subscription.endpoint } });
      }
    }

    if (window.TimeflowToast) window.TimeflowToast.success('Notification preferences saved');
  } catch (error) {
    if (window.TimeflowToast) window.TimeflowToast.error('Failed to save notifications');
    console.warn('Notifications update failed', error);
  }
};

const savePomodoro = async () => {
  try {
    await axios.put('/api/settings/pomodoro', {
      pomodoro_work_min: pomodoroForm.value.pomodoro_work_min,
      pomodoro_break_min: pomodoroForm.value.pomodoro_break_min,
    });
    if (window.TimeflowToast) window.TimeflowToast.success('Pomodoro settings saved');
  } catch (error) {
    if (window.TimeflowToast) window.TimeflowToast.error('Failed to save pomodoro settings');
    console.warn('Pomodoro update failed', error);
  }
};

const saveLeaderboard = async () => {
  try {
    await axios.put('/api/gamification/leaderboard-opt-in', {
      opt_in: leaderboardForm.value.opt_in,
      alias: leaderboardForm.value.alias,
    });
    if (window.TimeflowToast) window.TimeflowToast.success('Leaderboard preferences saved');
  } catch (error) {
    if (window.TimeflowToast) window.TimeflowToast.error('Failed to save leaderboard settings');
    console.warn('Leaderboard update failed', error);
  }
};

const exportData = async () => {
  try {
    const response = await axios.get('/api/settings/export');
    const payload = response.data?.data || {};
    const blob = new Blob([JSON.stringify(payload, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'timeflow-export.json';
    link.click();
    URL.revokeObjectURL(url);
    if (window.TimeflowToast) window.TimeflowToast.success('Data exported');
  } catch (error) {
    if (window.TimeflowToast) window.TimeflowToast.error('Export failed');
    console.warn('Export failed', error);
  }
};

const deleteAccount = async () => {
  try {
    await axios.delete('/api/settings/account');
    window.location.href = '/';
  } catch (error) {
    console.warn('Delete account failed', error);
  }
};

onMounted(() => {
  loadSettings();
});
</script>

<template>
  <div class="settings-page">
    <AppShell :navigation="props.navigation">
      <div class="page-header">
        <div>
          <div class="page-title">Settings</div>
          <div class="page-subtitle">Manage your profile and preferences.</div>
        </div>
      </div>

      <div class="settings-layout">
        <div class="settings-nav">
          <button
            v-for="item in sections"
            :key="item"
            class="settings-link"
            :class="{ active: activeSection === item }"
            type="button"
            @click="activeSection = item"
          >
            {{ item }}
          </button>
        </div>

        <div class="settings-panel">
          <div v-if="activeSection === 'Profile'" class="tf-card">
            <div class="panel-title">Profile</div>
            <div class="profile-row">
              <div class="avatar-lg">{{ profileForm.name ? profileForm.name.slice(0, 2).toUpperCase() : 'TF' }}</div>
              <button class="outline-btn" type="button">Upload</button>
            </div>
            <div class="field">
              <label class="field-label">Name</label>
              <input class="text-input" type="text" data-testid="profile-name" v-model="profileForm.name" />
            </div>
            <div class="field">
              <label class="field-label">Email</label>
              <input class="text-input" type="email" :value="profileForm.email" disabled style="opacity:0.6;cursor:not-allowed" />
            </div>
            <div class="field">
              <label class="field-label">Timezone</label>
              <select class="text-input" data-testid="profile-timezone" v-model="profileForm.timezone">
                <option value="Asia/Kolkata">Asia/Kolkata (IST)</option>
                <option value="UTC">UTC</option>
                <option value="America/New_York">America/New_York (EST)</option>
                <option value="America/Chicago">America/Chicago (CST)</option>
                <option value="America/Denver">America/Denver (MST)</option>
                <option value="America/Los_Angeles">America/Los_Angeles (PST)</option>
                <option value="Europe/London">Europe/London (GMT)</option>
                <option value="Europe/Berlin">Europe/Berlin (CET)</option>
                <option value="Asia/Tokyo">Asia/Tokyo (JST)</option>
                <option value="Australia/Sydney">Australia/Sydney (AEST)</option>
              </select>
            </div>
            <button class="primary-btn" type="button" @click="saveProfile">Save profile</button>
          </div>

          <div v-else-if="activeSection === 'Preferences'" class="tf-card">
            <div class="panel-title">Preferences</div>
            <div class="toggle-row">
              <div>
                <span>Plan Auto Rollover</span>
                <div class="field-hint">Automatically carry over unfinished daily plan tasks to the next day.</div>
              </div>
              <label class="toggle-switch">
                <input type="checkbox" v-model="preferencesForm.plan_auto_rollover" />
                <span class="toggle-slider"></span>
              </label>
            </div>
            <button class="primary-btn" type="button" @click="savePreferences">Save preferences</button>
          </div>

          <div v-else-if="activeSection === 'Appearance'" class="tf-card">
            <div class="panel-title">Appearance</div>
            <div class="toggle-row">
              <div>
                <span>Dark mode</span>
                <div class="field-hint">Switch between light and dark theme.</div>
              </div>
              <label class="toggle-switch">
                <input type="checkbox" :checked="darkMode" @change="toggleDarkMode" />
                <span class="toggle-slider"></span>
              </label>
            </div>
          </div>

          <div v-else-if="activeSection === 'Notifications'" class="tf-card">
            <div class="panel-title">Notifications</div>
            <div class="toggle-row">
              <span>Push notifications</span>
              <input type="checkbox" v-model="notificationsForm.notifications_enabled" />
            </div>
            <div class="toggle-row">
              <span>Email digest</span>
              <input type="checkbox" v-model="notificationsForm.email_digest_enabled" />
            </div>
            <button class="primary-btn" type="button" @click="saveNotifications">Save notifications</button>
          </div>

          <div v-else-if="activeSection === 'Pomodoro'" class="tf-card">
            <div class="panel-title">Pomodoro</div>
            <div class="field">
              <label class="field-label">Work interval</label>
              <input class="text-input" type="range" min="15" max="60" v-model="pomodoroForm.pomodoro_work_min" />
            </div>
            <div class="field">
              <label class="field-label">Short break</label>
              <input class="text-input" type="range" min="3" max="15" v-model="pomodoroForm.pomodoro_break_min" />
            </div>
            <div class="field">
              <label class="field-label">Long break</label>
              <input class="text-input" type="range" min="10" max="30" v-model="pomodoroForm.pomodoro_long_break_min" />
            </div>
            <button class="primary-btn" type="button" @click="savePomodoro">Save pomodoro</button>
          </div>

          <div v-else-if="activeSection === 'Goals'" class="tf-card">
            <div class="panel-title">Goals</div>
            <div class="field">
              <label class="field-label">Daily goal</label>
              <input class="text-input" type="range" min="1" max="12" v-model="goalsForm.daily_goal_hours" />
            </div>
          </div>

          <div v-else-if="activeSection === 'Leaderboard'" class="tf-card">
            <div class="panel-title">Leaderboard</div>
            <div class="toggle-row">
              <span>Show me on leaderboard</span>
              <input type="checkbox" v-model="leaderboardForm.opt_in" />
            </div>
            <div class="field">
              <label class="field-label">Alias</label>
              <input class="text-input" type="text" data-testid="leaderboard-alias" v-model="leaderboardForm.alias" />
            </div>
            <button class="primary-btn" type="button" @click="saveLeaderboard">Save leaderboard</button>
          </div>

          <div v-else class="tf-card">
            <div class="panel-title">Account</div>
            <button class="outline-btn" type="button" @click="exportData">Download all my data</button>
            <button class="danger-btn" type="button" @click="deleteAccount">Delete account</button>
          </div>
        </div>
      </div>
    </AppShell>
  </div>
</template>

<style scoped>
.settings-page {
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

.settings-layout {
  display: grid;
  grid-template-columns: 238px 1fr;
  gap: 15px;
  margin-top: 15px;
}

.settings-nav {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.settings-link {
  padding: 10px 13px;
  border-radius: 10px;
  border: 1px solid transparent;
  background: transparent;
  text-align: left;
  font-size: 15px;
  color: var(--tf-text-secondary);
  cursor: pointer;
}

.settings-link.active {
  background: rgba(124, 92, 252, 0.12);
  border-color: rgba(124, 92, 252, 0.22);
  color: #5b3fd4;
}

.settings-panel {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.panel-title {
  font-size: 16px;
  font-weight: 700;
  margin-bottom: 10px;
}

.profile-row {
  display: flex;
  align-items: center;
  gap: 15px;
  margin-bottom: 15px;
}

.avatar-lg {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--tf-violet), var(--tf-mint));
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 25px;
  font-weight: 800;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 13px;
}

.field-label {
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--tf-text-hint);
}

.text-input {
  width: 100%;
  padding: 10px 13px;
  border-radius: 10px;
  border: 1px solid var(--tf-border-default);
  background: var(--tf-bg-card-alt);
  font-size: 16px;
}

.primary-btn {
  height: 48px;
  padding: 0 18px;
  border-radius: 11px;
  border: none;
  background: var(--tf-violet);
  color: #fff;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  align-self: flex-start;
}

.outline-btn {
  height: 45px;
  padding: 0 15px;
  border-radius: 11px;
  border: 1px solid var(--tf-border-default);
  background: transparent;
  color: var(--tf-text-secondary);
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
}

.danger-btn {
  height: 45px;
  padding: 0 15px;
  border-radius: 11px;
  border: 1px solid rgba(239, 68, 68, 0.4);
  background: transparent;
  color: var(--tf-red);
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  margin-top: 10px;
}

.toggle-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 15px;
  color: var(--tf-text-secondary);
  padding: 8px 0;
}

@media (max-width: 1125px) {
  .settings-layout {
    grid-template-columns: 1fr;
  }
}

.field-hint {
  font-size: 14px;
  color: var(--tf-text-hint);
  margin-top: 3px;
}

.toggle-switch {
  position: relative;
  display: inline-block;
  width: 50px;
  height: 28px;
  flex-shrink: 0;
}

.toggle-switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

.toggle-slider {
  position: absolute;
  cursor: pointer;
  inset: 0;
  background: var(--tf-border-strong);
  border-radius: 28px;
  transition: background 0.3s ease;
}

.toggle-slider::before {
  content: '';
  position: absolute;
  height: 20px;
  width: 20px;
  left: 4px;
  bottom: 4px;
  background: #fff;
  border-radius: 50%;
  transition: transform 0.3s ease;
}

.toggle-switch input:checked + .toggle-slider {
  background: var(--tf-violet);
}

.toggle-switch input:checked + .toggle-slider::before {
  transform: translateX(23px);
}
</style>
