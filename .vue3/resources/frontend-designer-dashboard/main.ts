import {createApp} from 'vue';
import router from './routers.ts';
import Dashboard from './Dashboard.vue';
import LoginOrRegistration from './login/LoginOrRegistration.vue'
import {createPinia} from 'pinia'
import './style.scss'

const pinia = createPinia()

let designerProfilePage = document.querySelector('#designer_profile_page');
if (designerProfilePage) {
  document.querySelector('body').classList.add('designer-profile-page');
  const app = createApp(Dashboard);
  app.use(pinia)
  app.use(router)
  app.mount(designerProfilePage);
}


let el = document.querySelector('#designer_profile_page_need_login');
if (el) {
  document.querySelector('html').style.overflowY = 'hidden';
  document.querySelector('body').classList.add('designer-profile-page');

  createApp(LoginOrRegistration).mount(el);
}
