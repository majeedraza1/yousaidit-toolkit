<template>
  <ShaplaModal
      :active="state.show_dynamic_card_editor"
      @close="state.show_dynamic_card_editor = false"
      type="box"
      content-size="full"
      :show-card-footer="false"
      :show-close-icon="false"
      class="modal--single-product-dynamic-card"
      content-class="modal-dynamic-card-content"
  >
    <div class="w-full h-full flex flex-col lg:flex-row lg:space-x-4">
      <div class="flex flex-col flex-grow dynamic-card--canvas">
        <div class="w-full flex dynamic-card--canvas-slider">
          <SwiperSlider v-if="state.show_dynamic_card_editor && Object.keys(state.payload).length"
                        :card_size="state.card_size" :slide-to="state.slideTo" @slideChange="onSlideChange">
            <template v-slot:canvas="slotProps">
              <dynamic-card-canvas
                  :show-edit-icon="true"
                  :data-options="`${JSON.stringify(state.payload)}`"
                  :active-section-index="state.activeSectionIndex"
                  :card-width-mm="154"
                  :card-height-mm="156"
                  :element-width-mm="`${pxToMm(slotProps.sizes.width)}`"
                  :element-height-mm="`${pxToMm(slotProps.sizes.height)}`"
                  :element-width-px="`${slotProps.sizes.width}`"
                  :element-height-px="`${slotProps.sizes.height}`"
                  @edit:layer="(event) => handleEditSection(event.detail.section,event.detail.index)"
              ></dynamic-card-canvas>
            </template>
            <template v-slot:video-message>
              <VideoInnerMessage
                  :product_id="state.product_id"
                  :inner-message="state.innerMessage2"
                  :card_size="state.card_size"
                  @change="changeVideoInnerMessage"
                  :open-ai-editable="1===state.slideTo"
              />
            </template>
            <template v-slot:inner-message>
              <div class="dynamic-card--editable-content-container">
                <EditableContent
                    placeholder="Please click here to write your message"
                    :font-family="state.innerMessage.font_family"
                    :font-size="state.innerMessage.font_size"
                    :text-align="state.innerMessage.alignment"
                    :color="state.innerMessage.color"
                    v-model="state.innerMessage.message"
                    :card-size="state.card_size"
                    @lengthError="onLengthError"
                    :open-ai-editable="2===state.slideTo"
                />
                <div v-if="state.showLengthError" class="has-error p-2 my-4 absolute bottom-0">
                  Oops... your message is too long, please keep inside the box.
                </div>
              </div>
            </template>
          </SwiperSlider>
        </div>
        <div class="swiper-thumbnail mt-4 dynamic-card--canvas-thumb bg-gray-200">
          <div class="flex space-x-4 p-2 justify-center">
            <ShaplaImage container-width="64px" class="bg-gray-100" @click.native="state.slideTo = 0"
                         :class="{'border border-solid border-primary':state.slideTo === 0}">
              <img :src="state.product_thumb" alt="">
            </ShaplaImage>
            <ShaplaImage container-width="64px" class="bg-gray-100" @click.native="state.slideTo = 1"
                         :class="{'border border-solid border-primary':state.slideTo === 1}">
              <img :src="placeholder_im_left" alt=""/>
            </ShaplaImage>
            <ShaplaImage container-width="64px" class="bg-gray-100" @click.native="state.slideTo = 2"
                         :class="{'border border-solid border-primary':state.slideTo === 2}">
              <img :src="placeholder_im_right" alt=""/>
            </ShaplaImage>
          </div>
        </div>
      </div>
      <div
          class="flex flex-col justify-between bg-gray-100 p-2 dynamic-card--controls lg:border border-solid border-gray-100">
        <div v-if="state.activeSectionIndex === -1 && state.slideTo === 0">
          <div><strong>Help tips:</strong></div>
          <div class="flex">
            Click on icon (
            <ShaplaIcon size="medium">
              <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px">
                <rect fill="none" height="24" width="24"></rect>
                <path
                    d="M3,10h11v2H3V10z M3,8h11V6H3V8z M3,16h7v-2H3V16z M18.01,12.87l0.71-0.71c0.39-0.39,1.02-0.39,1.41,0l0.71,0.71 c0.39,0.39,0.39,1.02,0,1.41l-0.71,0.71L18.01,12.87z M17.3,13.58l-5.3,5.3V21h2.12l5.3-5.3L17.3,13.58z"></path>
              </svg>
            </ShaplaIcon>
            ) to customize text.
          </div>
          <div class="flex">
            Click on icon (
            <ShaplaIcon size="medium">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24px" height="24px">
                <rect fill="none" height="24" width="24"></rect>
                <path
                    d="M18.85,10.39l1.06-1.06c0.78-0.78,0.78-2.05,0-2.83L18.5,5.09c-0.78-0.78-2.05-0.78-2.83,0l-1.06,1.06L18.85,10.39z M14.61,11.81L7.41,19H6v-1.41l7.19-7.19L14.61,11.81z M13.19,7.56L4,16.76V21h4.24l9.19-9.19L13.19,7.56L13.19,7.56z M19,17.5 c0,2.19-2.54,3.5-5,3.5c-0.55,0-1-0.45-1-1s0.45-1,1-1c1.54,0,3-0.73,3-1.5c0-0.47-0.48-0.87-1.23-1.2l1.48-1.48 C18.32,15.45,19,16.29,19,17.5z M4.58,13.35C3.61,12.79,3,12.06,3,11c0-1.8,1.89-2.63,3.56-3.36C7.59,7.18,9,6.56,9,6 c0-0.41-0.78-1-2-1C5.74,5,5.2,5.61,5.17,5.64C4.82,6.05,4.19,6.1,3.77,5.76C3.36,5.42,3.28,4.81,3.62,4.38C3.73,4.24,4.76,3,7,3 c2.24,0,4,1.32,4,3c0,1.87-1.93,2.72-3.64,3.47C6.42,9.88,5,10.5,5,11c0,0.31,0.43,0.6,1.07,0.86L4.58,13.35z"></path>
              </svg>
            </ShaplaIcon>
            ) to customize image.
          </div>
        </div>
        <template v-if="state.activeSectionIndex >= 0">
          <div v-if="state.activeSection.section_type === 'input-text'" class="mb-4">
            <input type="text" v-model="state.activeSection.text" :placeholder="state.activeSection.placeholder">
            <ShaplaButton outline size="small" @click="state.activeSection.text = ''">Clear</ShaplaButton>
            <ShaplaButton outline size="small" theme="primary" @click="closeSection">Confirm</ShaplaButton>
          </div>
          <div v-if="state.activeSection.section_type === 'input-image'" class="mb-4">
            <ShaplaTabs fullwidth centered>
              <ShaplaTab name="Settings" selected>
                <InputUserOptions
                    v-model="state.activeSection.userOptions"
                    @change:modelValue="onChangeUserOptions"
                    :card-width-mm="card_dimension[0]"
                    :card-height-mm="card_dimension[1]"
                />
              </ShaplaTab>
              <ShaplaTab name="Images">
                <div class="flex flex-wrap uploade-image-thumbnail-container">
                  <template v-if="state.images.length">
                    <div v-for="_img in state.images" class="w-1/4 p-1">
                      <img :src="_img.thumbnail.src || _img.full.src" alt=""
                           @click="handleImageSelect(_img)"
                           class="border-4 border-solid border-gray-200"
                           :class="{'border-primary':isImageSelected(_img)}"
                      >
                    </div>
                  </template>
                  <template v-else>
                    You did not add any image yet. Upload some images first.
                  </template>
                </div>
              </ShaplaTab>
              <ShaplaTab name="Upload">
                <ShaplaFileUploader
                    :url="uploadUrl"
                    @headers="beforeSendEvent"
                    @success="finishedEvent"
                    @failed="handleFileUploadFailed"
                />
              </ShaplaTab>
              <ShaplaTab v-if="stability_ai_enabled" name="Use AI">
                <AiImageControls @aiImageGenerated="onGeneratedAiImage"/>
              </ShaplaTab>
            </ShaplaTabs>
          </div>
        </template>
        <div v-if="state.slideTo === 2">
          <EditorControls
              v-model="state.innerMessage"
              @change="onChangeEditorControls"
              @generateContent="onGenerateContentRight"
          />
        </div>
        <div v-if="state.slideTo === 1">
          <EditorControls
              v-model="state.innerMessage2"
              @change="onChangeEditorControls"
              @generateContent="onGenerateContentLeft"
          />
        </div>
        <div class="space-y-2">
          <ShaplaButton theme="primary" size="small" fullwidth outline
                        @click="state.show_dynamic_card_editor = false">
            Cancel
          </ShaplaButton>
          <ShaplaButton theme="primary" size="medium" fullwidth @click="handleSubmit">
            Add to basket
          </ShaplaButton>
        </div>
      </div>
    </div>
  </ShaplaModal>
