import Vue from 'vue';
import VueRouter from 'vue-router';
import Home from './views/Home';
import Settings from './views/Settings';

Vue.use(VueRouter);

const routes = [
	{path: '/', name: 'Home', component: Home},
	{path: '/settings', name: 'Settings', component: Settings}
];

export default new VueRouter({
	routes // short for `routes: routes`
});
