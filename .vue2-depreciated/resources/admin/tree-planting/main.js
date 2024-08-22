import Vue from "vue";
import App from "./App.vue";
import treePlantingRouter from './routers.js'
import menuFix from '../utils/admin-menu-fix.js';

let el = document.querySelector('#yousaidit_admin_tree_planting');
if (el) {
  new Vue({
    el: el,
    router: treePlantingRouter(),
    render: h => h(App)
  });
}

// fix the admin menu for the slug "stackonet-art-work"
menuFix('tree-planting');