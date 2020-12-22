import Vue from 'vue';
import axios from 'axios'
import App from './App'
import router from './routers.js';
import orderDispatcherStore from './store.js';
import menuFix from "../utils/admin-menu-fix.js";
import Dialog from 'shapla-confirm-dialog'

Vue.use(Dialog);

if (window.Stackonet.nonce) {
	axios.defaults.headers.common['X-WP-Nonce'] = window.Stackonet.nonce;
}

let el = document.querySelector('#stackonet_order_dispatcher');
if (el) {
	new Vue({
		el: el,
		store: orderDispatcherStore(),
		router: router,
		render: h => h(App)
	});
}

// fix the admin menu for the slug "stackonet-art-work"
menuFix('order-dispatcher');
