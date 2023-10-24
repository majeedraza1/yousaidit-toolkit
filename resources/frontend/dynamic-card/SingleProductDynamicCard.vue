<template>
  <div>
    <modal
        :active="show_dynamic_card_editor"
        @close="show_dynamic_card_editor = false"
        type="box"
        content-size="full"
        :show-card-footer="false"
        :show-close-icon="false"
        class="modal--single-product-dynamic-card"
        content-class="modal-dynamic-card-content"
    >
      <div class="w-full h-full flex sm:flex-col md:flex-col lg:flex-row lg:space-x-4">
        <div class="flex flex-col flex-grow dynamic-card--canvas">
          <div class="w-full flex dynamic-card--canvas-slider">
            <swiper-slider v-if="show_dynamic_card_editor && Object.keys(payload).length"
                           :card_size="card_size" :slide-to="slideTo" @slideChange="onSlideChange">
              <template v-slot:canvas="slotProps">
                <dynamic-card-canvas
                    show-edit-icon
                    :options="`${JSON.stringify(payload)}`"
                    :active-section-index="activeSectionIndex"
                    :card-width-mm="card_dimension[0]"
                    :card-height-mm="card_dimension[1]"
                    :element-width-mm="`${pxToMm(slotProps.sizes.width)}`"
                    :element-height-mm="`${pxToMm(slotProps.sizes.height)}`"
                    @edit:layer="(event) => handleEditSection(event.detail.section,event.detail.index)"
                ></dynamic-card-canvas>
              </template>
              <template v-slot:video-message>
                <video-inner-message
                    :product_id="product_id"
                    :inner-message="innerMessage2"
                    :card_size="card_size"
                    @change="changeVideoInnerMessage"
                    :open-ai-editable="1===slideTo"
                />
              </template>
              <template v-slot:inner-message>
                <div class="dynamic-card--editable-content-container">
                  <editable-content
                      placeholder="Please click here to write your message"
                      :font-family="innerMessage.font_family"
                      :font-size="innerMessage.font_size"
                      :text-align="innerMessage.alignment"
                      :color="innerMessage.color"
                      v-model="innerMessage.message"
                      :card-size="card_size"
                      @lengthError="onLengthError"
                      :open-ai-editable="2===slideTo"
                  />
                  <div v-if="showLengthError" class="has-error p-2 my-4 absolute bottom-0">
                    Oops... your message is too long, please keep inside the box.
                  </div>
                </div>
              </template>
            </swiper-slider>
          </div>
          <div class="swiper-thumbnail mt-4 dynamic-card--canvas-thumb bg-gray-200">
            <div class="flex space-x-4 p-2 justify-center">
              <image-container container-width="64px" class="bg-gray-100" @click.native="slideTo = 0"
                               :class="{'border border-solid border-primary':slideTo === 0}">
                <img :src="product_thumb" alt="">
              </image-container>
              <image-container container-width="64px" class="bg-gray-100" @click.native="slideTo = 1"
                               :class="{'border border-solid border-primary':slideTo === 1}">
                <img :src="placeholder_im_left" alt=""/>
              </image-container>
              <image-container container-width="64px" class="bg-gray-100" @click.native="slideTo = 2"
                               :class="{'border border-solid border-primary':slideTo === 2}">
                <img :src="placeholder_im_right" alt=""/>
              </image-container>
            </div>
          </div>
        </div>
        <div
            class="flex flex-col justify-between bg-gray-100 p-2 dynamic-card--controls lg:border border-solid border-gray-100">
          <div v-if="activeSectionIndex === -1 && slideTo === 0">
            <div><strong>Help tips:</strong></div>
            <div class="flex">
              Click on icon (
              <icon-container size="medium">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px">
                  <rect fill="none" height="24" width="24"></rect>
                  <path
                      d="M3,10h11v2H3V10z M3,8h11V6H3V8z M3,16h7v-2H3V16z M18.01,12.87l0.71-0.71c0.39-0.39,1.02-0.39,1.41,0l0.71,0.71 c0.39,0.39,0.39,1.02,0,1.41l-0.71,0.71L18.01,12.87z M17.3,13.58l-5.3,5.3V21h2.12l5.3-5.3L17.3,13.58z"></path>
                </svg>
              </icon-container>
              ) to customize text.
            </div>
            <div class="flex">
              Click on icon (
              <icon-container size="medium">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24px" height="24px">
                  <rect fill="none" height="24" width="24"></rect>
                  <path
                      d="M18.85,10.39l1.06-1.06c0.78-0.78,0.78-2.05,0-2.83L18.5,5.09c-0.78-0.78-2.05-0.78-2.83,0l-1.06,1.06L18.85,10.39z M14.61,11.81L7.41,19H6v-1.41l7.19-7.19L14.61,11.81z M13.19,7.56L4,16.76V21h4.24l9.19-9.19L13.19,7.56L13.19,7.56z M19,17.5 c0,2.19-2.54,3.5-5,3.5c-0.55,0-1-0.45-1-1s0.45-1,1-1c1.54,0,3-0.73,3-1.5c0-0.47-0.48-0.87-1.23-1.2l1.48-1.48 C18.32,15.45,19,16.29,19,17.5z M4.58,13.35C3.61,12.79,3,12.06,3,11c0-1.8,1.89-2.63,3.56-3.36C7.59,7.18,9,6.56,9,6 c0-0.41-0.78-1-2-1C5.74,5,5.2,5.61,5.17,5.64C4.82,6.05,4.19,6.1,3.77,5.76C3.36,5.42,3.28,4.81,3.62,4.38C3.73,4.24,4.76,3,7,3 c2.24,0,4,1.32,4,3c0,1.87-1.93,2.72-3.64,3.47C6.42,9.88,5,10.5,5,11c0,0.31,0.43,0.6,1.07,0.86L4.58,13.35z"></path>
                </svg>
              </icon-container>
              ) to customize image.
            </div>
          </div>
          <template v-if="activeSectionIndex >= 0">
            <div v-if="activeSection.section_type === 'input-text'" class="mb-4">
              <input type="text" v-model="activeSection.text" :placeholder="activeSection.placeholder">
              <shapla-button outline size="small" @click="activeSection.text = ''">Clear</shapla-button>
              <shapla-button outline size="small" theme="primary" @click="closeSection">Confirm
              </shapla-button>
            </div>
            <div v-if="activeSection.section_type === 'input-image'" class="mb-4">
              <tabs fullwidth centered>
                <tab name="Images" selected>
                  <div class="flex flex-wrap uploade-image-thumbnail-container">
                    <template v-if="images.length">
                      <div v-for="_img in images" class="w-1/4 p-1">
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
                </tab>
                <tab name="Upload">
                  <file-uploader
                      :url="uploadUrl"
                      @before:send="beforeSendEvent"
                      @success="finishedEvent"
                      @failed="handleFileUploadFailed"
                  />
                </tab>
              </tabs>
              <div class="relative border border-solid mt-6"
                   v-if="activeSection.image && activeSection.image.src">
                <img :src="activeSection.image.src" alt=""/>
                <delete-icon class="absolute -top-2 -right-2" @click="removeImage"/>
              </div>
            </div>
          </template>
          <div v-if="slideTo === 2">
            <editor-controls
                v-model="innerMessage"
                @change="onChangeEditorControls"
                @generateContent="onGenerateContentRight"
            />
          </div>
          <div v-if="slideTo === 1">
            <editor-controls
                v-model="innerMessage2"
                @change="onChangeEditorControls"
                @generateContent="onGenerateContentLeft"
            />
          </div>
          <div class="space-y-2">
            <shapla-button theme="primary" size="small" fullwidth outline
                           @click="show_dynamic_card_editor = false">
              Cancel
            </shapla-button>
            <shapla-button theme="primary" size="medium" fullwidth @click="handleSubmit">
              Add to basket
            </shapla-button>
          </div>
        </div>
      </div>
    </modal>
    <notification :options="notifications"/>
    <spinner :active="loading"/>
    <confirm-dialog/>
  </div>
