<script setup lang="ts">
import {computed, onMounted, reactive} from "vue";
import {ShaplaButton, ShaplaColumn, ShaplaFileUploader, ShaplaIcon, ShaplaImage} from "@shapla/vue-components";
import {Notify} from "@shapla/vanilla-components";
import CardOptions from "../components/CardOptions.vue";
import CardOptionsPreview from "../components/CardOptionsPreview.vue";
import {StandardCardBaseInterface, UploadedAttachmentInterface} from "../../interfaces/designer-card.ts";
import {useRouter} from "vue-router";
import useDesignerCardStore from "../stores/store-cards.ts";

import mugTestData from '../sample-data/mug-sample.ts'

const store = useDesignerCardStore();
const router = useRouter();

const state = reactive<{
  stepDone: number;
  readRequirement: boolean;
  cardSize: 'square' | 'a4';
  card: StandardCardBaseInterface,
  upload_error_message: string;
}>({
  stepDone: 0,
  readRequirement: false,
  cardSize: 'square',
  card: {
    image_id: 0,
    image: null,
    title: '',
    description: '',
    sizes: ['square'],
    categories_ids: [],
    tags_ids: [],
    attributes: {},
    market_places: ['yousaidit'],
    rude_card: 'no',
    has_suggest_tags: 'no',
    suggest_tags: '',
  },
  upload_error_message: '',
})

const downloadTemplate = (fileType: 'ps' | 'ai') => {
  const a = document.createElement('a')
  a.target = '_blank'
  a.href = window.DesignerProfile.templates[fileType];
  a.click();
}

const fileRequestHeaders = computed(() => {
  if (window.DesignerProfile.restNonce) {
    return {'X-WP-Nonce': window.DesignerProfile.restNonce};
  } else {
    return {}
  }
})

const hasImage = computed(() => state.card.image_id > 0)

const handleImageUpload = (fileObject, serverResponse) => {
  const attachment = serverResponse.data.attachment as UploadedAttachmentInterface;
  state.card.image_id = attachment.id;
  state.card.image = attachment;
}
const handleImageUploadFailed = (fileObject, serverResponse) => {
  if (serverResponse.message) {
    state.upload_error_message = serverResponse.message;
  }
}

const onSubmit = () => {
  store.createMug(state.card).then(() => {
    router.push({name: 'Cards'});
  })
}

const removeImage = () => {
  store
      .deleteImage(state.card.image_id)
      .catch(errors => {
        if (errors.message) {
          Notify.error(errors.message, 'Error!');
        }
      })
      .finally(() => {
        state.card.image_id = 0;
        state.card.image = null;
      })
}

onMounted(() => {
  // state.card = mugTestData as StandardCardBaseInterface;
  // state.stepDone = 4;
})
</script>

