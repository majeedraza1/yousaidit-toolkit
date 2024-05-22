import {createRouter, createWebHashHistory} from 'vue-router';
import Reminders from './pages/Reminders.vue';
import RemindersGroups from './pages/RemindersGroups.vue';
import RemindersQueue from './pages/RemindersQueue.vue';

const routes = [
  {path: '/', name: 'Reminders', component: Reminders},
  {path: '/groups', name: 'RemindersGroups', component: RemindersGroups},
  {path: '/queue', name: 'Queue', component: RemindersQueue},
];

const router = createRouter({
  history: createWebHashHistory(),
  routes,
});

export default router;

