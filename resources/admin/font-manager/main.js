import Vue from "vue";
import App from "./App.vue";
import menuFix from '../utils/admin-menu-fix.js';

let el = document.querySelector('#yousaidit_card_admin_font_manager');
if (el) {
    new Vue({
        el: el,
        render: h => h(App)
    });
}

// fix the admin menu for the slug "stackonet-art-work"
menuFix('font-manager');