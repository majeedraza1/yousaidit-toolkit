import Vue from 'vue';
import axios from 'axios';
import App from './App.vue'
import router from './routers.js';
import stackonetStorage from './store.js';
import wordpress_menu_fix from "../../utils/wordpress_menu_fix";
import {Dialog} from 'shapla-confirm-dialog';

Vue.use(Dialog);

if (window.StackonetToolkit.restRoot) {
	axios.defaults.baseURL = window.StackonetToolkit.restRoot;
}

if (window.StackonetToolkit.restNonce) {
	axios.defaults.headers.common['X-WP-Nonce'] = window.StackonetToolkit.restNonce;
}

let el = document.querySelector('#yousaidit-toolkit-admin');
if (el) {
	new Vue({el, store: stackonetStorage(), router, render: h => h(App)});
}

// fix the admin menu for the slug "yousaidit-toolkit"
wordpress_menu_fix('yousaidit-toolkit');
