import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';
import axios from 'axios';
import Login from '@/Pages/Auth/Login.vue';

vi.mock('axios');

const flushPromises = () => new Promise((resolve) => setTimeout(resolve, 0));

test('Login submits credentials to Fortify', async () => {
  axios.get.mockResolvedValue({ data: {} });
  axios.post.mockResolvedValue({ data: {} });
  const wrapper = mount(Login);

  await wrapper.find('[data-testid="login-email"]').setValue('user@example.com');
  await wrapper.find('[data-testid="login-password"]').setValue('secret');
  await wrapper.find('form').trigger('submit');

  await flushPromises();
  await nextTick();

  expect(axios.get).toHaveBeenCalledWith('/sanctum/csrf-cookie');
  expect(axios.post).toHaveBeenCalledWith('/login', {
    email: 'user@example.com',
    password: 'secret',
  });
});
