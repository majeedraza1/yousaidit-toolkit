<template>
  <div class="yousaidit-designer-profile">
    <ShaplaDashboard
        title="Dashboard"
        :activate-side-nav="activateSideNav"
        @open:sidenav="activateSideNav = true"
        @close:sidenav="activateSideNav = false"
        :user-display-name="current_user.display_name"
        :avatar-url="current_user.avatar_url"
        greeting="Hello,"
        header-theme="default"
    >
      <template v-slot:sidenav-menu>
        <ul class="sidenav-list">
          <template v-for="menu in routeEndpoints">
            <li class="sidenav-list__item" v-if="!menu.hideSidenav">
              <a class="sidenav-list__link" :class="{'is-active':$route.name === menu.name}"
                 :href="`#${menu.path}`" @click.prevent="toRoute(menu)">{{ menu.title }}</a>
            </li>
          </template>
        </ul>
        <div class="sidenav-spacer"></div>
        <ul class="sidenav-list">
          <li class="sidenav-list__item">
            <a class="sidenav-list__link" :href="logOutUrl">
              Logout
              <ShaplaIcon>
                <svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24">
                  <path d="M0 0h24v24H0z" fill="none"/>
                  <path d="M16.01 11H4v2h12.01v3L20 12l-3.99-4z"/>
                </svg>
              </ShaplaIcon>
            </a>
          </li>
        </ul>
      </template>

      <template v-slot:navbar-brand v-if="!!logoUrl">
        <div class="dashboard-logo">
          <img :src="logoUrl" alt="">
        </div>
      </template>

      <template v-slot:navbar-start>
        <ul class="nav-menu hidden lg:flex ml-4 my-0">
          <template v-for="menu in routeEndpoints">
            <li class="sidenav-list__item" v-if="!menu.hideSidenav">
              <a class="sidenav-list__link" :class="{'is-active':$route.name === menu.name}"
                 :href="`#${menu.path}`" @click.prevent="toRoute(menu)">{{ menu.title }}</a>
            </li>
          </template>
        </ul>
      </template>
      <template v-slot:navbar-end>
        <ul class="nav-menu hidden md:flex m-0">
          <li><a target="_blank" :href="current_user.author_posts_url">View Profile</a></li>
          <li><a href="#/faq">FAQs</a></li>
          <li><a href="#/contact">Contact Us</a></li>
        </ul>
      </template>

      <router-view></router-view>

    </ShaplaDashboard>
    <SvgIcons/>
  </div>
</template>

<script lang="ts" setup>
import {ShaplaDashboard, ShaplaIcon} from '@shapla/vue-components';
import {routeEndpoints} from "./routers";
import SvgIcons from "./SvgIcons.vue";
import {computed, ref} from "vue";
import {useRoute, useRouter} from 'vue-router'

const router = useRouter();
const route = useRoute()

const activateSideNav = ref(false);

const current_user = computed(() => window.DesignerProfile.user)
const logoUrl = computed(() => window.DesignerProfile.logoUrl)
const logOutUrl = computed(() => window.DesignerProfile.logOutUrl.replace('&amp;', '&').replace('&amp;', '&'))

const toRoute = (menu) => {
  if (route.name !== menu.name) {
    router.push({name: menu.name});
  }
  activateSideNav.value = false;
}
</script>
