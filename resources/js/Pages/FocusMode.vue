<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';

const WORK_MINUTES = 25;
const BREAK_MINUTES = 5;
const SNOOZE_MINUTES = 5;
const LONG_SESSION_SECONDS = 2 * 60 * 60;
const STORAGE_KEY = 'timeflow.focusTimer';

const mode = ref('work');
const remainingSeconds = ref(WORK_MINUTES * 60);
const isRunning = ref(false);
const snoozeUsed = ref(false);
const cycles = ref(0);
const lastTick = ref(Date.now());
const longSessionNotified = ref(false);
const sessionStart = ref(null);
const offline = ref(!navigator.onLine);
const inFullscreen = ref(false);
const canInstall = ref(false);

let intervalId = null;

const formattedTime = computed(() => {
  const total = Math.max(0, remainingSeconds.value);
  const minutes = String(Math.floor(total / 60)).padStart(2, '0');
  const seconds = String(total % 60).padStart(2, '0');
  return `${minutes}:${seconds}`;
});

const statusLabel = computed(() => {
  return mode.value === 'work' ? 'Deep focus' : 'Mandatory break';
});

const backgroundLabel = computed(() => {
  return mode.value === 'work' ? 'Focus' : 'Recover';
});

const saveState = () => {
  const payload = {
    mode: mode.value,
    remainingSeconds: remainingSeconds.value,
    isRunning: isRunning.value,
    snoozeUsed: snoozeUsed.value,
    cycles: cycles.value,
    lastTick: Date.now(),
    sessionStart: sessionStart.value,
    longSessionNotified: longSessionNotified.value,
  };

  localStorage.setItem(STORAGE_KEY, JSON.stringify(payload));
};

const restoreState = () => {
  const raw = localStorage.getItem(STORAGE_KEY);
  if (!raw) return;

  try {
    const payload = JSON.parse(raw);
    mode.value = payload.mode || 'work';
    remainingSeconds.value = Number(payload.remainingSeconds ?? WORK_MINUTES * 60);
    isRunning.value = Boolean(payload.isRunning);
    snoozeUsed.value = Boolean(payload.snoozeUsed);
    cycles.value = Number(payload.cycles ?? 0);
    lastTick.value = Number(payload.lastTick ?? Date.now());
    sessionStart.value = payload.sessionStart ?? null;
    longSessionNotified.value = Boolean(payload.longSessionNotified);
  } catch (error) {
    localStorage.removeItem(STORAGE_KEY);
  }
};

const updateTick = () => {
  if (!isRunning.value) return;

  const now = Date.now();
  const deltaSeconds = Math.floor((now - lastTick.value) / 1000);

  if (deltaSeconds <= 0) return;

  remainingSeconds.value = Math.max(0, remainingSeconds.value - deltaSeconds);
  lastTick.value = now;

  if (mode.value === 'work' && sessionStart.value) {
    const elapsed = Math.floor((now - sessionStart.value) / 1000);
    if (elapsed >= LONG_SESSION_SECONDS && !longSessionNotified.value) {
      longSessionNotified.value = true;
      notifyUser('Take a breather', 'You have been in focus for 2 hours.');
    }
  }

  if (remainingSeconds.value === 0) {
    transitionMode();
  }

  saveState();
};

const startTimer = () => {
  if (!isRunning.value) {
    isRunning.value = true;
    lastTick.value = Date.now();

    if (mode.value === 'work' && !sessionStart.value) {
      sessionStart.value = lastTick.value;
    }

    saveState();
  }
};

const pauseTimer = () => {
  if (isRunning.value) {
    isRunning.value = false;
    saveState();
  }
};

const resetTimer = () => {
  isRunning.value = false;
  mode.value = 'work';
  remainingSeconds.value = WORK_MINUTES * 60;
  snoozeUsed.value = false;
  cycles.value = 0;
  sessionStart.value = null;
  longSessionNotified.value = false;
  saveState();
};

const toggleTimer = () => {
  if (isRunning.value) {
    pauseTimer();
    return;
  }

  startTimer();
};

const transitionMode = () => {
  if (mode.value === 'work') {
    mode.value = 'break';
    remainingSeconds.value = BREAK_MINUTES * 60;
    snoozeUsed.value = false;
    cycles.value += 1;
  } else {
    mode.value = 'work';
    remainingSeconds.value = WORK_MINUTES * 60;
    sessionStart.value = Date.now();
    longSessionNotified.value = false;
  }

  isRunning.value = true;
  lastTick.value = Date.now();
  saveState();

  notifyUser(
    mode.value === 'break' ? 'Break time' : 'Focus resumed',
    mode.value === 'break' ? 'Stand up and reset.' : 'Back to deep work.'
  );
};

const snoozeBreak = () => {
  if (mode.value !== 'break' || snoozeUsed.value) return;

  remainingSeconds.value += SNOOZE_MINUTES * 60;
  snoozeUsed.value = true;
  saveState();
};

