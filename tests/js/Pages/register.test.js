import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';
import axios from 'axios';
import Register from '@/Pages/Auth/Register.vue';

vi.mock('axios');

const flushPromises = () => new Promise((resolve) => setTimeout(resolve, 0));

test('Register submits details to Fortify', async () => {
  axios.get.mockResolvedValue({ data: {} });
  axios.post.mockResolvedValue({ data: {} });

  const wrapper = mount(Register);

  await wrapper.find('[data-testid="register-name"]').setValue('Dana Lee');
  await wrapper.find('[data-testid="register-email"]').setValue('dana@example.com');
  await wrapper.find('[data-testid="register-password"]').setValue('secret');
  await wrapper.find('[data-testid="register-confirm"]').setValue('secret');
  await wrapper.find('form').trigger('submit');

  await flushPromises();
  await nextTick();

  expect(axios.get).toHaveBeenCalledWith('/sanctum/csrf-cookie');
  expect(axios.post).toHaveBeenCalledWith('/register', {
    name: 'Dana Lee',
    email: 'dana@example.com',
    password: 'secret',
    password_confirmation: 'secret',
  });
});
