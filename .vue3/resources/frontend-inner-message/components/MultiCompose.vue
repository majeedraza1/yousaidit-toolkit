<template>
  <div class="multi-compose w-full h-full flex flex-col lg:flex-row lg:space-x-4">
    <div class="flex flex-col flex-grow dynamic-card--canvas">
      <div class="w-full h-full flex dynamic-card--canvas-slider">
        <SwiperSlider :card_size="cardSize" :slide-to="state.slideTo" :hide-canvas="true"
                      @slideChange="onSlideChange">
          <template v-slot:video-message>
            <VideoInnerMessage
                :product_id="product_id"
                :inner-message="state.leftInnerMessage"
                :card_size="cardSize"
                :open-ai-editable="0===state.slideTo"
                @change="changeVideoInnerMessage"
            />
          </template>
          <template v-slot:inner-message>
            <div class="dynamic-card--editable-content-container">
              <EditableContent
                  placeholder="Please click here to write your message"
                  :font-family="state.rightInnerMessage.font_family"
                  :font-size="state.rightInnerMessage.font_size"
                  :text-align="state.rightInnerMessage.alignment"
                  :color="state.rightInnerMessage.color"
                  v-model="state.rightInnerMessage.message"
                  :card-size="cardSize"
                  :open-ai-editable="1===state.slideTo"
                  @lengthError="error => onLengthError(error, 'right')"
              />
              <div v-if="state.showRightMessageLengthError" class="has-error p-2 my-4 absolute bottom-0">
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
            <img :src="placeholder_im_left" alt=""/>
          </ShaplaImage>
          <ShaplaImage container-width="64px" class="bg-gray-100" @click.native="state.slideTo = 1"
                       :class="{'border border-solid border-primary':state.slideTo === 1}">
            <img :src="placeholder_im_right" alt=""/>
          </ShaplaImage>
        </div>
      </div>
    </div>
    <div
        class="flex flex-col justify-between bg-gray-100 p-2 dynamic-card--controls lg:border border-solid border-gray-100">
      <div v-if="state.slideTo === 0">
        <EditorControls
            v-model="state.leftInnerMessage"
            @change="onChangeLeftEditorControls"
            @generateContent="onGenerateContentLeft"
        />
      </div>
      <div v-if="state.slideTo === 1">
        <EditorControls
            v-model="state.rightInnerMessage"
            @change="onChangeEditorControls"
            @generateContent="onGenerateContentRight"
        />
      </div>
      <div class="space-y-2 mb-24 md:mb-0">
        <ShaplaButton theme="primary" size="small" fullwidth outline @click="emit('close')">
          Cancel
        </ShaplaButton>
        <ShaplaButton theme="primary" size="medium" fullwidth @click="handleSubmit">
          Add to basket
        </ShaplaButton>
        <div class="">&nbsp;</div>
      </div>
    </div>
  </div>
</template>

<script lang="ts" setup>
import {ShaplaButton, ShaplaImage} from "@shapla/vue-components";
import VideoInnerMessage from "./VideoInnerMessage.vue";
import EditableContent from "./EditableContent.vue";
import EditorControls from "./EditorControls.vue";
import SwiperSlider from "./SwiperSlider.vue";
import {onMounted, reactive, watch} from "vue";
import {InnerMessagePropsInterface} from "../../interfaces/inner-message.ts";

const defaults = (): InnerMessagePropsInterface => {
  return {
    message: '',
    font_family: "'Indie Flower', cursive",
    font_size: '18',
    alignment: 'center',
    color: '#1D1D1B',
    type: 'text',
    video_id: 0,
  }
}

const emit = defineEmits<{
  submit: [value: { left: InnerMessagePropsInterface, right: InnerMessagePropsInterface }];
  close: [];
}>();

const props = defineProps({
  active: {type: Boolean, default: false},
  cardSize: {type: String},
  product_id: {type: Number, default: 0},
  leftMessage: {type: Object, default: () => ({})},
  rightMessage: {type: Object, default: () => ({})},
  btnText: {type: String, default: 'Add to Basket'}
})

const state = reactive<{
  slideTo: number;
  leftInnerMessage: InnerMessagePropsInterface;
  rightInnerMessage: InnerMessagePropsInterface;
  showLeftMessageLengthError: boolean;
  showRightMessageLengthError: boolean;
}>({
  slideTo: 0,
  leftInnerMessage: defaults(),
  rightInnerMessage: defaults(),
  showLeftMessageLengthError: false,
  showRightMessageLengthError: false,
})

const propsToState = (newValue) => {
  return {
    message: newValue.content,
    font_family: newValue.font,
    font_size: newValue.size,
    alignment: newValue.align,
    color: newValue.color,
    type: newValue.type ?? 'text',
    video_id: newValue.video_id ?? 0,
  }
}

const placeholder_im_left = window.StackonetToolkit.placeholderUrlIML;
const placeholder_im_right = window.StackonetToolkit.placeholderUrlIMR;

watch(() => props.leftMessage, newValue => state.leftInnerMessage = propsToState(newValue), {deep: true})
watch(() => props.rightMessage, newValue => state.rightInnerMessage = propsToState(newValue), {deep: true})

onMounted(() => {
  if (props.leftMessage && Object.keys(props.leftMessage).length) {
    state.leftInnerMessage = propsToState(props.leftMessage);
  }
  if (props.rightMessage && Object.keys(props.rightMessage).length) {
    state.rightInnerMessage = propsToState(props.rightMessage);
  }
})


const changeVideoInnerMessage = (type: string, value) => {
  if ('type' === type) {
    state.leftInnerMessage.type = value;
  } else if ('video_id' === type) {
    state.leftInnerMessage.video_id = value;
  } else if ('message' === type) {
    state.leftInnerMessage.message = value;
  } else {
    state.leftInnerMessage.type = '';
    state.leftInnerMessage.video_id = 0;
  }
}
const onSlideChange = (activeIndex: number) => {
  if (activeIndex !== state.slideTo) {
    state.slideTo = activeIndex;
  }
}
const onLengthError = (error: boolean, side: string = null) => {
  if (side === 'left') {
    state.showLeftMessageLengthError = error;
  } else if (side === 'right') {
    state.showRightMessageLengthError = error;
  }
}
const onChangeLeftEditorControls = (args) => {
  if ('emoji' === args.key) {
    document.execCommand("insertHtml", false, args.payload);
  }
}
const onChangeEditorControls = (args) => {
  if ('emoji' === args.key) {
    document.execCommand("insertHtml", false, args.payload);
  }
}
const messagesLinesToString = (lines) => {
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
  state.leftInnerMessage.type = 'text';
  state.leftInnerMessage.message = messagesLinesToString(args.lines);
}
const onGenerateContentRight = (args) => {
  state.rightInnerMessage.message = messagesLinesToString(args.lines);
}
const handleSubmit = () => {
  emit('submit', {left: state.leftInnerMessage, right: state.rightInnerMessage});
}
</script>
