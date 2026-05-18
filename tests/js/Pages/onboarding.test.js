import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';
import Onboarding from '@/Pages/Onboarding.vue';

const flushPromises = () => new Promise((resolve) => setTimeout(resolve, 0));

test('Onboarding captures selections and stores progress', async () => {
  const setItemSpy = vi.spyOn(Storage.prototype, 'setItem');

  const wrapper = mount(Onboarding);

  await wrapper.find('[data-testid="role-student"]').trigger('click');
  await wrapper.find('[data-testid="onboarding-next"]').trigger('click');

  await wrapper.find('[data-testid="goal-slider"]').setValue('7');
  await wrapper.find('[data-testid="onboarding-next"]').trigger('click');

  await wrapper.find('[data-testid="first-item"]').setValue('Deep work');
  await wrapper.find('[data-testid="onboarding-finish"]').trigger('click');

  await flushPromises();
  await nextTick();

  expect(setItemSpy).toHaveBeenCalled();
});
