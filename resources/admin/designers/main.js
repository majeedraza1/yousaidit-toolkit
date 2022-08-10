import Vue from 'vue';
import axios from 'axios'
import App from './App'
import router from './routers.js';
import designerStore from './store.js';
import menuFix from "../utils/admin-menu-fix.js";
import {Dialog} from 'shapla-vue-components'

Vue.use(Dialog);

axios.defaults.headers.common['X-WP-Nonce'] = window.Stackonet.nonce;

let el = document.querySelector('#yousaiditcard_admin_designer');
if (el) {
	new Vue({
		el: el,
		store: designerStore(),
		router: router,
		render: h => h(App)
	});
}

// fix the admin menu for the slug "stackonet-art-work"
menuFix('designers');
