import Vue from 'vue';
import LoginOrRegistration from './LoginOrRegistration'
import {Dialog} from 'shapla-confirm-dialog';
import designersAuth from './store.js';

Vue.config.productionTip = false;

Vue.use(Dialog);

let el = document.querySelector('#designer_profile_page_need_login');
if (el) {
	document.querySelector('html').style.overflowY = 'hidden';
	document.querySelector('body').classList.add('designer-profile-page');

	new Vue({
		el: el,
		store: designersAuth,
		render: h => h(LoginOrRegistration)
	});
}
