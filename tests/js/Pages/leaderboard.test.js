import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';
import axios from 'axios';
import Leaderboard from '@/Pages/Leaderboard.vue';

vi.mock('axios');

const flushPromises = () => new Promise((resolve) => setTimeout(resolve, 0));

test('Leaderboard shows weekly leaders from API', async () => {
  const profile = {
    xp_total: 1550,
    level: 6,
    next_level_xp: 6000,
    level_progress: 0.2,
    streak_current: 12,
    streak_longest: 20,
    badge_count: 7,
    last_active_date: '2026-05-18',
  };

  const leaders = [
    { rank: 1, display_name: 'Alpha', xp: 420 },
    { rank: 2, display_name: 'Bravo', xp: 360 },
  ];

  axios.get.mockImplementation((url) => {
    if (url === '/api/gamification/profile') {
      return Promise.resolve({ data: { data: profile } });
    }
    if (url === '/api/gamification/leaderboard') {
      return Promise.resolve({ data: { data: leaders } });
    }
    return Promise.resolve({ data: {} });
  });

  const wrapper = mount(Leaderboard, {
    props: { navigation: { sections: [] } },
    global: {
      stubs: {
        AppShell: { template: '<div><slot /></div>' },
      },
    },
  });

  await flushPromises();
  await nextTick();

  expect(axios.get).toHaveBeenCalledWith('/api/gamification/leaderboard');
  expect(wrapper.text()).toContain('Alpha');
  expect(wrapper.text()).toContain('420 XP');
});
