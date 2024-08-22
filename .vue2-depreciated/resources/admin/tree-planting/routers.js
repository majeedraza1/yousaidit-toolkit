import Vue from 'vue';
import VueRouter from 'vue-router';
import Purchases from './pages/Purchases.vue';
import InQueue from './pages/InQueue.vue';

Vue.use(VueRouter);

const routes = [
	{path: '/', name: 'Purchases', component: Purchases},
	{path: '/queue', name: 'InQueue', component: InQueue},
];

const treePlantingRouter = () => {
	return new VueRouter({
		routes: routes
	});
}

export default treePlantingRouter;
