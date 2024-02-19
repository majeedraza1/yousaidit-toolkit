import {createApp} from 'vue';
import App from './App.vue'
import WordPressMenuFix from '../utils/WordPressMenuFix.ts';
import router from "./routers.ts";

import {createPinia} from "pinia";

const pinia = createPinia()

let el = document.querySelector('#yousaiditcard_admin_reminders');
if (el) {
  const app = createApp(App);
  app.use(pinia)
  app.use(router)
  app.mount(el)

  // fix the admin menu for the slug "stackonet-art-work"
  new WordPressMenuFix('reminders');
}
