import Vue from 'vue';
import VueRouter from 'vue-router';
import Report from './pages/Report';
import Cards from './pages/Cards';
import Revenue from './pages/Revenue';
import Payments from './pages/Payments';
import Profile from './pages/Profile';
import Faq from './pages/Faq';
import ContactUs from './pages/ContactUs';

Vue.use(VueRouter);

const routeEndpoints = [
	{path: '/', name: 'Report', component: Report, title: 'Dashboard'},
	{path: '/cards', name: 'Cards', component: Cards, title: 'Cards'},
	{path: '/revenue', name: 'Revenue', component: Revenue, title: 'Revenue'},
	{path: '/payment', name: 'Payments', component: Payments, title: 'Payment Details'},
	{path: '/profile', name: 'Profile', component: Profile, title: 'Profile'},
	{path: '/faq', name: 'Faq', component: Faq, title: 'Faq', hideSidenav: true},
	{path: '/contact', name: 'ContactUs', component: ContactUs, title: 'Contact Us', hideSidenav: true},
];

export {routeEndpoints};
export default new VueRouter({routes: routeEndpoints});
