import Vue from 'vue';
import axios from 'axios';
import {Dialog} from 'shapla-vue-components';
import router from './routers.js';
import designersStore from './store.js';
import Dashboard from './Dashboard';

Vue.use(Dialog);

if (window.DesignerProfile && window.DesignerProfile.restNonce) {
	axios.defaults.headers.common['X-WP-Nonce'] = window.DesignerProfile.restNonce;
}

let designerProfilePage = document.querySelector('#designer_profile_page');
if (designerProfilePage) {
	document.querySelector('body').classList.add('designer-profile-page');
	new Vue({
		el: designerProfilePage,
		router: router,
		store: designersStore,
		render: h => h(Dashboard)
	});
}