</template>

<script>
import axios from "axios";
import {mapState} from "vuex";
import {
  ConfirmDialog,
  deleteIcon,
  FileUploader,
  iconContainer,
  imageContainer,
  modal,
  notification,
  shaplaButton,
  spinner,
  tab,
  tabs
} from "shapla-vue-components";
import CardWebViewer from "@/components/DynamicCardPreview/CardWebViewer";
import EditableContent from "@/frontend/inner-message/EditableContent";
import EditorControls from "@/frontend/inner-message/EditorControls";
import SwiperSlider from "@/frontend/dynamic-card/SwiperSlider.vue";
import GustLocalStorage from "@/frontend/dynamic-card/GustLocalStorage.ts";
import VideoInnerMessage from "@/frontend/dynamic-card/VideoInnerMessage";

export default {
  name: "SingleProductDynamicCard",
  components: {
    EditableContent, CardWebViewer, modal, shaplaButton, iconContainer, SwiperSlider, imageContainer, spinner,
    VideoInnerMessage, EditorControls, FileUploader, tabs, tab, deleteIcon, notification, ConfirmDialog
  },
  data() {
    return {
      slideTo: 0,
      product_id: 0,
      card_size: '',
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
      notifications: {}
    }
  },
  computed: {
    ...mapState(['loading']),
    placeholder_im_left() {
      return window.StackonetToolkit.placeholderUrlIML;
    },
    placeholder_im_right() {
      return window.StackonetToolkit.placeholderUrlIMR;
    },
    uploadUrl() {
      return StackonetToolkit.restRoot + '/dynamic-cards/media';
    },
    isUserLoggedIn() {
      return window.StackonetToolkit.isUserLoggedIn || false;
    },
    loginUrl() {
      return window.StackonetToolkit.loginUrl ?? '';
    },
    card_dimension() {
      const card_sizes = {
        a4: [426, 303],
        a5: [303, 216],
        a6: [216, 154],
        square: [306, 156],
      }
      if (Object.keys(card_sizes).indexOf(this.card_size) === -1) {
        return [0, 0];
      }
      let dimension = card_sizes[this.card_size];
      return [(dimension[0] / 2) + 1, dimension[1]];
    },
  },
  watch: {
    slideTo() {
      this.closeSection();
    },
    show_dynamic_card_editor(newValue) {
      if (false === newValue) {
        document.dispatchEvent(new CustomEvent('hide.CardCategoryPopup', {
          detail: {product_id: this.product_id, card_size: this.card_size}
        }));
      }
    },
    activeSection: {
      deep: true,
      handler(newValue) {
        if (this.activeSectionIndex >= 0) {
          this.payload.card_items[this.activeSectionIndex] = newValue;
        }
      }
    }
  },
  methods: {

    messagesLinesToString(lines) {
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
    },
    onGenerateContentLeft(args) {
      this.innerMessage2.type = 'text';
      this.innerMessage2.message = this.messagesLinesToString(args.lines);
    },
    onGenerateContentRight(args) {
      this.innerMessage.message = this.messagesLinesToString(args.lines);
    },
    changeVideoInnerMessage(type, value) {
      if ('type' === type) {
        this.innerMessage2.type = value;
      } else if ('video_id' === type) {
        this.innerMessage2.video_id = value;
      } else {
        this.innerMessage2.type = '';
        this.innerMessage2.video_id = 0;
      }
    },
    pxToMm(px) {
      return Math.round(px * 0.2645833333);
    },
    onLengthError(error) {
      this.showLengthError = error;
    },
    isImageSelected(image) {
      return this.activeSection.image && image.id === this.activeSection.image.id;
    },
    closeSection() {
      this.activeSection = {};
      this.activeSectionIndex = -1;
    },
    removeImage() {
      if (this.activeSection.image) {
        this.activeSection.image = {};
      }
      this.closeSection();
    },
    onChangeEditorControls(args) {
      if ('emoji' === args.key) {
        document.execCommand("insertHtml", false, args.payload);
      }
    },
    onSlideChange(activeIndex) {
      if (activeIndex !== this.slideTo) {
        this.slideTo = activeIndex;
      }
    },
    handleEditSection(section, index) {
      this.activeSectionIndex = index;
      this.activeSection = section;
    },
    handleSubmit() {
      if (this.is_card_category_popup) {
        document.dispatchEvent(new CustomEvent('submit.DynamicCard', {
          detail: {
            product_id: this.product_id,
            card_size: this.card_size,
            payload: this.payload,
            left: this.innerMessage2,
            right: this.innerMessage,
          }
        }));
        this.show_dynamic_card_editor = false;
        return;
      }
      let fieldsContainer = document.querySelector('#_dynamic_card_fields');
      this.payload.card_items.forEach((item, index) => {
        let inputId = `#_dynamic_card_input-${index}`
        if (['static-text', 'input-text'].indexOf(item.section_type) !== -1) {
          fieldsContainer.querySelector(inputId).value = item.text;
        }
        if (['static-image', 'input-image'].indexOf(item.section_type) !== -1) {
          fieldsContainer.querySelector(inputId).value = item.image.id || item.imageOptions.img.id;
        }
      });
      let imContainer = document.querySelector('#_inner_message_fields');
      if (imContainer) {
        imContainer.querySelector('#_inner_message_content').value = this.innerMessage.message;
        imContainer.querySelector('#_inner_message_font').value = this.innerMessage.font_family;
        imContainer.querySelector('#_inner_message_size').value = this.innerMessage.font_size;
        imContainer.querySelector('#_inner_message_align').value = this.innerMessage.alignment;
        imContainer.querySelector('#_inner_message_color').value = this.innerMessage.color;
      }
      let imContainer2 = document.querySelector('#_video_inner_message_fields');
      if (imContainer2 && this.innerMessage2.type) {
        imContainer2.querySelector('#_inner_message2_type').value = this.innerMessage2.type;
        if ('video' === this.innerMessage2.type && this.innerMessage2.video_id) {
          imContainer2.querySelector('#_inner_message2_video_id').value = this.innerMessage2.video_id;
          localStorage.removeItem(`__gust_video_${this.product_id}`);
        }
        if ('text' === this.innerMessage2.type && this.innerMessage2.message) {
          imContainer2.querySelector('#_inner_message2_content').value = this.innerMessage2.message;
          imContainer2.querySelector('#_inner_message2_font').value = this.innerMessage2.font_family;
          imContainer2.querySelector('#_inner_message2_size').value = this.innerMessage2.font_size;
          imContainer2.querySelector('#_inner_message2_align').value = this.innerMessage2.alignment;
          imContainer2.querySelector('#_inner_message2_color').value = this.innerMessage2.color;
        }
      }
      let variations_form = document.querySelector('form.cart');
      if (variations_form) {
        this.$store.commit('SET_LOADING_STATUS', true);
        variations_form.submit();
      }
    },
    loadCardInfo() {
      if (this.readFromServer) {
        return;
      }
      axios.get(StackonetToolkit.restRoot + `/dynamic-cards/${this.product_id}`).then(response => {
        let data = response.data.data;
        this.payload = data.payload;
        this.product_thumb = data.product_thumb;
        this.placeholder_im = data.placeholder_im;
        this.readFromServer = true;
      });
    },
    beforeSendEvent(xhr) {
      if (window.StackonetToolkit.restNonce) {
        xhr.setRequestHeader('X-WP-Nonce', window.StackonetToolkit.restNonce);
      }
    },
    finishedEvent(fileObject, response) {
      if (response.success) {
        this.images.unshift(response.data);
        if (!this.isUserLoggedIn) {
          GustLocalStorage.appendMedia(response.data.id);
        }
      }
    },
    handleFileUploadFailed(fileObject, response) {
      if (response.message) {
        this.notifications = {type: 'error', title: 'Error!', message: response.message};
      }
    },
    fetchImages() {
      let config = {};
      if (!this.isUserLoggedIn) {
        config = {params: {images: GustLocalStorage.getMedia()}}
      }
      axios.get(this.uploadUrl, config).then(response => {
        if (response.data.data) {
          this.images = response.data.data;
        }
      })
    },
    handleImageSelect(image) {
      this.activeSection.image = {id: image.id, ...image.full}
    },
  },
  mounted() {
    let el = document.querySelector('#dynamic-card-container');
    if (el) {
      this.product_id = parseInt(el.dataset.productId);
      this.card_size = el.dataset.cardSize;
    }

    this.loadCardInfo();
    this.fetchImages();

    let btn = document.querySelector('.button--customize-dynamic-card');
    if (btn) {
      if (btn.hasAttribute('disabled')) {
        btn.removeAttribute('disabled');
      }
      btn.addEventListener('click', event => {
        event.preventDefault();
        this.show_dynamic_card_editor = true;
      });
    }

    document.addEventListener('show.DynamicCardModal', () => {
      this.show_dynamic_card_editor = true;
      this.is_card_category_popup = true;
      window.console.log('Card Category Popup: show dynamic card modal.');
    })
  }
}
</script>

