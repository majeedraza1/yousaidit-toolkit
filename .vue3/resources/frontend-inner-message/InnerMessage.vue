<template>
  <div class="yousaidit-inner-message">
    <ShaplaModal :active="state.showModal" type="box" content-size="full" @close="closeModal"
                 :show-close-icon="false" :close-on-background-click="false"
                 :class="{
				      'modal--inner-message-compose':true,
				      'has-multi-compose modal--single-product-dynamic-card':hasBothSideContent|| state.page === 'single-product'
			     }"
                 :content-class="hasBothSideContent || state.page === 'single-product'?'modal-dynamic-card-content':''">
      <div v-show="hasBothSideContent || state.page === 'single-product'">
        <multi-compose
            v-if="state.showModal"
            :active="state.showModal"
            :left-message="state.videoInnerMessage"
            :right-message="state.innerMessage"
            :card-size="state.card_size"
            :product_id="state.product_id"
            @close="closeModal"
            @submit="onUpdateMultiCompose"
        />
      </div>
      <compose v-show="state.hasLeftPageContent && !state.hasRightPageContent" :active="state.showModal"
               :inner-message="state.videoInnerMessage" :card-size="state.card_size"
               :btn-text="btnText" @close="closeModal" @submit="(_data) => submit(_data,'left')"/>
      <compose v-show="state.hasRightPageContent && !state.hasLeftPageContent" :active="state.showModal"
               :inner-message="state.innerMessage" :card-size="state.card_size"
               :btn-text="btnText" @close="closeModal" @submit="(_data) => submit(_data,'right')"/>
    </ShaplaModal>
    <ShaplaModal v-if="state.showViewModal" :active="true" type="card" title="Preview" content-size="full"
                 @close="closeViewModal" :show-card-footer="false">
      <template v-if="hasBothSideContent">
        <div class="w-full flex dynamic-card--canvas-slider"
             style="height: calc(100vh - 150px);overflow: hidden">
          <SwiperSlider :card_size="state.card_size" :slide-to="state.slideTo" :hide-canvas="true">
            <template v-slot:video-message>
              <editable-content
                  :editable="false"
                  class="shadow-lg mb-4 bg-white"
                  :font-family="state.videoInnerMessage.font"
                  :font-size="state.videoInnerMessage.size"
                  :text-align="state.videoInnerMessage.align"
                  :color="state.videoInnerMessage.color"
                  v-model="state.videoInnerMessage.content"
                  :card-size="state.card_size"
              />
            </template>
            <template v-slot:inner-message>
              <editable-content
                  :editable="false"
                  class="shadow-lg mb-4 bg-white"
                  :font-family="state.innerMessage.font"
                  :font-size="state.innerMessage.size"
                  :text-align="state.innerMessage.align"
                  :color="state.innerMessage.color"
                  v-model="state.innerMessage.content"
                  :card-size="state.card_size"
              />
            </template>
          </SwiperSlider>
        </div>
      </template>
      <template v-else-if="state.hasLeftPageContent">
        <div style="max-width: 400px;" class="ml-auto mr-auto">
          <editable-content
              :editable="false"
              class="shadow-lg mb-4 bg-white"
              :font-family="state.videoInnerMessage.font"
              :font-size="state.videoInnerMessage.size"
              :text-align="state.videoInnerMessage.align"
              :color="state.videoInnerMessage.color"
              v-model="state.videoInnerMessage.content"
              :card-size="state.card_size"
          />
        </div>
      </template>
      <template v-else-if="state.hasRightPageContent">
        <div style="max-width: 400px;" class="ml-auto mr-auto">
          <editable-content
              :editable="false"
              class="shadow-lg mb-4 bg-white"
              :font-family="state.innerMessage.font"
              :font-size="state.innerMessage.size"
              :text-align="state.innerMessage.align"
              :color="state.innerMessage.color"
              v-model="state.innerMessage.content"
              :card-size="state.card_size"
          />
        </div>
      </template>
    </ShaplaModal>
  </div>
</template>

<script lang="ts" setup>
import axios from "../utils/axios.ts";
import Compose from "./components/Compose.vue";
import MultiCompose from "./components/MultiCompose.vue";
import EditableContent from "./components/EditableContent.vue";
import SwiperSlider from "./components/SwiperSlider.vue";
import {Notify, Spinner} from "@shapla/vanilla-components";
import {ShaplaModal} from "@shapla/vue-components";
import {computed, onMounted, reactive} from "vue";
import {
  InnerMessageCartItemDataInterface,
  InnerMessageCartItemPropsInterface,
  LeftAndRightInnerMessagePropsInterface
} from "../interfaces/inner-message.ts";