const notifyUser = (title, body) => {
  if (!('serviceWorker' in navigator)) return;
  if (Notification.permission !== 'granted') return;

  if (navigator.serviceWorker.controller) {
    navigator.serviceWorker.controller.postMessage({
      type: 'show-notification',
      title,
      body,
    });
  } else {
    new Notification(title, { body });
  }
};

const enterFullscreen = async () => {
  if (!document.fullscreenElement && document.documentElement.requestFullscreen) {
    await document.documentElement.requestFullscreen();
  }
};

const exitFocus = async () => {
  if (document.fullscreenElement && document.exitFullscreen) {
    await document.exitFullscreen();
  }

  window.history.length > 1 ? window.history.back() : window.location.assign('/');
};

const toggleFullscreen = () => {
  if (document.fullscreenElement) {
    document.exitFullscreen?.();
  } else {
    enterFullscreen();
  }
};

const onKeyDown = (event) => {
  if (event.code === 'Space') {
    event.preventDefault();
    toggleTimer();
  }

  if (event.code === 'Escape') {
    event.preventDefault();
    exitFocus();
  }

  if (event.key.toLowerCase() === 'f') {
    event.preventDefault();
    toggleFullscreen();
  }
};

const onInstallAvailable = () => {
  canInstall.value = true;
};

const installApp = async () => {
  if (!window.TimeflowPwa?.promptInstall) return;
  const accepted = await window.TimeflowPwa.promptInstall();
  if (accepted) {
    canInstall.value = false;
  }
};

const requestNotifications = async () => {
  if (!window.TimeflowPwa?.requestNotifications) return;
  await window.TimeflowPwa.requestNotifications();
};

const handleStorage = (event) => {
  if (event.key === STORAGE_KEY) {
    restoreState();
  }
};

const handleOnline = () => {
  offline.value = false;
};

const handleOffline = () => {
  offline.value = true;
};

const handleFullscreenChange = () => {
  inFullscreen.value = Boolean(document.fullscreenElement);
};

onMounted(() => {
  restoreState();

  intervalId = window.setInterval(updateTick, 1000);
  window.addEventListener('keydown', onKeyDown);
  window.addEventListener('storage', handleStorage);
  window.addEventListener('online', handleOnline);
  window.addEventListener('offline', handleOffline);
  window.addEventListener('pwa-install-available', onInstallAvailable);
  document.addEventListener('fullscreenchange', handleFullscreenChange);

  if (document.fullscreenElement) {
    inFullscreen.value = true;
  }
});

onUnmounted(() => {
  if (intervalId) {
    window.clearInterval(intervalId);
  }

  window.removeEventListener('keydown', onKeyDown);
  window.removeEventListener('storage', handleStorage);
  window.removeEventListener('online', handleOnline);
  window.removeEventListener('offline', handleOffline);
  window.removeEventListener('pwa-install-available', onInstallAvailable);
  document.removeEventListener('fullscreenchange', handleFullscreenChange);
});
</script>

<template>
  <div class="focus-shell" :data-mode="mode">
    <header class="focus-top">
      <div class="status">
        <span class="signal" :class="offline ? 'offline' : 'online'"></span>
        <span class="label">{{ offline ? 'Offline' : 'Online' }}</span>
      </div>
      <div class="headline">
        <div class="tag">{{ statusLabel }}</div>
        <div class="subtitle">{{ backgroundLabel }} cycle {{ cycles + 1 }}</div>
      </div>
      <div class="controls">
        <button class="ghost" @click="enterFullscreen">Fullscreen</button>
        <button class="ghost" @click="requestNotifications">Enable alerts</button>
        <button class="ghost" v-if="canInstall" @click="installApp">Install app</button>
      </div>
    </header>

    <main class="focus-stage">
      <div class="orb">
        <div class="orb-inner">
          <span class="timer">{{ formattedTime }}</span>
          <span class="state">{{ mode === 'work' ? 'Focus session' : 'Recovery' }}</span>
        </div>
      </div>

      <div class="action-row">
        <button class="primary" @click="toggleTimer">{{ isRunning ? 'Pause' : 'Start' }}</button>
        <button class="ghost" @click="resetTimer">Reset</button>
        <button class="ghost" @click="exitFocus">Exit</button>
      </div>

      <div class="hint-row">
        <span>Space: start or pause</span>
        <span>F: fullscreen</span>
        <span>Esc: exit</span>
      </div>
    </main>

    <section class="break-overlay" v-if="mode === 'break'">
      <div class="overlay-card">
        <div class="overlay-title">Break enforced</div>
        <div class="overlay-body">Reset your focus before the next session.</div>
        <button class="ghost" :disabled="snoozeUsed" @click="snoozeBreak">
          {{ snoozeUsed ? 'Snooze used' : 'Snooze 5 min' }}
        </button>
      </div>
    </section>
  </div>
