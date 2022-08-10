import Vue from 'vue';
import VueRouter from 'vue-router';
import Products from './pages/Products';
import ShipStationOrders from './pages/ShipStationOrders';

Vue.use(VueRouter);

const routes = [
	{path: '/', name: 'Products', component: Products},
	{path: '/orders', name: 'Orders', component: ShipStationOrders},
];

export default new VueRouter({
	routes // short for `routes: routes`
});
