<template>
    <div class="yousaidit-inner-message">
        <modal :active="showModal" type="box" content-size="full" @close="closeModal"
               :show-close-icon="false" :close-on-background-click="false"
               :class="{
				      'modal--inner-message-compose':true,
				      'has-multi-compose modal--single-product-dynamic-card':hasBothSideContent|| page === 'single-product'
			     }"
               :content-class="hasBothSideContent || page === 'single-product'?'modal-dynamic-card-content':''">
            <div v-show="hasBothSideContent || page === 'single-product'">
                <multi-compose
                        v-if="showModal"
                        :active="showModal"
                        :left-message="videoInnerMessage"
                        :right-message="innerMessage"
                        :card-size="card_size"
                        :product_id="product_id"
                        @close="closeModal"
                        @submit="onUpdateMultiCompose"
                />
            </div>
            <compose v-show="hasLeftPageContent && !hasRightPageContent" :active="showModal"
                     :inner-message="videoInnerMessage" :card-size="card_size"
                     :btn-text="btnText" @close="closeModal" @submit="(_data) => submit(_data,'left')"/>
            <compose v-show="hasRightPageContent && !hasLeftPageContent" :active="showModal"
                     :inner-message="innerMessage" :card-size="card_size"
                     :btn-text="btnText" @close="closeModal" @submit="(_data) => submit(_data,'right')"/>
        </modal>
        <modal v-if="showViewModal" :active="true" type="card" title="Preview" content-size="full"
               @close="closeViewModal" :show-card-footer="false">
            <template v-if="hasBothSideContent">
                <div class="w-full flex dynamic-card--canvas-slider"
                     style="height: calc(100vh - 150px);overflow: hidden">
                    <swiper-slider :card_size="card_size" :slide-to="slideTo" :hide-canvas="true"
                                   @slideChange="onSlideChange">
                        <template v-slot:video-message>
                            <editable-content
                                    :editable="false"
                                    class="shadow-lg sm:mb-4 sm:bg-white"
                                    :font-family="videoInnerMessage.font"
                                    :font-size="videoInnerMessage.size"
                                    :text-align="videoInnerMessage.align"
                                    :color="videoInnerMessage.color"
                                    v-model="videoInnerMessage.content"
                                    :card-size="card_size"
                            />
                        </template>
                        <template v-slot:inner-message>
                            <editable-content
                                    :editable="false"
                                    class="shadow-lg sm:mb-4 sm:bg-white"
                                    :font-family="innerMessage.font"
                                    :font-size="innerMessage.size"
                                    :text-align="innerMessage.align"
                                    :color="innerMessage.color"
                                    v-model="innerMessage.content"
                                    :card-size="card_size"
                            />
                        </template>
                    </swiper-slider>
                </div>
            </template>
            <template v-else-if="hasLeftPageContent">
                <div style="max-width: 400px;" class="ml-auto mr-auto">
                    <editable-content
                            :editable="false"
                            class="shadow-lg sm:mb-4 sm:bg-white"
                            :font-family="videoInnerMessage.font"
                            :font-size="videoInnerMessage.size"
                            :text-align="videoInnerMessage.align"
                            :color="videoInnerMessage.color"
                            v-model="videoInnerMessage.content"
                            :card-size="card_size"
                    />
                </div>
            </template>
            <template v-else-if="hasRightPageContent">
                <div style="max-width: 400px;" class="ml-auto mr-auto">
                    <editable-content
                            :editable="false"
                            class="shadow-lg sm:mb-4 sm:bg-white"
                            :font-family="innerMessage.font"
                            :font-size="innerMessage.size"
                            :text-align="innerMessage.align"
                            :color="innerMessage.color"
                            v-model="innerMessage.content"
                            :card-size="card_size"
                    />
                </div>
            </template>
        </modal>
        <confirm-dialog/>
        <notification :options="notification"/>
        <spinner :active="loading"/>
    </div>
</template>

<script>
import {mapState} from 'vuex';
import axios from "axios";
import {ConfirmDialog, modal, notification, shaplaButton, spinner} from 'shapla-vue-components';
import Compose from "./Compose.vue";
import MultiCompose from "./MultiCompose.vue";
import EditableContent from "@/frontend/inner-message/EditableContent";
import SwiperSlider from "@/frontend/dynamic-card/SwiperSlider.vue";
import VideoInnerMessage from "@/frontend/dynamic-card/VideoInnerMessage.vue";

