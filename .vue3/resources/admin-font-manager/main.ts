import {createApp} from "vue";
import App from "./App.vue";
import router from "./routers.js";
import WordPressMenuFix from "../utils/WordPressMenuFix.ts";

let el = document.querySelector('#yousaidit_card_admin_font_manager');
if (el) {
  const app = createApp(App);
  app.use(router)
  app.mount(el);
}

// fix the admin menu for the slug "stackonet-art-work"
new WordPressMenuFix('font-manager');