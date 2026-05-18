import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';
import axios from 'axios';
import Settings from '@/Pages/Settings.vue';

vi.mock('axios');

const flushPromises = () => new Promise((resolve) => setTimeout(resolve, 0));

test('Settings loads user profile data from API', async () => {
  axios.get.mockResolvedValue({
    data: {
      name: 'Dana Lee',
      timezone: 'UTC',
      pomodoro_work_min: 30,
      pomodoro_break_min: 5,
      notifications_enabled: true,
      email_digest_enabled: false,
      leaderboard_opt_in: true,
      leaderboard_alias: 'Ace',
    },
  });

  const wrapper = mount(Settings, {
    props: { navigation: { sections: [] } },
    global: {
      stubs: {
        AppShell: { template: '<div><slot /></div>' },
      },
    },
  });

  await flushPromises();
  await nextTick();

  expect(axios.get).toHaveBeenCalledWith('/api/user');
  expect(wrapper.find('[data-testid="profile-name"]').element.value).toBe('Dana Lee');

  const leaderboardTab = wrapper.findAll('.settings-link').find((node) => node.text() === 'Leaderboard');
  await leaderboardTab.trigger('click');
  await nextTick();

  expect(wrapper.find('[data-testid="leaderboard-alias"]').element.value).toBe('Ace');
});