</template>

<script lang="ts" setup>
import axios from "../utils/axios.ts";
import {
  ShaplaButton,
  ShaplaFileUploader,
  ShaplaIcon,
  ShaplaImage,
  ShaplaModal,
  ShaplaTab,
  ShaplaTabs
} from "@shapla/vue-components";
import {Notify, Spinner} from '@shapla/vanilla-components'
import EditableContent from "./components/EditableContent.vue";
import EditorControls from "./components/EditorControls.vue";
import SwiperSlider from "./components/SwiperSlider.vue";
import GustLocalStorage from "./helpers/GustLocalStorage.ts";
import VideoInnerMessage from "./components/VideoInnerMessage.vue";
import InputUserOptions from "./components/InputUserOptions.vue";
import {computed, onMounted, reactive, watch} from "vue";
import AiImageControls from "./components/AiImageControls.vue";

const state = reactive({
  slideTo: 0,
  product_id: 0,
  card_size: 'square',
  show_dynamic_card_editor: false,
  is_card_category_popup: false,
  payload: {},
  innerMessage: {
    message: '',
    font_family: "'Indie Flower', cursive",
    font_size: '18',
    alignment: 'center',
    color: '#1D1D1B',
  },
  innerMessage2: {
    message: '',
    font_family: "'Indie Flower', cursive",
    font_size: '18',
    alignment: 'center',
    color: '#1D1D1B',
    type: '',
    video_id: 0,
  },
  readFromServer: false,
  images: [],
  activeSection: {},
  activeSectionIndex: -1,
  product_thumb: '',
  placeholder_im: '',
  showLengthError: false,
})

