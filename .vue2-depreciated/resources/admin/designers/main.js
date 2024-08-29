import Vue from 'vue';
import App from './App'
import router from './routers.js';
import menuFix from "../utils/admin-menu-fix.js";

let el = document.querySelector('#yousaiditcard_admin_designer');
if (el) {
	new Vue({
		el: el,
		router: router,
		render: h => h(App)
	});
}

// fix the admin menu for the slug "stackonet-art-work"
menuFix('designers');