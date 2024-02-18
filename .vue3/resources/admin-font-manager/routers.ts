import {createRouter, createWebHashHistory} from 'vue-router';
import PagePreInstalledFonts from './pages/PagePreInstalledFonts.vue';
import ExtraFontsListPage from './pages/ExtraFontsListPage.vue';

const routes = [
  {path: '/', name: 'PagePreInstalledFonts', component: PagePreInstalledFonts},
  {path: '/extra', name: 'ExtraFontsListPage', component: ExtraFontsListPage},
];

const router = createRouter({
  history: createWebHashHistory(),
  routes,
});

export default router;