</template>

<style scoped>
.focus-shell {
  min-height: 100vh;
  padding: 35px 30px 50px;
  display: flex;
  flex-direction: column;
  gap: 40px;
  font-family: 'Plus Jakarta Sans', sans-serif;
  color: #f8fafc;
  background: radial-gradient(circle at 20% 10%, rgba(124, 92, 252, 0.2), transparent 45%),
    radial-gradient(circle at 80% 0%, rgba(14, 207, 164, 0.18), transparent 50%),
    #07070c;
}

.focus-shell[data-mode="break"] {
  background: radial-gradient(circle at 20% 10%, rgba(245, 166, 35, 0.2), transparent 45%),
    radial-gradient(circle at 80% 0%, rgba(239, 68, 68, 0.2), transparent 50%),
    #09070b;
}

.focus-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  flex-wrap: wrap;
}

.status {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 15px;
  letter-spacing: 0.2em;
  text-transform: uppercase;
  color: rgba(248, 250, 252, 0.6);
}

.signal {
  width: 10px;
  height: 10px;
  border-radius: 1249px;
  background: #0ecfa4;
  box-shadow: 0 0 10px rgba(14, 207, 164, 0.8);
}

.signal.offline {
  background: #ef4444;
  box-shadow: 0 0 10px rgba(239, 68, 68, 0.8);
}

.headline {
  text-align: center;
  min-width: 250px;
}

.tag {
  font-size: 15px;
  letter-spacing: 0.3em;
  text-transform: uppercase;
  color: rgba(248, 250, 252, 0.6);
}

.subtitle {
  margin-top: 10px;
  font-size: 23px;
  font-weight: 700;
}

.controls {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.focus-stage {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 30px;
}

.orb {
  width: min(525px, 90vw);
  height: min(525px, 90vw);
  border-radius: 50%;
  background: radial-gradient(circle at 30% 30%, rgba(255, 255, 255, 0.12), transparent 60%),
    radial-gradient(circle at 70% 80%, rgba(124, 92, 252, 0.2), transparent 55%),
    rgba(8, 8, 14, 0.9);
  border: 1px solid rgba(124, 92, 252, 0.3);
  display: grid;
  place-items: center;
  position: relative;
  box-shadow: 0 38px 88px rgba(15, 23, 42, 0.55);
}

.focus-shell[data-mode="break"] .orb {
  border-color: rgba(245, 166, 35, 0.4);
}

.orb::after {
  content: '';
  position: absolute;
  inset: 23px;
  border-radius: 50%;
  border: 1px dashed rgba(248, 250, 252, 0.2);
  animation: spin 16s linear infinite;
}

.orb-inner {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 13px;
  text-align: center;
  z-index: 1;
}

.timer {
  font-family: 'JetBrains Mono', monospace;
  font-size: clamp(60px, 7vw, 120px);
  letter-spacing: 0.1em;
}

.state {
  font-size: 18px;
  text-transform: uppercase;
  letter-spacing: 0.3em;
  color: rgba(248, 250, 252, 0.6);
}

.action-row {
  display: flex;
  gap: 15px;
  flex-wrap: wrap;
  justify-content: center;
}

.primary,
.ghost {
  border: none;
  padding: 13px 23px;
  border-radius: 1249px;
  font-weight: 600;
  font-size: 18px;
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}

.primary {
  background: linear-gradient(90deg, #7c5cfc, #0ecfa4);
  color: #071014;
  box-shadow: 0 15px 38px rgba(14, 207, 164, 0.3);
}

.primary:hover {
  transform: translateY(-1px);
}

.ghost {
  background: rgba(248, 250, 252, 0.08);
  color: rgba(248, 250, 252, 0.8);
  border: 1px solid rgba(248, 250, 252, 0.15);
}

.ghost:disabled {
  opacity: 0.5;
  cursor: default;
}

.hint-row {
  display: flex;
  gap: 20px;
  flex-wrap: wrap;
  justify-content: center;
  font-size: 15px;
  color: rgba(248, 250, 252, 0.5);
  text-transform: uppercase;
  letter-spacing: 0.2em;
}

.break-overlay {
  position: fixed;
  inset: 0;
  background: rgba(4, 4, 10, 0.82);
  display: grid;
  place-items: center;
  backdrop-filter: blur(8px);
}

.overlay-card {
  padding: 30px 35px;
  border-radius: 23px;
  background: rgba(12, 10, 20, 0.9);
  border: 1px solid rgba(245, 166, 35, 0.4);
  text-align: center;
  box-shadow: 0 28px 63px rgba(0, 0, 0, 0.6);
}

.overlay-title {
  font-size: 23px;
  font-weight: 700;
  margin-bottom: 10px;
}

.overlay-body {
  font-size: 18px;
  color: rgba(248, 250, 252, 0.7);
  margin-bottom: 20px;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>
