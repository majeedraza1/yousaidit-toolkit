import Vue from 'vue';
import HowItWorks from './HowItWorks';

let howItWorks = document.querySelector('#you_said_it_how_it_works');
if (howItWorks) {
	new Vue({el: howItWorks, render: h => h(HowItWorks)});
}
