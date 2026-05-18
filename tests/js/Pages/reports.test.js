import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';
import axios from 'axios';
import Reports from '@/Pages/Reports.vue';

vi.mock('axios');

const flushPromises = () => new Promise((resolve) => setTimeout(resolve, 0));

test('Reports loads report list from export data', async () => {
  const reports = [
    {
      id: 10,
      title: 'Custom Report',
      date_from: '2026-05-01',
      date_to: '2026-05-10',
      share_token: 'share-token',
      file_path: 'reports/report-10.pdf',
    },
  ];

  axios.get.mockResolvedValue({
    data: {
      data: {
        reports,
      },
    },
  });

  const wrapper = mount(Reports, {
    props: { navigation: { sections: [] } },
    global: {
      stubs: {
        AppShell: { template: '<div><slot /></div>' },
      },
    },
  });

  await flushPromises();
  await nextTick();

  expect(axios.get).toHaveBeenCalledWith('/api/settings/export');
  expect(wrapper.text()).toContain('Custom Report');
});
