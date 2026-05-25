import './bootstrap';
import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { createApp, h } from 'vue';
import TfToast from './Components/TfToast.vue';

createInertiaApp({
	title: (title) => (title ? `${title} - TimeFlow` : 'TimeFlow'),
	resolve: (name) => {
		const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });

		return pages[`./Pages/${name}.vue`];
	},
	setup({ el, App, props, plugin }) {
		const app = createApp({
			render: () => h('div', [h(App, props), h(TfToast)]),
		});
		app.use(plugin);
		app.mount(el);
	},
});

const registerServiceWorker = () => {
	if (!('serviceWorker' in navigator)) {
		return;
	}

	window.addEventListener('load', () => {
		navigator.serviceWorker.register('/sw.js').catch(() => {
			// No-op: service worker is optional in local dev.
		});
	});
};

const setupInstallPrompt = () => {
	let deferredPrompt = null;

	window.addEventListener('beforeinstallprompt', (event) => {
		event.preventDefault();
		deferredPrompt = event;
		window.dispatchEvent(new CustomEvent('pwa-install-available'));
	});

	window.addEventListener('appinstalled', () => {
		deferredPrompt = null;
	});

	window.TimeflowPwa = {
		promptInstall: async () => {
			if (!deferredPrompt) return false;
			deferredPrompt.prompt();
			const choice = await deferredPrompt.userChoice;
			deferredPrompt = null;
			return choice.outcome === 'accepted';
		},
		requestNotifications: async () => {
			if (!('Notification' in window)) return false;
			const permission = await Notification.requestPermission();
			return permission === 'granted';
		},
		setDailyReminderTime: (value) => {
			localStorage.setItem('timeflow.dailyReminderTime', value);
		},
	};
};

const scheduleDailyReminder = () => {
	if (!('Notification' in window) || !('serviceWorker' in navigator)) return;

	setInterval(() => {
		const permission = Notification.permission;
		if (permission !== 'granted') return;

		const reminderTime = localStorage.getItem('timeflow.dailyReminderTime') || '20:00';
		const [reminderHour, reminderMinute] = reminderTime.split(':').map(Number);
		if (Number.isNaN(reminderHour) || Number.isNaN(reminderMinute)) return;

		const now = new Date();
		const todayKey = now.toISOString().slice(0, 10);
		const lastSent = localStorage.getItem('timeflow.dailyReminderSent');

		if (lastSent === todayKey) return;

		const reminderDate = new Date();
		reminderDate.setHours(reminderHour, reminderMinute, 0, 0);

		if (now < reminderDate) return;

		localStorage.setItem('timeflow.dailyReminderSent', todayKey);

		if (navigator.serviceWorker.controller) {
			navigator.serviceWorker.controller.postMessage({
				type: 'show-notification',
				title: 'Daily reminder',
				body: 'Log a session to keep your streak safe.',
			});
		}
	}, 60000);
};

registerServiceWorker();
setupInstallPrompt();
scheduleDailyReminder();
