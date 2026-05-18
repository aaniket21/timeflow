import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';
import axios from 'axios';
import Achievements from '@/Pages/Achievements.vue';

vi.mock('axios');

const flushPromises = () => new Promise((resolve) => setTimeout(resolve, 0));

test('Achievements loads profile stats and badges', async () => {
  const profile = {
    xp_total: 1550,
    level: 5,
    next_level_xp: 3000,
    level_progress: 0.35,
    streak_current: 12,
    streak_longest: 20,
    badge_count: 7,
    last_active_date: '2026-05-18',
  };

  const badges = [
    { id: 1, name: 'First Flame', icon: 'FF', earned: true, category: 'consistency' },
    { id: 2, name: 'Tomato Head', icon: 'TH', earned: false, category: 'focus' },
  ];

  axios.get.mockImplementation((url) => {
    if (url === '/api/gamification/profile') {
      return Promise.resolve({ data: { data: profile } });
    }
    if (url === '/api/gamification/badges') {
      return Promise.resolve({ data: { data: badges } });
    }
    return Promise.resolve({ data: {} });
  });

  const wrapper = mount(Achievements, {
    props: { navigation: { sections: [] } },
    global: {
      stubs: {
        AppShell: { template: '<div><slot /></div>' },
      },
    },
  });

  await flushPromises();
  await nextTick();

  expect(axios.get).toHaveBeenCalledWith('/api/gamification/profile');
  expect(axios.get).toHaveBeenCalledWith('/api/gamification/badges');
  expect(wrapper.text()).toContain('Badges earned');
  expect(wrapper.text()).toContain('7');
});
