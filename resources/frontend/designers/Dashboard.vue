<template>
  <div class="yousaidit-designer-profile">
    <dashboard-layout
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
              <icon-container>
                <svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24">
                  <path d="M0 0h24v24H0z" fill="none"/>
                  <path d="M16.01 11H4v2h12.01v3L20 12l-3.99-4z"/>
                </svg>
              </icon-container>
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

    </dashboard-layout>

    <svg-icons/>
  </div>
</template>

<script>
import {dashboardLayout, iconContainer} from 'shapla-vue-components';
import {TouchSwipe} from './TouchSwipe'
import {routeEndpoints} from "./routers";
import SvgIcons from "./SvgIcons";

export default {
  name: "Dashboard",
  mixins: [TouchSwipe],
  components: {SvgIcons, dashboardLayout, iconContainer},

  data() {
    return {
      activateSideNav: false,
    }
  },
  computed: {
    routeEndpoints() {
      return routeEndpoints;
    },
    current_user() {
      return DesignerProfile.user;
    },
    logoUrl() {
      return DesignerProfile.logoUrl;
    },
    logOutUrl() {
      return DesignerProfile.logOutUrl.replace('&amp;', '&').replace('&amp;', '&');
    },
  },

  mounted() {
    this.$on('swipe:end', (direction, data) => {
      if (!this.activateSideNav && 'to-right' === direction && data['startX'] < 20) {
        this.activateSideNav = true;
      }
      if (this.activateSideNav && 'to-left' === direction) {
        this.activateSideNav = false;
      }
    });
  },
  methods: {
    toRoute(menu) {
      if (this.$route.name !== menu.name) {
        this.$router.push({name: menu.name});
      }
      this.activateSideNav = false;
    }
  }
}
</script>

<style lang="scss">
@import "~shapla-color-system/src/variables";
@import "../../scss/tailwind";

.shapla-dashboard-header__burger {
  @apply lg:hidden;
}

body.designer-profile-page {
  header.site-header {
    z-index: -1;
  }
}

.yousaidit-designer-profile {
  .sidenav-list__item {
    margin: 0;
  }

  .shapla-dashboard-sidenav-menu {
    height: 100%;
    display: flex;
    flex-direction: column;
  }

  .sidenav-spacer {
    flex-grow: 1;
  }

  .shapla-text-field__help-text {
    color: var(--shapla-text-secondary, rgba(0, 0, 0, 0.54));
  }
}

.nav-menu {
  list-style-type: none;

  a {
    font-size: 16px;
    font-weight: 500;
    padding-right: 1rem;
  }

  li {
    margin-bottom: 5px;
    margin-top: 5px;
    line-height: 1;
  }
}

.dashboard-logo {
  img {
    max-height: 40px;
  }
}
</style>
