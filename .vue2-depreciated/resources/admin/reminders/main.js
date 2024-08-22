import Vue from 'vue';
import App from './App'
import remindersRouter from './routers.js';
import reminderStore from './store.js';
import menuFix from "../utils/admin-menu-fix.js";

let el = document.querySelector('#yousaiditcard_admin_reminders');
if (el) {
    new Vue({
        el: el,
        store: reminderStore(),
        router: remindersRouter(),
        render: h => h(App)
    });
}

// fix the admin menu for the slug "stackonet-art-work"
menuFix('reminders');