<style lang="scss">
body.is-dynamic-card-product {
  .button--customize-dynamic-card {
    width: 100%;
    margin-top: 1rem;
  }

  .quantity,
  .single_add_to_cart_button {
    display: none !important;
  }
}

.dynamic-card--editable-content-container {
  display: flex;
  height: 100%;
  justify-content: center;
  align-items: center;
  border: 1px solid #f5f5f5;
}

.uploade-image-thumbnail-container {

}

.modal--single-product-dynamic-card {
  box-sizing: border-box;

  *, *:before, *:after {
    box-sizing: border-box;
  }

  .card-preview-canvas {
    border: 1px solid #f5f5f5;
  }

  .modal-dynamic-card-content {
    border-radius: 0;
    height: 100vh;
    max-height: 100vh;
    padding: 0 !important;
    width: 100vw;

    .admin-bar & {
      margin-top: 32px;
      height: calc(100vh - 32px);

      @media screen and (max-width: 782px) {
        margin-top: 46px;
        height: calc(100vh - 46px);
      }
    }
  }

  @media screen and (min-width: 1024px) {
    .modal-dynamic-card-content {
      overflow: hidden;
    }
    .dynamic-card--canvas {
      height: calc(100vh - 2rem); // excluding padding of modal box
      width: calc(100% - 360px);

      &-slider {
        height: calc(100vh - (2rem + 100px + 1rem)); // excluding padding of modal box
      }

      &-thumb {
        height: 100px;
      }
    }

    .dynamic-card--controls {
      width: 360px;
    }
  }
}
</style>
