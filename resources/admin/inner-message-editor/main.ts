import Vue from 'vue';
import App from "./App.vue";

let mainEl = document.querySelector('#order-im-editor');
if (!mainEl) {
  mainEl = document.createElement('div');
  mainEl.id = '#order-im-editor';
  document.body.append(mainEl)
}

if (mainEl) {
  new Vue({
    el: mainEl,
    render: h => h(App)
  })
}