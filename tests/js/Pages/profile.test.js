import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';
import axios from 'axios';
import Profile from '@/Pages/Gamification/Profile.vue';

vi.mock('axios');

const flushPromises = () => new Promise((resolve) => setTimeout(resolve, 0));

test('Profile loads gamification stats from API', async () => {
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

  axios.get.mockResolvedValue({ data: { data: profile } });

  const wrapper = mount(Profile, {
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
  expect(wrapper.text()).toContain('12 days');
  expect(wrapper.text()).toContain('7');
});
