import Vue from 'vue';
import {Dialog} from 'shapla-vue-components';
import innerMessageStore from './store.js';
import InnerMessage from './InnerMessage';

Vue.use(Dialog);

let designerProfilePage = document.querySelector('#inner-message');
if (designerProfilePage) {
	document.querySelector('body').classList.add('has-inner-message');
	new Vue({
		el: designerProfilePage,
		store: innerMessageStore,
		render: h => h(InnerMessage)
	});
}
