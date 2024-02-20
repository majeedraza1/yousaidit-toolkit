import {createApp} from 'vue';
import App from './App.vue'
import router from './routers.ts';
import WordPressMenuFix from "../utils/WordPressMenuFix.ts";

let el = document.querySelector('#stackonet_toolkit_admin');
if (el) {
  const app = createApp(App);
  app.use(router)
  app.mount(el);
}

// fix the admin menu for the slug "stackonet-art-work"
new WordPressMenuFix('stackonet-art-work');
