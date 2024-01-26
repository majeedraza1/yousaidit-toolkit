import {createApp} from 'vue';
import LoginOrRegistration from './LoginOrRegistration.vue'

let el = document.querySelector('#designer_profile_page_need_login');
if (el) {
  document.querySelector('html').style.overflowY = 'hidden';
  document.querySelector('body').classList.add('designer-profile-page');

  createApp(LoginOrRegistration).mount(el);
}
