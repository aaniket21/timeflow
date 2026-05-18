import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';
import axios from 'axios';
import ResetPassword from '@/Pages/Auth/ResetPassword.vue';

vi.mock('axios');

const flushPromises = () => new Promise((resolve) => setTimeout(resolve, 0));

test('Reset password submits token and new password', async () => {
  axios.get.mockResolvedValue({ data: {} });
  axios.post.mockResolvedValue({ data: {} });

  window.history.pushState({}, '', '/reset-password?token=abc123&email=user@example.com');

  const wrapper = mount(ResetPassword);

  await wrapper.find('[data-testid="reset-password"]').setValue('secret');
  await wrapper.find('[data-testid="reset-confirm"]').setValue('secret');
  await wrapper.find('form').trigger('submit');

  await flushPromises();
  await nextTick();

  expect(axios.get).toHaveBeenCalledWith('/sanctum/csrf-cookie');
  expect(axios.post).toHaveBeenCalledWith('/reset-password', {
    email: 'user@example.com',
    token: 'abc123',
    password: 'secret',
    password_confirmation: 'secret',
  });
});
