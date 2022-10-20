import Vue from 'vue';
import axios from 'axios'
import App from './App'
import remindersRouter from './routers.js';
import reminderStore from './store.js';
import menuFix from "../utils/admin-menu-fix.js";
import {Dialog} from 'shapla-vue-components'

Vue.use(Dialog);

jQuery.ajaxSetup({
	beforeSend: function (xhr) {
		xhr.setRequestHeader('X-WP-Nonce', window.Stackonet.nonce);
	}
});

axios.defaults.headers.common['X-WP-Nonce'] = window.Stackonet.nonce;

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
