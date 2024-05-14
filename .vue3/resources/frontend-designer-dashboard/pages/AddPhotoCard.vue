<script setup lang="ts">
import {ShaplaButton, ShaplaColumn, ShaplaColumns, ShaplaFileUploader, ShaplaImage} from "@shapla/vue-components";
import useDesignerCardStore from "../stores/store-cards.ts";
import {computed, onMounted, reactive, ref} from "vue";
import {
  DynamicCardItemInterface,
  DynamicCardPayloadInterface,
  PhotoCardBaseInterface,
  UploadedAttachmentInterface,
} from "../../interfaces/designer-card.ts";
import CardOptionsPreview from "../components/CardOptionsPreview.vue";
import CardOptions from "../components/CardOptions.vue";
import {useRouter} from "vue-router";
import {convertPXtoMM} from "../../utils/helper.ts";

import cardTestData from '../sample-data/photo-card-sample.ts'

const store = useDesignerCardStore();
const router = useRouter();

const state = reactive<{
  card: PhotoCardBaseInterface;
  main_card_upload_error_message: string;
  demo_card_upload_error_message: string;
  previewWidth: number;
  previewHeight: number;
  stepDone: number;
}>({
  card: {
    main_image_id: 0,
    demo_image_id: 0,
    main_image: null,
    demo_image: null,
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
  main_card_upload_error_message: '',
  demo_card_upload_error_message: '',
  previewWidth: 0,
  previewHeight: 0,
  stepDone: 0,
})

const fileRequestHeaders = computed(() => {
  if (window.DesignerProfile.restNonce) {
    return {'X-WP-Nonce': window.DesignerProfile.restNonce};
  } else {
    return {}
  }
})

const hasMainImage = computed<boolean>(() => state.card && state.card.main_image_id > 0);
const hasDemoImage = computed<boolean>(() => state.card && state.card.demo_image_id > 0);

const dynamicCardPayload = computed<DynamicCardPayloadInterface>(() => {
  const card_items: DynamicCardItemInterface[] = [];
  if (state.card) {
    if (state.card.demo_image_id > 0) {
      const demoImage: DynamicCardItemInterface = {
        label: 'Demo Image',
        section_type: 'input-image',
        position: {left: 0, top: 0},
        imageOptions: {
          img: {
            id: state.card.demo_image.id,
            src: state.card.demo_image.full.src,
            width: state.card.demo_image.full.width,
            height: state.card.demo_image.full.height,
          },
          width: 154,
          height: 155,
          align: 'left'
        }
      }
      card_items.push(demoImage)
    }
    if (state.card.main_image_id > 0) {
      const mainImage: DynamicCardItemInterface = {
        label: 'Main Image',
        section_type: 'static-image',
        position: {left: 0, top: 0},
        imageOptions: {
          img: {
            id: state.card.main_image.id,
            src: state.card.main_image.full.src,
            width: state.card.main_image.full.width,
            height: state.card.main_image.full.height,
          },
          width: 154,
          height: 156,
          align: 'left'
        }
      }
      card_items.push(mainImage)
    }
  }
  return {
    card_size: 'square',
    card_bg_type: 'color',
    card_bg_color: '#ffffff',
    card_background: [],
    card_items: card_items,
  }
})

const canvasContainer = ref(null);

const handleMainImageUpload = (fileObject, serverResponse) => {
  const attachment = serverResponse.data.attachment as UploadedAttachmentInterface;
  state.card.main_image_id = attachment.id;
  state.card.main_image = attachment;
}
const handleMainImageUploadFail = (fileObject, serverResponse) => {
  if (serverResponse.message) {
    state.main_card_upload_error_message = serverResponse.message;
  }
}

const handleDemoImageUpload = (fileObject, serverResponse) => {
  const attachment = serverResponse.data.attachment as UploadedAttachmentInterface;
  state.card.demo_image_id = attachment.id;
  state.card.demo_image = attachment;
}
const handleDemoImageUploadFail = (fileObject, serverResponse) => {
  if (serverResponse.message) {
    state.demo_card_upload_error_message = serverResponse.message;
  }
}

const canGoOnStepTwo = computed(() => (state.card.main_image_id > 0 && state.card.demo_image_id > 0))
const canGoOnStepThree = computed(() => {
  return (
      state.card.title.length > 10 &&
      state.card.description.length > 10 &&
      state.card.categories_ids.length > 0 &&
      state.card.tags_ids.length > 0
  )
})

const calculateWidthAndHeight = () => {
  let innerEL = canvasContainer.value;
  let d = [154, 156];

  if (innerEL) {
    state.previewWidth = innerEL.offsetWidth || (document.body.offsetWidth - 30);
    state.previewHeight = Math.round(state.previewWidth * (d[1] / d[0]));
  }
}

const onSubmit = () => {
  const data: PhotoCardBaseInterface = {
    dynamic_card_payload: dynamicCardPayload.value,
    ...state.card
  };

  store.createPhotoCard(data).then(() => {
    router.push({name: 'Cards'});
  })
}

onMounted(() => {
  // @TODO remove it after testing.
  state.card = cardTestData;

  setTimeout(() => calculateWidthAndHeight(), 1000)
})
</script>

<template>
  <h2 class="text-center text-4xl bg-gray-100 p-2 border border-solid border-primary mb-4">Add Photo Card</h2>
  <div class="max-w-5xl mx-auto">
    <div v-if="state.stepDone === 0" class="flex">
      <div class="w-full md:w-1/2 p-2">
        <ShaplaImage :width-ratio="150" :height-ratio="150">
          <div class="dynamic-card-canvas-container" ref="canvasContainer">
            <dynamic-card-canvas
                :data-options='`${JSON.stringify(dynamicCardPayload)}`'
                :card-width-mm="154"
                :card-height-mm="156"
                :element-width-mm="convertPXtoMM(state.previewWidth)"
                :element-height-mm="convertPXtoMM(state.previewHeight)"
                :element-width-px="state.previewWidth"
                :element-height-px="state.previewHeight"
            ></dynamic-card-canvas>
          </div>
        </ShaplaImage>
      </div>
      <div class="w-full md:w-1/2 p-2">
        <div>
          <h2 class="text-2xl leading-none mb-4">Card Size</h2>
          <p>The size we're printing is square (15cm x 15cm), please upload the image in JPEG or PNG format with a
            minimum resolution of <strong>1819 x 1843 px</strong>.</p>
        </div>
        <div>
          <h2 class="text-2xl leading-none mb-4">Bleed Needed</h2>
          <p>For the best results, please ensure your design as a 3mm bleed on the top, right and bottom and 1mm
            on the left of your design. These parts will get cut off when printed, anything you would like on
            the printed design must be kept within the cropping masks.</p>
        </div>
        <div class="mb-4 lg:flex space-x-4">
          <div class="w-full lg:w-1/2 mb-4">
            <h2 class="text-2xl leading-none mb-0">Upload Main Image</h2>
            <p class="mb-4">Please use royalty free image for commercial use.</p>
            <div class="w-full lg:max-w-[600px]">
              <template v-if="hasMainImage">
                <ShaplaImage class="border border-solid border-gray-200" container-width="150px"
                             container-height="150px">
                  <img :src="state.card.main_image.thumbnail.src" alt="Main image">
                </ShaplaImage>
              </template>
              <template v-else>
                <ShaplaFileUploader
                    class="static-card-image-uploader"
                    :url="store.attachment_upload_url"
                    @success="handleMainImageUpload"
                    @fail="handleMainImageUploadFail"
                    text-max-upload-limit="Max upload filesize: 5MB"
                    :headers="fileRequestHeaders"
                    :params="{type:'card_image',card_size:'square',card_type:'photo_card'}"
                />
                <div v-if="state.main_card_upload_error_message.length">
                  <div v-html="state.main_card_upload_error_message"
                       class="p-2 text-red-600 border border-solid border-red-600"></div>
                </div>
              </template>
            </div>
          </div>
          <div class="w-full lg:w-1/2 mb-4">
            <h2 class="text-2xl leading-none mb-0">Upload Demo Image</h2>
            <p class="mb-4">Please use royalty free image for commercial use.</p>
            <div class="w-full lg:max-w-[600px]">
              <template v-if="hasDemoImage">
                <ShaplaImage class="border border-solid border-gray-200" container-width="150px"
                             container-height="150px">
                  <img :src="state.card.demo_image.thumbnail.src" alt="Main image">
                </ShaplaImage>
              </template>
              <template v-else>
                <ShaplaFileUploader
                    class="static-card-image-uploader"
                    :url="store.attachment_upload_url"
                    @success="handleDemoImageUpload"
                    @fail="handleDemoImageUploadFail"
                    text-max-upload-limit="Max upload filesize: 5MB"
                    :headers="fileRequestHeaders"
                    :params="{type:'card_image',card_size:'square',card_type:'photo_card'}"
                />
                <div v-if="state.demo_card_upload_error_message.length">
                  <div v-html="state.demo_card_upload_error_message"
                       class="p-2 text-red-600 border border-solid border-red-600"></div>
                </div>
              </template>
            </div>
          </div>
        </div>
        <div>
          <ShaplaButton theme="primary" size="large" fullwidth @click="state.stepDone = 1" :disabled="!canGoOnStepTwo">
            Next
          </ShaplaButton>
        </div>
      </div>
    </div>
    <div v-if="1 === state.stepDone" class="flex flex-col items-center">
      <CardOptions v-model="state.card"/>
      <div class="flex justify-center mt-4">
        <ShaplaButton theme="primary" @click="state.stepDone = 2" :disabled="!canGoOnStepThree">Next</ShaplaButton>
      </div>
    </div>
    <div v-if="2 === state.stepDone" class="mb-4">
      <CardOptionsPreview :card="state.card"/>
      <ShaplaColumns multiline>
        <template v-if="state.card.main_image_id">
          <ShaplaColumn :tablet="3"><strong>Main Image</strong></ShaplaColumn>
          <ShaplaColumn :tablet="9">
            <div class="max-w-[300px] h-auto">
              <img :src="state.card.main_image.thumbnail.src" alt="">
            </div>
          </ShaplaColumn>
        </template>
        <template v-if="state.card.demo_image_id">
          <ShaplaColumn :tablet="3"><strong>Demo Image</strong></ShaplaColumn>
          <ShaplaColumn :tablet="9">
            <div class="max-w-[300px] h-auto">
              <img :src="state.card.demo_image.thumbnail.src" alt="">
            </div>
          </ShaplaColumn>
        </template>
      </ShaplaColumns>
      <div class="flex justify-center mt-4">
        <ShaplaButton theme="primary" @click="onSubmit">Submit</ShaplaButton>
      </div>
    </div>
    <div class="mb-4">&nbsp;</div>
  </div>
</template>