const defaultData = () => {
  return {
    showModal: false,
    isCardCategoryPopup: false,
    card_size: '',
    showViewModal: false,
    innerMessage: {},
    videoInnerMessage: {},
    hasRightPageContent: false,
    hasLeftPageContent: false,
    slideTo: 0,
    page: 'single-product',
    cartkey: '',
    canvas_height: 0,
    canvas_width: 0,
  }
}

const state = reactive<{
  showModal: boolean,
  isCardCategoryPopup: boolean,
  product_id: number,
  card_size: string,
  showViewModal: boolean,
  innerMessage: InnerMessageCartItemPropsInterface,
  videoInnerMessage: InnerMessageCartItemPropsInterface,
  hasRightPageContent: boolean,
  hasLeftPageContent: boolean,
  slideTo: number,
  page: 'single-product' | 'cart',
  cartkey: string,
  canvas_height: number,
  canvas_width: number,
}>({
  showModal: false,
  isCardCategoryPopup: false,
  product_id: 0,
  card_size: '',
  showViewModal: false,
  innerMessage: null,
  videoInnerMessage: null,
  hasRightPageContent: false,
  hasLeftPageContent: false,
  slideTo: 0,
  page: 'single-product',
  cartkey: '',
  canvas_height: 0,
  canvas_width: 0,
})

const hasBothSideContent = computed(() => state.hasLeftPageContent && state.hasRightPageContent)
const btnText = computed(() => state.page === 'cart' ? 'Update' : 'Add to Basket')

const readInnerMessageDateForCardItem = (dataset) => {
  Spinner.show();
  let data = {action: 'get_cart_item_info', item_key: dataset['cartItemKey'], mode: dataset['mode']}
  axios
      .get(window.StackonetToolkit.ajaxUrl, {params: data})
      .then(response => {
        const _data = response.data as InnerMessageCartItemDataInterface;
        if (_data._inner_message && _data._inner_message.content) {
          state.innerMessage = _data._inner_message;
          state.hasRightPageContent = true;
        }
        if (_data._video_inner_message && _data._video_inner_message.content) {
          state.videoInnerMessage = _data._video_inner_message;
          state.hasLeftPageContent = true;
        }

        if (_data._card_size) {
          state.card_size = _data._card_size;
        } else if (_data.variation["attribute_pa_size"]) {
          state.card_size = _data.variation["attribute_pa_size"];
        } else {
          state.card_size = 'square';
        }

        if (data.mode === 'view') {
          state.showViewModal = true;
        } else if (data.mode === 'edit') {
          state.showModal = true;
          state.page = 'cart';
          state.cartkey = _data.key;
        }
      })
      .catch(error => {
        console.log(error);
      })
      .finally(() => {
        Spinner.hide();
      });
}


const closeViewModal = () => {
  state.showViewModal = false;
  if (document.body.classList.contains('has-shapla-modal')) {
    document.body.classList.remove('has-shapla-modal');
  }
  Object.assign(state, defaultData());
}
const closeModal = () => {
  state.showModal = false;
  let checkbox = document.querySelector<HTMLInputElement>('#custom_message');
  if (checkbox) {
    checkbox.checked = false;
  }
  Object.assign(state, defaultData());
}


const onUpdatePopupItemInfo = (data: LeftAndRightInnerMessagePropsInterface) => {
  document.body.dispatchEvent(new CustomEvent('update.CardCategoryPopup', {detail: data}));
  state.showModal = false;
}
const onUpdateCartItemInfo = (data: LeftAndRightInnerMessagePropsInterface) => {
  jQuery.ajax({
    url: window.StackonetToolkit.ajaxUrl,
    method: 'POST',
    data: {
      action: 'update_cart_item_info',
      item_key: state.cartkey,
      messages: data
    },
    success: function () {
      window.location.reload();
    }
  })
}
const onUpdateSingleProductInnerMessage = (data: LeftAndRightInnerMessagePropsInterface) => {
  let fieldsContainer = document.querySelector('#_inner_message_fields');
  if (fieldsContainer) {
    const rightPageContent = data.right;
    fieldsContainer.querySelector<HTMLInputElement>('#_inner_message_content').value = rightPageContent.message;
    fieldsContainer.querySelector<HTMLInputElement>('#_inner_message_font').value = rightPageContent.font_family;
    fieldsContainer.querySelector<HTMLInputElement>('#_inner_message_size').value = rightPageContent.font_size;
    fieldsContainer.querySelector<HTMLInputElement>('#_inner_message_align').value = rightPageContent.alignment;
    fieldsContainer.querySelector<HTMLInputElement>('#_inner_message_color').value = rightPageContent.color;
  }

  let imContainer2 = document.querySelector('#_video_inner_message_fields');
  if (imContainer2) {
    const leftPageContent = data.left;
    imContainer2.querySelector<HTMLInputElement>('#_inner_message2_type').value = leftPageContent.type;
    imContainer2.querySelector<HTMLInputElement>('#_inner_message2_video_id').value = leftPageContent.video_id.toString();
    imContainer2.querySelector<HTMLInputElement>('#_inner_message2_content').value = leftPageContent.message;
    imContainer2.querySelector<HTMLInputElement>('#_inner_message2_font').value = leftPageContent.font_family;
    imContainer2.querySelector<HTMLInputElement>('#_inner_message2_size').value = leftPageContent.font_size;
    imContainer2.querySelector<HTMLInputElement>('#_inner_message2_align').value = leftPageContent.alignment;
    imContainer2.querySelector<HTMLInputElement>('#_inner_message2_color').value = leftPageContent.color;
  }

  let variations_form = document.querySelector<HTMLFormElement>('form.cart');
  if (variations_form) {
    Spinner.show();
    state.showModal = false;
    variations_form.submit();
  }
}