const defaultData = () => {
    return {
        showModal: false,
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

export default {
    name: "InnerMessage",
    components: {
        SwiperSlider, EditableContent, Compose, spinner, notification, ConfirmDialog, modal, shaplaButton,
        VideoInnerMessage, MultiCompose
    },
    data() {
        return {
            showModal: false,
            product_id: 0,
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
        };
    },
    computed: {
        ...mapState(['loading', 'notification']),
        hasBothSideContent() {
            return this.hasLeftPageContent && this.hasRightPageContent;
        },
        btnText() {
            return this.page === 'cart' ? 'Update' : 'Add to Basket';
        }
    },
    mounted() {
        let customMessage = document.querySelector('#custom_message');
        if (customMessage) {
            customMessage.addEventListener('change', event => {
                this.showModal = event.target.checked;
            });
            customMessage.addEventListener('blur', event => {
                this.showModal = event.target.checked;
            });
        }
        let btnIM = document.querySelector('.button--add-inner-message');
        if (btnIM) {
            btnIM.addEventListener('click', event => {
                event.preventDefault();
                this.showModal = true;
                let variations_form = document.querySelector('form.variations_form') || document.querySelector('form.cart');
                if (variations_form) {
                    let form = new FormData(variations_form);
                    for (const [key, value] of form.entries()) {
                        if (key === "attribute_pa_size") {
                            this.card_size = value.length ? value : 'square';
                        }
                        if (key === 'add-to-cart') {
                            this.product_id = parseInt(value);
                        }
                    }
                } else {
                    this.card_size = 'square';
                }
            });
        }
        document.addEventListener('click', event => {
            let dataset = event.target.dataset;
            if (dataset['cartItemKey']) {
                this.readInnerMessageDateForCardItem(dataset);
            }
        });
    },
    methods: {
        changeVideoInnerMessage(type, value) {
            if ('type' === type) {
                this.videoInnerMessage.type = value;
            } else if ('video_id' === type) {
                this.videoInnerMessage.video_id = value;
            } else {
                this.videoInnerMessage.type = '';
                this.videoInnerMessage.video_id = 0;
            }
        },
        readInnerMessageDateForCardItem(dataset) {
            this.$store.commit('SET_LOADING_STATUS', true);
            let data = {action: 'get_cart_item_info', item_key: dataset['cartItemKey'], mode: dataset['mode']}
            axios.get(StackonetToolkit.ajaxUrl, {params: data})
                .then(response => {
                    const _data = response.data;
                    this.$store.commit('SET_LOADING_STATUS', false);
                    if (_data._inner_message && _data._inner_message.content.length) {
                        this.innerMessage = _data._inner_message;
                        this.hasRightPageContent = true;
                    }
                    if (_data._video_inner_message && _data._video_inner_message.content.length) {
                        this.videoInnerMessage = _data._video_inner_message;
                        this.hasLeftPageContent = true;
                    }

                    if (_data._card_size) {
                        this.card_size = _data._card_size;
                    } else if (_data.variation["attribute_pa_size"]) {
                        this.card_size = _data.variation["attribute_pa_size"];
                    } else {
                        this.card_size = 'square';
                    }

                    if (data.mode === 'view') {
                        this.showViewModal = true;
                    } else if (data.mode === 'edit') {
                        this.showModal = true;
                        this.page = 'cart';
                        this.cartkey = _data.key;
                    }
                })
                .catch(error => {
                    this.$store.commit('SET_LOADING_STATUS', false);
                    console.log(error);
                });
        },
        onSlideChange() {
        },
        closeViewModal() {
            this.showViewModal = false;
            if (document.body.classList.contains('has-shapla-modal')) {
                document.body.classList.remove('has-shapla-modal');
            }
            Object.assign(this.$data, defaultData());
        },
        closeModal() {
            this.showModal = false;
            let checkbox = document.querySelector('#custom_message');
            if (checkbox) {
                checkbox.checked = false;
            }
            Object.assign(this.$data, defaultData());
        },
        submit(data, side = 'right') {
            let message = '';
            if (!data.message.length) {
                message = "Add some message";
            }
            if (data.message.length && data.message.length < 1) {
                message = "Message too short.";
            }

            if (message.length) {
                return this.$store.commit("SET_NOTIFICATION", {type: 'error', title: 'Error!', message: message});
            }
            this.showModal = false;
            let fieldsContainer = document.querySelector('#_inner_message_fields');
            if (fieldsContainer) {
                fieldsContainer.querySelector('#_inner_message_content').value = data.message;
                fieldsContainer.querySelector('#_inner_message_font').value = data.font_family;
                fieldsContainer.querySelector('#_inner_message_size').value = data.font_size;
                fieldsContainer.querySelector('#_inner_message_align').value = data.alignment;
                fieldsContainer.querySelector('#_inner_message_color').value = data.color;
            }

            let variations_form = document.querySelector('form.cart');
            if (variations_form) {
                this.$store.commit('SET_LOADING_STATUS', true);
                let form = new FormData(variations_form), data = {};
                for (const [key, value] of form.entries()) {
                    if (key === "attribute_pa_size") {
                        this.card_size = value;
                    }
                    data[`${key}`] = value;
                }
                variations_form.submit();
            }

            if (this.page === 'cart') {
                window.jQuery.ajax({
                    url: StackonetToolkit.ajaxUrl,
                    method: 'POST',
                    data: {
                        action: 'set_cart_item_info',
                        page_side: side,
                        item_key: this.cartkey,
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
        },
        onUpdateMultiCompose(data) {
            if ('cart' === this.page) {
                return this.onUpdateCartItemInfo(data);
            }

            let fieldsContainer = document.querySelector('#_inner_message_fields');
            if (fieldsContainer) {
                const rightPageContent = data.right;
                fieldsContainer.querySelector('#_inner_message_content').value = rightPageContent.message;
                fieldsContainer.querySelector('#_inner_message_font').value = rightPageContent.font_family;
                fieldsContainer.querySelector('#_inner_message_size').value = rightPageContent.font_size;
                fieldsContainer.querySelector('#_inner_message_align').value = rightPageContent.alignment;
                fieldsContainer.querySelector('#_inner_message_color').value = rightPageContent.color;
            }

            let imContainer2 = document.querySelector('#_video_inner_message_fields');
            if (imContainer2) {
                const leftPageContent = data.left;
                imContainer2.querySelector('#_inner_message2_type').value = leftPageContent.type;
                imContainer2.querySelector('#_inner_message2_video_id').value = leftPageContent.video_id;
                imContainer2.querySelector('#_inner_message2_content').value = leftPageContent.message;
                imContainer2.querySelector('#_inner_message2_font').value = leftPageContent.font_family;
                imContainer2.querySelector('#_inner_message2_size').value = leftPageContent.font_size;
                imContainer2.querySelector('#_inner_message2_align').value = leftPageContent.alignment;
                imContainer2.querySelector('#_inner_message2_color').value = leftPageContent.color;
            }

            let variations_form = document.querySelector('form.cart');
            if (variations_form) {
                this.$store.commit('SET_LOADING_STATUS', true);
                this.showModal = false;
                variations_form.submit();
            }
        },
        onUpdateCartItemInfo(data) {
            window.jQuery.ajax({
                url: StackonetToolkit.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'update_cart_item_info',
                    item_key: this.cartkey,
                    messages: data
                },
                success: function () {
                    window.location.reload();
                }
            })
        },
    }
}
</script>

<style lang="scss">
.yousaidit-inner-message {
  box-sizing: border-box;

  *, *:before, *:after {
    box-sizing: border-box;
  }

  .modal--inner-message-compose {
    .shapla-modal-content {
      border-radius: 0;
      height: var(--inner-message-modal-height, 100vh);
      width: 100vw;

      .admin-bar & {
        margin-top: var(--admin-bar-height, 32px);
        --inner-message-modal-height: calc(100vh - var(--admin-bar-height, 32px));

        @media screen and (max-width: 782px) {
          --admin-bar-height: 46px;
        }
      }
    }
  }

  .shapla-modal-content.is-full {
    max-height: 100vh;
  }

  &__actions {
    margin-top: 1rem;
    text-align: right;

    > *:not(:last-child) {
      margin-right: 8px;
    }
  }
}
</style>