<template>
  <h2 class="text-center text-4xl bg-gray-100 p-2 border border-solid border-primary mb-4">Add Mug</h2>
  <div class="max-w-5xl mx-auto">
    <div v-if="0 === state.stepDone" class="flex">
      <div class="w-full md:w-1/2 p-2">
        <ShaplaImage :width-ratio="210" :height-ratio="99" class="shadow">
          <img v-if="state.card.image_id" :src="state.card.image.attachment_url" alt="">
          <div v-if="!state.card.image_id" class="w-full h-full">
            <ShaplaFileUploader
                class="static-card-image-uploader"
                :url="store.attachment_upload_url"
                @success="handleImageUpload"
                @fail="handleImageUploadFailed"
                text-max-upload-limit="Max upload filesize: 5MB"
                :headers="fileRequestHeaders"
                :params="{type:'card_image',card_size:state.cardSize,card_type:'mug'}"
            />
            <div v-if="state.upload_error_message.length">
              <div v-html="state.upload_error_message"
                   class="p-2 text-red-600 border border-solid border-red-600"></div>
            </div>
          </div>
        </ShaplaImage>
        <div class="mt-2 flex justify-center">
          <ShaplaButton v-if="state.card.image_id" theme="primary" @click="removeImage">Remove Image</ShaplaButton>
        </div>
      </div>
      <div class="w-full md:w-1/2 p-2">
        <div>
          <h2 class="text-2xl leading-none mb-4">Mug Size</h2>
          <p>The size we're printing is square (21cm x 9.9cm), please upload the image in JPEG format with a minimum
            resolution of <strong>2480 x 1169 px</strong>.</p>
        </div>
        <div>
          <h2 class="text-2xl leading-none mb-4">Templates</h2>
          <p>To make it easier, why not download one of our templates to ensure your artwork is going to be print
            ready.</p>
          <div class="space-x-2">
            <ShaplaIcon size="large" @click="downloadTemplate('ps')">
              <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32">
                <path fill="#00c8ff"
                      d="M0 0.4v31.2h32v-31.2zM1.333 1.733h29.333v28.533h-29.333zM7.733 7.707c0-0.089 0.187-0.155 0.299-0.155 0.859-0.044 2.117-0.067 3.437-0.067 3.696 0 5.133 2.027 5.133 4.621 0 3.387-2.456 4.84-5.469 4.84-0.507 0-0.68-0.023-1.033-0.023v5.123c0 0.111-0.044 0.155-0.153 0.155h-2.059c-0.111 0-0.153-0.040-0.153-0.151zM10.1 14.789c0.307 0.021 0.549 0.021 1.080 0.021 1.56 0 3.027-0.549 3.027-2.661 0-1.693-1.048-2.552-2.829-2.552-0.528 0-1.033 0.021-1.276 0.044zM21.576 13.205c-1.056 0-1.408 0.528-1.408 0.968 0 0.484 0.24 0.813 1.649 1.54 2.091 1.013 2.749 1.98 2.749 3.409 0 2.133-1.627 3.28-3.827 3.28-1.168 0-2.16-0.244-2.733-0.573-0.087-0.044-0.107-0.109-0.107-0.22v-1.956c0-0.133 0.064-0.177 0.152-0.112 0.832 0.551 1.803 0.792 2.683 0.792 1.056 0 1.496-0.44 1.496-1.035 0-0.484-0.307-0.903-1.649-1.607-1.893-0.907-2.685-1.827-2.685-3.369 0-1.716 1.341-3.147 3.673-3.147 1.147 0 1.952 0.176 2.392 0.373 0.109 0.067 0.133 0.176 0.133 0.264v1.827c0 0.111-0.067 0.177-0.2 0.133-0.592-0.352-1.467-0.573-2.319-0.568z"></path>
              </svg>
            </ShaplaIcon>
            <ShaplaIcon size="large" @click="downloadTemplate('ai')">
              <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32">
                <path fill="#ff7c00"
                      d="M0 0.4v31.2h32v-31.2zM1.333 1.733h29.333v28.533h-29.333zM11.1 18.067l-1.056 3.997c-0.023 0.111-0.067 0.136-0.197 0.136h-1.957c-0.133 0-0.153-0.044-0.133-0.197l3.787-13.26c0.067-0.24 0.109-0.451 0.131-1.111 0-0.088 0.044-0.133 0.111-0.133h2.795c0.088 0 0.133 0.024 0.155 0.133l4.247 14.392c0.023 0.111 0 0.176-0.111 0.176h-2.2c-0.111 0-0.176-0.027-0.197-0.115l-1.1-4.020zM14.817 15.9c-0.373-1.475-1.253-4.704-1.584-6.267h-0.023c-0.285 1.56-0.989 4.2-1.54 6.267zM20.817 8.489c0-0.857 0.593-1.364 1.364-1.364 0.813 0 1.364 0.549 1.364 1.364 0 0.88-0.573 1.364-1.387 1.364-0.8 0-1.347-0.484-1.341-1.364zM20.967 11.521c0-0.107 0.044-0.147 0.155-0.147h2.093c0.117 0 0.16 0.044 0.16 0.155v10.527c0 0.111-0.021 0.155-0.153 0.155h-2.067c-0.133 0-0.177-0.067-0.177-0.173z"></path>
              </svg>
            </ShaplaIcon>
          </div>
        </div>
        <div class="mt-4">
          <ShaplaButton theme="primary" :disabled="!hasImage" @click="state.stepDone = 1">Next
          </ShaplaButton>
        </div>
      </div>
    </div>
    <div v-if="1 === state.stepDone" class="flex flex-col items-center">
      <CardOptions v-model="state.card" :is-mug="true"/>
      <div class="flex justify-center mt-4">
        <ShaplaButton theme="primary" @click="state.stepDone = 2">Next</ShaplaButton>
      </div>
    </div>
    <div v-if="2 === state.stepDone" class="flex flex-col items-center">
      <CardOptionsPreview :card="state.card" :is-mug="true">
        <template v-slot:before-column-end>
          <template v-if="state.card.image">
            <ShaplaColumn :tablet="3"><strong>Card Image</strong></ShaplaColumn>
            <ShaplaColumn :tablet="9">
              <div class="max-w-[300px] h-auto">
                <ShaplaImage :width-ratio="state.card.image.full.width" :height-ratio="state.card.image.full.height">
                  <img :src="state.card.image.full.src" alt="">
                </ShaplaImage>
              </div>
            </ShaplaColumn>
          </template>
        </template>
      </CardOptionsPreview>

      <div class="w-full mt-4">
        <ShaplaButton theme="primary" @click="onSubmit" fullwidth size="large">Submit</ShaplaButton>
      </div>
    </div>
    <div class="mb-4">&nbsp;</div>
  </div>
</template>