const stability_ai_enabled = window.StackonetToolkit.stability_ai_enabled;
const placeholder_im_left = window.StackonetToolkit.placeholderUrlIML;
const placeholder_im_right = window.StackonetToolkit.placeholderUrlIMR;
const uploadUrl = window.StackonetToolkit.restRoot + '/dynamic-cards/media';
const isUserLoggedIn = window.StackonetToolkit.isUserLoggedIn || false;
const card_dimension = computed(() => {
  const card_sizes = {
    a4: [426, 303],
    a5: [303, 216],
    a6: [216, 154],
    square: [306, 156],
  }
  if (Object.keys(card_sizes).indexOf(state.card_size) === -1) {
    return [0, 0];
  }
  let dimension = card_sizes[state.card_size];
  return [(dimension[0] / 2) + 1, dimension[1]];
})


const closeSection = () => {
  state.activeSection = {};
  state.activeSectionIndex = -1;
}

watch(() => state.slideTo, () => closeSection());

watch(() => state.activeSection, (newValue) => {
  if (state.activeSectionIndex >= 0) {
    state.payload.card_items[state.activeSectionIndex] = newValue;
  }
}, {deep: true});

watch(() => state.show_dynamic_card_editor, (newValue) => {
  if (false === newValue) {
    document.dispatchEvent(new CustomEvent('hide.CardCategoryPopup', {
      detail: {
        product_id: state.product_id,
        card_size: state.card_size
      }
    }));
  }
}, {deep: true});


