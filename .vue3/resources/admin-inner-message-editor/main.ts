import {createApp} from 'vue';
import App from "./App.vue";

let mainEl = document.querySelector('#order-im-editor');
if (!mainEl) {
  mainEl = document.createElement('div');
  mainEl.id = '#order-im-editor';
  document.body.append(mainEl)
}

if (mainEl) {
  const app = createApp(App);
  app.mount(mainEl);
}