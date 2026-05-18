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

const activeSection = ref('Profile');
const sections = ['Profile', 'Notifications', 'Pomodoro', 'Goals', 'Leaderboard', 'Account'];

const profileForm = ref({
  name: '',
  timezone: '',
  avatar_url: '',
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

const loadSettings = async () => {
  try {
    const response = await axios.get('/api/user');
    const data = response.data || {};
    profileForm.value = {
      name: data.name || '',
      timezone: data.timezone || '',
      avatar_url: data.avatar_url || '',
    };
    notificationsForm.value = {
      notifications_enabled: Boolean(data.notifications_enabled),
      email_digest_enabled: Boolean(data.email_digest_enabled),
    };
    pomodoroForm.value = {
      pomodoro_work_min: Number(data.pomodoro_work_min || 25),
      pomodoro_break_min: Number(data.pomodoro_break_min || 5),
      pomodoro_long_break_min: Number(data.pomodoro_long_break_min || 15),
    };
    goalsForm.value = {
      daily_goal_hours: Number(data.daily_goal_hours || 6),
    };
    leaderboardForm.value = {
      opt_in: Boolean(data.leaderboard_opt_in),
      alias: data.leaderboard_alias || '',
    };
  } catch (error) {
    console.warn('Settings load failed', error);
  }
};

const saveProfile = async () => {
  try {
    await axios.put('/api/settings/profile', profileForm.value);
  } catch (error) {
    console.warn('Profile update failed', error);
  }
};

const saveNotifications = async () => {
  try {
    await axios.put('/api/settings/notifications', notificationsForm.value);
  } catch (error) {
    console.warn('Notifications update failed', error);
  }
};

const savePomodoro = async () => {
  try {
    await axios.put('/api/settings/pomodoro', {
      pomodoro_work_min: pomodoroForm.value.pomodoro_work_min,
      pomodoro_break_min: pomodoroForm.value.pomodoro_break_min,
    });
  } catch (error) {
    console.warn('Pomodoro update failed', error);
  }
};

const saveLeaderboard = async () => {
  try {
    await axios.put('/api/gamification/leaderboard-opt-in', {
      opt_in: leaderboardForm.value.opt_in,
      alias: leaderboardForm.value.alias,
    });
  } catch (error) {
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
  } catch (error) {
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
              <label class="field-label">Timezone</label>
              <select class="text-input" data-testid="profile-timezone" v-model="profileForm.timezone">
                <option :value="profileForm.timezone || 'UTC'">{{ profileForm.timezone || 'UTC' }}</option>
              </select>
            </div>
            <button class="primary-btn" type="button" @click="saveProfile">Save profile</button>
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

<style>
.settings-page {
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

.settings-layout {
  display: grid;
  grid-template-columns: 190px 1fr;
  gap: 12px;
  margin-top: 12px;
}

.settings-nav {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.settings-link {
  padding: 8px 10px;
  border-radius: 8px;
  border: 1px solid transparent;
  background: transparent;
  text-align: left;
  font-size: 12px;
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
  gap: 12px;
}

.panel-title {
  font-size: 13px;
  font-weight: 700;
  margin-bottom: 8px;
}

.profile-row {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
}

.avatar-lg {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--tf-violet), var(--tf-mint));
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 20px;
  font-weight: 800;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin-bottom: 10px;
}

.field-label {
  font-size: 10px;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--tf-text-hint);
}

.text-input {
  width: 100%;
  padding: 8px 10px;
  border-radius: 8px;
  border: 1px solid var(--tf-border-default);
  background: var(--tf-bg-card-alt);
  font-size: 13px;
}

.primary-btn {
  height: 38px;
  padding: 0 14px;
  border-radius: 9px;
  border: none;
  background: var(--tf-violet);
  color: #fff;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  align-self: flex-start;
}

.outline-btn {
  height: 36px;
  padding: 0 12px;
  border-radius: 9px;
  border: 1px solid var(--tf-border-default);
  background: transparent;
  color: var(--tf-text-secondary);
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
}

.danger-btn {
  height: 36px;
  padding: 0 12px;
  border-radius: 9px;
  border: 1px solid rgba(239, 68, 68, 0.4);
  background: transparent;
  color: var(--tf-red);
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  margin-top: 8px;
}

.toggle-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 12px;
  color: var(--tf-text-secondary);
  padding: 6px 0;
}

@media (max-width: 900px) {
  .settings-layout {
    grid-template-columns: 1fr;
  }
}
</style>
