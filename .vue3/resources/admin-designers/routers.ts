import {createRouter, createWebHashHistory} from 'vue-router';
import Designers from './pages/Designers.vue';
import Designer from './pages/Designer.vue';
import Cards from './pages/Cards.vue';
import Card from './pages/Card.vue';
import Settings from './pages/Settings.vue';
import PayPalPayouts from './pages/PayPalPayouts.vue';
import PayPalPayout from './pages/PayPalPayout.vue';
import Commissions from './pages/Commissions.vue';

const routes = [
  {path: '/', name: 'Designers', component: Designers},
  {path: '/designers/:id', name: 'Designer', component: Designer},
  {path: '/cards', name: 'Cards', component: Cards},
  {path: '/cards/:id', name: 'Card', component: Card},
  {path: '/settings', name: 'Settings', component: Settings},
  {path: '/payouts', name: 'Payouts', component: PayPalPayouts},
  {path: '/payouts/:id', name: 'Payout', component: PayPalPayout},
  {path: '/commissions', name: 'Commissions', component: Commissions},
];

const router = createRouter({
  history: createWebHashHistory(),
  routes,
});

export default router;
