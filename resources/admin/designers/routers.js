import Vue from 'vue';
import VueRouter from 'vue-router';
import Cards from './pages/Cards';
import Card from './pages/Card';
import Designers from './pages/Designers';
import Designer from './pages/Designer';
import Settings from './pages/Settings';
import Commissions from './pages/Commissions';
import PayPalPayouts from './pages/PayPalPayouts';
import PayPalPayout from './pages/PayPalPayout';

Vue.use(VueRouter);

const routes = [
	{path: '/', name: 'Designers', component: Designers},
	{path: '/designers/:id', name: 'Designer', component: Designer},
	{path: '/cards', name: 'Cards', component: Cards},
	{path: '/cards/:id', name: 'Card', component: Card},
	{path: '/settings', name: 'Settings', component: Settings},
	{path: '/commissions', name: 'Commissions', component: Commissions},
	{path: '/payouts', name: 'Payouts', component: PayPalPayouts},
	{path: '/payouts/:id', name: 'Payout', component: PayPalPayout},
];

export default new VueRouter({routes: routes});
