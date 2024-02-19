import {createApp} from "vue";
import App from "./App.vue";
import router from './routers.js'
import WordPressMenuFix from "../utils/WordPressMenuFix.ts";

let el = document.querySelector('#yousaidit_admin_tree_planting');
if (el) {
  const app = createApp(App);
  app.use(router);
  app.mount(el);
}

// fix the admin menu for the slug "stackonet-art-work"
new WordPressMenuFix('tree-planting');