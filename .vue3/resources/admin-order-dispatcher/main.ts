import {createApp} from 'vue';
import App from './App.vue'
import router from './routers';
import WordPressMenuFix from "../utils/WordPressMenuFix.ts";
import {createPinia} from "pinia";
import './style.scss';

const pinia = createPinia();

let el = document.querySelector('#stackonet_order_dispatcher');
if (el) {
  const app = createApp(App);
  app.use(router)
  app.use(pinia)
  app.mount(el);
}

// fix the admin menu for the slug "stackonet-art-work"
new WordPressMenuFix('order-dispatcher')
