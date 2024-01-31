<script lang="ts" setup>
import {ShaplaColumn, ShaplaColumns} from '@shapla/vue-components'
import useDesignerDashboardStore from "../store.ts";
import {useRouter} from "vue-router";

const store = useDesignerDashboardStore();
const router = useRouter();
const redirectTo = (cardType: string) => {
  if ('standard' === cardType) {
    router.push({name: 'AddStandardCard'});
  }
  if ('photo-card' === cardType) {
    router.push({name: 'AddPhotoCard'});
  }
  if ('text-card' === cardType) {
    router.push({name: 'AddTextCard'});
  }
  if ('mug' === cardType) {
    router.push({name: 'AddMug'});
  }
}
</script>

<template>
  <div>
    <ShaplaColumns multiline>
      <ShaplaColumn :tablet="6" :desktop="4" :widescreen="3">
        <div class="border border-solid border-primary rounded" @click="redirectTo('standard')">
          <div class="yousaidit-report-card__content">
            <img :src="store.standardCardUrl" alt="Standard Card">
          </div>
          <div class="text-center p-4 bg-primary rounded-b">
            <div class="text-white font-bold text-xl">Standard Card</div>
          </div>
        </div>
      </ShaplaColumn>
      <ShaplaColumn :tablet="6" :desktop="4" :widescreen="3">
        <div class="border border-solid border-primary rounded" @click="redirectTo('photo-card')">
          <div class="yousaidit-report-card__content">
            <img :src="store.photoCardUrl" alt="Personalised Photo Card">
          </div>
          <div class="text-center p-4 bg-primary rounded-b">
            <div class="text-white font-bold text-xl">Personalised Photo Card</div>
          </div>
        </div>
      </ShaplaColumn>
      <ShaplaColumn :tablet="6" :desktop="4" :widescreen="3">
        <div class="relative">
          <div class="border border-solid border-primary rounded" @click="redirectTo('text-card')">
            <div class="yousaidit-report-card__content">
              <img :src="store.textCardUrl" alt="Personalised Text Card">
            </div>
            <div class="text-center p-4 bg-primary rounded-b">
              <div class="text-white font-bold text-xl">Personalised Text Card</div>
            </div>
          </div>
          <div class="absolute bg-white bg-opacity-75 top-0 left-0 w-full h-full flex justify-center items-center">
            <div class="font-bold text-xl text-primary">Coming Soon</div>
          </div>
        </div>
      </ShaplaColumn>
      <ShaplaColumn :tablet="6" :desktop="4" :widescreen="3">
        <div class="relative">
          <div class="border border-solid border-primary rounded" @click="redirectTo('mug')">
            <div class="text-base p-4">
              <img :src="store.mugUrl" alt="Mug">
            </div>
            <div class="text-center p-4 bg-primary rounded-b">
              <div class="text-white font-bold text-xl">Mug</div>
            </div>
          </div>
          <div class="absolute bg-white bg-opacity-75 top-0 left-0 w-full h-full flex justify-center items-center">
            <div class="font-bold text-xl text-primary">Coming Soon</div>
          </div>
        </div>
      </ShaplaColumn>
    </ShaplaColumns>
  </div>
</template>