const fetchImages = () => {
  let config = {};
  if (!isUserLoggedIn) {
    config = {params: {images: GustLocalStorage.getMedia()}}
  }
  axios.get(uploadUrl, config).then(response => {
    if (response.data.data) {
      state.images = response.data.data;
    }
  })
}

const loadCardInfo = () => {
  if (state.readFromServer) {
    return;
  }
  axios.get(`dynamic-cards/${state.product_id}`).then(response => {
    let data = response.data.data;
    state.payload = data.payload;
    state.product_thumb = data.product_thumb;
    state.placeholder_im = data.placeholder_im;
    state.readFromServer = true;
  });
}

const onChangeUserOptions = (newValue) => {
  window.console.log(newValue)
  if (state.activeSectionIndex >= 0) {
    state.payload.card_items[state.activeSectionIndex] = state.activeSection;
  }
}
const messagesLinesToString = (lines: string[]) => {
  let contentEl = document.createElement('div');
  lines.forEach(line => {
    let divEl = document.createElement('div');
    if (['<br>', ''].includes(line)) {
      divEl.append(document.createElement('br'))
    } else {
      divEl.innerText = line;
    }
    contentEl.append(divEl);
  })
  return contentEl.innerHTML;
}
const onGenerateContentLeft = (args) => {
  state.innerMessage2.type = 'text';
  state.innerMessage2.message = messagesLinesToString(args.lines);
}
const onGenerateContentRight = (args) => {
  state.innerMessage.message = messagesLinesToString(args.lines);
}
const changeVideoInnerMessage = (type, value) => {
  if ('type' === type) {
    state.innerMessage2.type = value;
  } else if ('video_id' === type) {
    state.innerMessage2.video_id = value;
  } else {
    state.innerMessage2.type = '';
    state.innerMessage2.video_id = 0;
  }
}
const pxToMm = (px: number, ppi: number = 72) => {
  return Math.round(px * (25.4 / ppi));
}
const onLengthError = (error) => {
  state.showLengthError = error;
}
const isImageSelected = (image) => {
  return state.activeSection.image && image.id === state.activeSection.image.id;
}
const removeImage = () => {
  if (state.activeSection.image) {
    state.activeSection.image = {};
  }
  closeSection();
}
const onChangeEditorControls = (args) => {
  if ('emoji' === args.key) {
    document.execCommand("insertHtml", false, args.payload);
  }
}
const onSlideChange = (activeIndex) => {
  if (activeIndex !== state.slideTo) {
    state.slideTo = activeIndex;
  }
}
const handleEditSection = (section, index) => {
  state.activeSectionIndex = index;
  state.activeSection = section;
  if ('input-image' === section['section_type']) {
    state.activeSection['userOptions'] = {
      rotate: 0,
      zoom: 0,
      position: {
        top: 0, left: 0
      },
    };
  }
}
const handleSubmit = () => {
  if (state.is_card_category_popup) {
    document.dispatchEvent(new CustomEvent('submit.DynamicCard', {
      detail: {
        product_id: state.product_id,
        card_size: state.card_size,
        payload: state.payload,
        left: state.innerMessage2,
        right: state.innerMessage,
      }
    }));
    state.show_dynamic_card_editor = false;
    return;
  }
  let fieldsContainer = document.querySelector('#_dynamic_card_fields');

  fieldsContainer.querySelector('[name="_dynamic_card_payload"]').value = JSON.stringify(state.payload);
  state.payload.card_items.forEach((item, index) => {
    let inputId = `#_dynamic_card_input-${index}`
    if (['static-text', 'input-text'].indexOf(item.section_type) !== -1) {
      fieldsContainer.querySelector<HTMLInputElement>(inputId).value = item.text;
    }
    if (['static-image', 'input-image'].indexOf(item.section_type) !== -1) {
      fieldsContainer.querySelector<HTMLInputElement>(inputId).value = item.image.id || item.imageOptions.img.id;
    }
  });
  let imContainer = document.querySelector('#_inner_message_fields');
  if (imContainer) {
    imContainer.querySelector<HTMLInputElement>('#_inner_message_content').value = state.innerMessage.message;
    imContainer.querySelector<HTMLInputElement>('#_inner_message_font').value = state.innerMessage.font_family;
    imContainer.querySelector<HTMLInputElement>('#_inner_message_size').value = state.innerMessage.font_size;
    imContainer.querySelector<HTMLInputElement>('#_inner_message_align').value = state.innerMessage.alignment;
    imContainer.querySelector<HTMLInputElement>('#_inner_message_color').value = state.innerMessage.color;
  }
  let imContainer2 = document.querySelector('#_video_inner_message_fields');
  if (imContainer2 && state.innerMessage2.type) {
    imContainer2.querySelector<HTMLInputElement>('#_inner_message2_type').value = state.innerMessage2.type;
    if ('video' === state.innerMessage2.type && state.innerMessage2.video_id) {
      imContainer2.querySelector<HTMLInputElement>('#_inner_message2_video_id').value = state.innerMessage2.video_id;
      localStorage.removeItem(`__gust_video_${state.product_id}`);
    }
    if ('text' === state.innerMessage2.type && state.innerMessage2.message) {
      imContainer2.querySelector<HTMLInputElement>('#_inner_message2_content').value = state.innerMessage2.message;
      imContainer2.querySelector<HTMLInputElement>('#_inner_message2_font').value = state.innerMessage2.font_family;
      imContainer2.querySelector<HTMLInputElement>('#_inner_message2_size').value = state.innerMessage2.font_size;
      imContainer2.querySelector<HTMLInputElement>('#_inner_message2_align').value = state.innerMessage2.alignment;
      imContainer2.querySelector<HTMLInputElement>('#_inner_message2_color').value = state.innerMessage2.color;
    }
  }
  let variations_form = document.querySelector<HTMLFormElement>('form.cart');
  if (variations_form) {
    Spinner.show();
    variations_form.submit();
  }
}
const beforeSendEvent = computed(() => {
  if (window.StackonetToolkit.restNonce) {
    return {'X-WP-Nonce': window.StackonetToolkit.restNonce}
  }
  return {}
})
const finishedEvent = (fileObject, response) => {
  if (response.success) {
    state.images.unshift(response.data);
    if (!isUserLoggedIn) {
      GustLocalStorage.appendMedia(response.data.id);
    }
    // Set it to active image
    handleImageSelect(response.data);
  }
}
const onGeneratedAiImage = (data) => {
  state.images.unshift(data);
  if (!isUserLoggedIn) {
    GustLocalStorage.appendMedia(data.id);
  }
  // Set it to active image
  handleImageSelect(data);
}
const handleFileUploadFailed = (fileObject, response) => {
  if (response.message) {
    Notify.error(response.message, 'Error!');
  }
}
const handleImageSelect = (image) => {
  state.activeSection.image = {id: image.id, ...image.full}
}

onMounted(() => {
  let el = document.querySelector<HTMLDivElement>('#dynamic-card-container');
  if (el) {
    state.product_id = parseInt(el.dataset.productId);
    state.card_size = el.dataset.cardSize;
  }

  loadCardInfo();
  fetchImages();

  let btn = document.querySelector<HTMLDivElement>('.button--customize-dynamic-card');
  if (btn) {
    if (btn.hasAttribute('disabled')) {
      btn.removeAttribute('disabled');
    }
    btn.addEventListener('click', event => {
      event.preventDefault();
      state.show_dynamic_card_editor = true;
    });
  }

  document.addEventListener('show.DynamicCardModal', () => {
    state.show_dynamic_card_editor = true;
    state.is_card_category_popup = true;
  })
})
</script>
