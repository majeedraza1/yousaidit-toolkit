import Vue from "vue";
import App from './App.vue';
import axios from "@/utils/axios";

const myAccountReminderEl = document.getElementById('yousaiditcard_my_account_reminders');
if (myAccountReminderEl) {
	if (window.StackonetToolkit.restNonce) {
		axios.defaults.headers.common['X-WP-Nonce'] = window.StackonetToolkit.restNonce;
	}
	new Vue({
		el: myAccountReminderEl,
		render: h => h(App)
	})
}
