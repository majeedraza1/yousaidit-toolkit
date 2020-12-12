import Vue from 'vue';
import axios from 'axios';
import App from './App.vue'
import router from './routers.js';
import stackonetFrontendStorage from './store.js';
import {Dialog} from 'shapla-confirm-dialog';

Vue.use(Dialog);

if (window.StackonetToolkit.restRoot) {
	axios.defaults.baseURL = window.StackonetToolkit.restRoot;
}

if (window.StackonetToolkit.restNonce) {
	axios.defaults.headers.common['X-WP-Nonce'] = window.StackonetToolkit.restNonce;
}

let el = document.querySelector('#yousaidit-toolkit-frontend');
if (el) {
	new Vue({
		el,
		store: stackonetFrontendStorage(),
		router,
		render: h => h(App)
	});
}