const submit = (data, side = 'right') => {
  let message = '';
  if (!data.message.length) {
    message = "Add some message";
  }
  if (data.message.length && data.message.length < 1) {
    message = "Message too short.";
  }

  if (message.length) {
    Notify.error(message, 'Error!');
  }
  state.showModal = false;
  let fieldsContainer = document.querySelector('#_inner_message_fields');
  if (fieldsContainer) {
    fieldsContainer.querySelector<HTMLInputElement>('#_inner_message_content').value = data.message;
    fieldsContainer.querySelector<HTMLInputElement>('#_inner_message_font').value = data.font_family;
    fieldsContainer.querySelector<HTMLInputElement>('#_inner_message_size').value = data.font_size;
    fieldsContainer.querySelector<HTMLInputElement>('#_inner_message_align').value = data.alignment;
    fieldsContainer.querySelector<HTMLInputElement>('#_inner_message_color').value = data.color;
  }

  let variations_form = document.querySelector<HTMLFormElement>('form.cart');
  if (variations_form) {
    Spinner.show();
    let form = new FormData(variations_form), data = {};
    for (const [key, value] of form.entries()) {
      if (key === "attribute_pa_size") {
        state.card_size = value as string;
      }
      data[`${key}`] = value;
    }
    variations_form.submit();
  }

  if (state.page === 'cart') {
    window.jQuery.ajax({
      url: window.StackonetToolkit.ajaxUrl,
      method: 'POST',
      data: {
        action: 'set_cart_item_info',
        page_side: side,
        item_key: state.cartkey,
        inner_message: {
          content: data.message,
          font: data.font_family,
          size: data.font_size,
          align: data.alignment,
          color: data.color,
        }
      },
      success: function () {
        window.location.reload();
      }
    })
  }
}
const onUpdateMultiCompose = (data: LeftAndRightInnerMessagePropsInterface) => {
  if ('cart' === state.page) {
    return onUpdateCartItemInfo(data);
  }
  if (state.isCardCategoryPopup) {
    return onUpdatePopupItemInfo(data);
  }

  if ('single-product' === state.page) {
    return onUpdateSingleProductInnerMessage(data);
  }
}

onMounted(() => {
  let customMessage = document.querySelector<HTMLInputElement>('#custom_message');
  if (customMessage) {
    customMessage.addEventListener('change', event => {
      state.showModal = !!(event.target as HTMLInputElement).checked;
    });
    customMessage.addEventListener('blur', event => {
      state.showModal = !!(event.target as HTMLInputElement).checked;
    });
  }
  let btnIM = document.querySelector<HTMLButtonElement>('.button--add-inner-message');
  if (btnIM) {
    btnIM.addEventListener('click', event => {
      event.preventDefault();
      state.showModal = true;
      let variations_form = document.querySelector<HTMLFormElement>('form.variations_form') ||
          document.querySelector<HTMLFormElement>('form.cart');
      if (variations_form) {
        let form = new FormData(variations_form);
        for (const [key, value] of form.entries()) {
          if (key === "attribute_pa_size") {
            state.card_size = value.length ? value as string : 'square';
          }
          if (key === 'add-to-cart') {
            state.product_id = parseInt(value as string);
          }
        }
      } else {
        state.card_size = 'square';
      }
    });
  }
  document.addEventListener('click', event => {
    let dataset = (event.target as HTMLElement).dataset;
    if (dataset['cartItemKey']) {
      readInnerMessageDateForCardItem(dataset);
    }
  });
  document.addEventListener('click', (event: MouseEvent) => {

    const form = (event.target as HTMLElement).closest('.card-popup-form')
    if ((event.target as HTMLElement).closest('.card-popup-add-a-message') && form) {
      event.preventDefault();
      state.product_id = parseInt(form.querySelector<HTMLInputElement>('[name="product_id"]').value);
      state.card_size = form.querySelector<HTMLInputElement>('[name="attribute_pa_size"]').value;
      state.showModal = true;
      state.isCardCategoryPopup = true;
    }
  });
})
</script>
