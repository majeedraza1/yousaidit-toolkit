<template>
  <div ref="root" class="yousaidit-inner-message-compose flex flex-col h-full -m-4 p-4 sm:bg-gray-100">
    <div class="h-full flex flex-wrap justify-center">
      <div class="sm:w-full flex items-center justify-center flex-grow" id="editable-content-container">
        <EditableContent
            v-if="active"
            class="shadow-lg sm:mb-4 sm:bg-white md:ml-auto md:mr-auto"
            style="max-width: 400px;"
            placeholder="Please click here to write your message"
            :font-family="state.font_family"
            :font-size="state.font_size"
            :text-align="state.alignment"
            :color="state.color"
            v-model="state.message"
            :card-size="cardSize"
        />
        <div v-if="state.showLengthError" class="has-error p-4 my-4">
          Oops... your message is too long, please keep inside the box.
        </div>
      </div>
      <div class="sm:w-full sm:mt-4 sm:mb-4">
        <div class="flex flex-col h-full bg-gray-100 w-80 ml-auto">
          <EditorControls v-model="state" @change="onChangeEditorControls"/>
          <div class="flex-grow"></div>
          <div class="flex space-x-2 p-4 mt-4">
            <ShaplaButton theme="primary" outline @click="emitClose" class="flex-grow">Cancel</ShaplaButton>
            <ShaplaButton theme="primary" @click="submit" class="flex-grow">{{ btnText }}</ShaplaButton>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts" setup>
import {ShaplaButton} from '@shapla/vue-components';
import EditableContent from "./EditableContent.vue";
import EditorControls from "./EditorControls.vue";
import {reactive, ref, watch} from "vue";

const root = ref<HTMLDivElement>(null);

const emit = defineEmits<{
  submit: [value: any];
  close: [];
}>()
const props = defineProps({
  cardSize: {type: String},
  active: {type: Boolean, default: false},
  innerMessage: {type: Object},
  btnText: {type: String, default: 'Add to Basket'}
})

const state = reactive({
  message: '',
  font_family: "'Indie Flower', cursive",
  font_size: '18',
  alignment: 'center',
  color: '#1D1D1B',
  showLengthError: false,
})


const onChangeEditorControls = (args) => {
  if ('font-family' === args.key) {
    state.font_family = args.payload.fontFamily;
  }
  if ('color' === args.key) {
    state.color = args.payload;
  }
  if ('emoji' === args.key) {
    document.execCommand("insertHtml", false, args.payload);
  }
}
const submit = () => emit('submit', state);
const emitClose = () => emit('close');

watch(() => props.innerMessage, newValue => {
  state.message = newValue.content;
  state.font_family = newValue.font;
  state.font_size = newValue.size;
  state.alignment = newValue.align;
  state.color = newValue.color;
}, {deep: true})

watch(() => state.message, () => {
  let content = root.value.querySelector<HTMLDivElement>('.editable-content'),
      editor = content ? content.querySelector<HTMLDivElement>('.editable-content__editor') : null;

  if (editor && content) {
    state.showLengthError = editor.offsetHeight > (0.95 * content.offsetHeight);
  }
})

</script>
