import {createApp} from 'vue';
import App from './App.vue'
import router from './routers.ts';
import WordPressMenuFix from "../utils/WordPressMenuFix.ts";

import {createPinia} from "pinia";

const pinia = createPinia()

let el = document.querySelector('#stackonet_toolkit_admin');
if (el) {
  const app = createApp(App);
  app.use(router)
  app.use(pinia)
  app.mount(el);
}

// fix the admin menu for the slug "stackonet-art-work"
new WordPressMenuFix('stackonet-art-work');
