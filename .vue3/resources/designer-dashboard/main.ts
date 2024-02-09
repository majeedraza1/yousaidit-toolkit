import {createApp} from 'vue';
import router from './routers.ts';
import Dashboard from './Dashboard.vue';
import {createPinia} from 'pinia'
import './style.scss'
import '../login/main.ts';

const pinia = createPinia()

let designerProfilePage = document.querySelector('#designer_profile_page');
if (designerProfilePage) {
  document.querySelector('body').classList.add('designer-profile-page');
  const app = createApp(Dashboard);
  app.use(pinia)
  app.use(router)
  app.mount(designerProfilePage);
}
