import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';
import axios from 'axios';
import ForgotPassword from '@/Pages/Auth/ForgotPassword.vue';

vi.mock('axios');

const flushPromises = () => new Promise((resolve) => setTimeout(resolve, 0));

test('Forgot password sends reset link via Fortify', async () => {
  axios.get.mockResolvedValue({ data: {} });
  axios.post.mockResolvedValue({ data: {} });

  const wrapper = mount(ForgotPassword);

  await wrapper.find('[data-testid="forgot-email"]').setValue('user@example.com');
  await wrapper.find('form').trigger('submit');

  await flushPromises();
  await nextTick();

  expect(axios.get).toHaveBeenCalledWith('/sanctum/csrf-cookie');
  expect(axios.post).toHaveBeenCalledWith('/forgot-password', {
    email: 'user@example.com',
  });
});
