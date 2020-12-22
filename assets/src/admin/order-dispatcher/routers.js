import Vue from 'vue';
import VueRouter from 'vue-router';
import Dispatcher from './pages/Dispatcher';
import PrintCards from './pages/PrintCards';
import DispatchOrders from './pages/DispatchOrders';
import CompleteOrders from './pages/CompleteOrders';

Vue.use(VueRouter);

const routes = [
	{path: '/', name: 'Dispatcher', component: Dispatcher},
	{path: '/print-cards', name: 'PrintCards', component: PrintCards},
	{path: '/dispatch-orders', name: 'DispatchOrders', component: DispatchOrders},
	{path: '/complete-orders', name: 'CompleteOrders', component: CompleteOrders},
];

export default new VueRouter({routes: routes});
