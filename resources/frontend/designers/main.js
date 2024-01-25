import Vue from 'vue';
import router from './routers.js';
import designersStore from './store.js';
import Dashboard from './Dashboard';

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
