import {createRouter, createWebHashHistory} from 'vue-router';
import Report from './pages/Report.vue';
import Revenue from './pages/Revenue.vue';
import Payments from './pages/Payments.vue';
import Profile from './pages/Profile.vue';
import Faq from './pages/Faq.vue';
import ContactUs from './pages/ContactUs.vue';
import Cards from './pages/Cards.vue';
import AddNewCard from './pages/AddNewCard.vue';
import AddStandardCard from './pages/AddStandardCard.vue';

const routeEndpoints = [
  {path: '/', name: 'Report', component: Report, title: 'Dashboard'},
  {path: '/cards', name: 'Cards', component: Cards, title: 'Cards'},
  {path: '/add-new-card', name: 'AddNewCard', component: AddNewCard, title: 'Add New Card'},
  {path: '/add-new-card/standard', name: 'AddStandardCard', component: AddStandardCard, hideSidenav: true},
  {path: '/revenue', name: 'Revenue', component: Revenue, title: 'Revenue'},
  {path: '/payment', name: 'Payments', component: Payments, title: 'Payment Details'},
  {path: '/profile', name: 'Profile', component: Profile, title: 'Profile'},
  {path: '/faq', name: 'Faq', component: Faq, title: 'Faq', hideSidenav: true},
  {path: '/contact', name: 'ContactUs', component: ContactUs, title: 'Contact Us', hideSidenav: true},
];

const router = createRouter({
  history: createWebHashHistory(),
  routes: routeEndpoints,
});

export {routeEndpoints}
export default router;
