<script setup lang="ts">
import {ShaplaFileUploader, ShaplaImage} from "@shapla/vue-components";
import useDesignerDashboardStore from "../store.ts";
import {computed, onMounted, reactive, ref} from "vue";
import {
  DynamicCardItemInterface,
  DynamicCardPayloadInterface,
  PhotoCardCardInterface,
  UploadedAttachmentInterface,
} from "../../interfaces/designer-card.ts";

import jsonCard from '../sample-data/photo-card-sample.ts'

const store = useDesignerDashboardStore();

const state = reactive<{
  card: PhotoCardCardInterface;
  main_card_upload_error_message: string;
  demo_card_upload_error_message: string;
  preveiwWidth: number;
  previewHeight: number;
}>({
  card: null,
  main_card_upload_error_message: '',
  demo_card_upload_error_message: '',
  preveiwWidth: 0,
  previewHeight: 0
})

const fileRequestHeaders = computed(() => {
  if (window.DesignerProfile.restNonce) {
    return {'X-WP-Nonce': window.DesignerProfile.restNonce};
  } else {
    return {}
  }
})

const hasMainImage = computed<boolean>(() => state.card && state.card.main_image_id > 0);

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
          width: 150,
          height: 150,
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
          width: 150,
          height: 150,
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
const pxToMm = (px: number) => Math.round(px * 0.2645833333);

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

const calculateWidthAndHeight = () => {
  let innerEL = canvasContainer.value;
  let d = [150, 150];

  state.preveiwWidth = innerEL.offsetWidth || (document.body.offsetWidth - 30);
  state.previewHeight = Math.round(state.preveiwWidth * (d[1] / d[0]));
}

onMounted(() => {
  state.card = jsonCard as PhotoCardCardInterface;
  setTimeout(() => calculateWidthAndHeight(), 1000)
})
</script>

<template>
  <h2 class="text-center text-4xl bg-gray-100 p-2 border border-solid border-primary mb-4">Add Photo Card</h2>
  <div>
    <div class="flex -m-2">
      <div class="sm:w-full md:w-1/2 p-2">
        <div class="dynamic-card-canvas-container" ref="canvasContainer">
          <dynamic-card-canvas
              :options='`${JSON.stringify(dynamicCardPayload)}`'
              :card-width-mm="150"
              :card-height-mm="150"
              :element-width-mm="pxToMm(state.preveiwWidth)"
              :element-height-mm="pxToMm(state.previewHeight)"
          ></dynamic-card-canvas>
        </div>
        <ShaplaImage>
          <template v-for="section in dynamicCardPayload.card_items">
            <img :src="section.imageOptions.img.src" alt="">
          </template>
        </ShaplaImage>
      </div>
      <div class="sm:w-full md:w-1/2 p-2">
        <div>
          <h2 class="text-2xl leading-none mb-4">Card Size</h2>
          <p>The size we're printing is square (15cm x 15cm), please upload the image in JPEG or PNG format with a
            minimum resolution of 1807 x 1807 px.</p>
        </div>
        <div>
          <h2 class="text-2xl leading-none mb-4">Bleed Needed</h2>
          <p>For the best results, please ensure your design as a 3mm bleed on the top, right and bottom and 1mm
            on the left of your design. These parts will get cut off when printed, anything you would like on
            the printed design must be kept within the cropping masks.</p>
        </div>
        <div class="mb-4">
          <h2 class="text-2xl leading-none mb-4">Upload Main Image</h2>
          <div class="w-full lg:max-w-[600px]">
            <ShaplaFileUploader
                class="static-card-image-uploader"
                :url="store.attachment_upload_url"
                @success="handleMainImageUpload"
                @fail="handleMainImageUploadFail"
                text-max-upload-limit="Max upload filesize: 5MB"
                :headers="fileRequestHeaders"
                :params="{type:'card_image',card_size:'square'}"
            />
            <div v-if="state.main_card_upload_error_message.length">
              <div v-html="state.main_card_upload_error_message"
                   class="p-2 text-red-600 border border-solid border-red-600"></div>
            </div>
          </div>
        </div>
        <div class="mb-4">
          <h2 class="text-2xl leading-none mb-0">Upload Demo Image</h2>
          <p class="mb-4">Please use royalty free image for commercial use.</p>
          <div class="w-full lg:max-w-[600px]">
            <ShaplaFileUploader
                class="static-card-image-uploader"
                :url="store.attachment_upload_url"
                @success="handleDemoImageUpload"
                @fail="handleDemoImageUploadFail"
                text-max-upload-limit="Max upload filesize: 5MB"
                :headers="fileRequestHeaders"
                :params="{type:'card_image',card_size:'square'}"
            />
            <div v-if="state.main_card_upload_error_message.length">
              <div v-html="state.main_card_upload_error_message"
                   class="p-2 text-red-600 border border-solid border-red-600"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
