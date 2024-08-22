import Vue from 'vue';
import InnerMessage from './InnerMessage';

let designerProfilePage = document.querySelector('#inner-message');
if (designerProfilePage) {
	document.querySelector('body').classList.add('has-inner-message');
	new Vue({
		el: designerProfilePage,
		render: h => h(InnerMessage)
	});
}
