<script setup lang="ts">
import {
  DynamicCardImageSectionInterface,
  DynamicCardItemInterface,
  UploadedAttachmentInterface
} from "../../interfaces/designer-card.ts";
import {computed, onMounted, PropType, reactive, watch} from "vue";
import {
  ShaplaButton,
  ShaplaColumn,
  ShaplaColumns,
  ShaplaFileUploader,
  ShaplaImage,
  ShaplaInput,
  ShaplaSelect,
  ShaplaSidenav
} from "@shapla/vue-components";

const defaultOptions: DynamicCardItemInterface = {
  label: '',
  section_type: 'input-text',
  position: {left: '', top: ''},
  text: '',
  placeholder: '',
  textOptions: {
    fontFamily: '',
    size: '16',
    align: 'left',
    color: '#000000',
    rotation: 0,
    spacing: 0,
  },
  imageOptions: {
    img: {id: 0, src: '', width: 0, height: 0},
    width: '',
    height: '',
    align: 'left',
  }
}

const props = defineProps({
  upload_url: {type: String, required: true},
  value: {type: Object as PropType<DynamicCardImageSectionInterface>, required: true},
  active: {type: Boolean, default: false},
  title: {type: String, default: 'Add New Section'},
  images: {type: Array, default: () => []},
  mode: {type: String, default: 'create'},
})

const state = reactive<{
  options: DynamicCardImageSectionInterface;
  upload_error_message: string;
}>({
  options: JSON.parse(JSON.stringify(Object.assign({}, defaultOptions))),
  upload_error_message: '',
})

const emit = defineEmits<{
  cancel: [];
  update: [value: DynamicCardImageSectionInterface];
  submit: [value: DynamicCardImageSectionInterface];
}>()

const hasImage = computed(() => state.options.imageOptions.img.id > 0)

const cancel = () => emit('cancel');

const clearImage = () => {
  state.options.imageOptions.img = {id: 0, src: '', width: 0, height: 0};
}

const fileRequestHeaders = computed(() => {
  if (window.DesignerProfile.restNonce) {
    return {'X-WP-Nonce': window.DesignerProfile.restNonce};
  } else {
    return {}
  }
})

const canSubmit = computed<boolean>(() => {
  if (!state.options.section_type.length) {
    return false;
  }
  return !!(
      state.options.position.left &&
      state.options.position.top &&
      state.options.imageOptions.img.id > 0
  );
})

const handleMainImageUpload = (fileObject, serverResponse) => {
  const attachment = serverResponse.data.attachment as UploadedAttachmentInterface;
  state.options.imageOptions.img = {id: attachment.id, ...attachment.full};
}
const handleMainImageUploadFail = (fileObject, serverResponse) => {
  if (serverResponse.message) {
    state.upload_error_message = serverResponse.message;
  }
}

const px_to_mm = (px: number) => Math.round(px * 0.2645833333);

const updateImageWidthHeight = (type: string, value: number) => {
  let height = px_to_mm(state.options.imageOptions.img.height),
      width = px_to_mm(state.options.imageOptions.img.width),
      newHeight = Math.round(value * (height / width)),
      newWidth = Math.round(value * (width / height));
  return type === 'width' ? newHeight : newWidth;
}

const handleImageWidthUpdate = (value: string) => {
  state.options.imageOptions.height = updateImageWidthHeight('width', parseInt(value));
}

const handleImageHeightUpdate = (value: string) => {
  state.options.imageOptions.width = updateImageWidthHeight('height', parseInt(value));
}

const confirm = () => {
  emit('submit', state.options);
  state.options = JSON.parse(JSON.stringify(Object.assign({}, defaultOptions)));
}

watch(() => props.value, newValue => state.options = newValue, {deep: true})
watch(() => state.options, newValue => emit('update', newValue), {deep: true})

onMounted(() => {
  state.options = props.value;
})
</script>

<template>
  <div class="input-image-section">
    <ShaplaSidenav :active="active" @close="cancel" position="right">
      <div class="input-image-section-inside">
        <div class="input-image-section-head">{{ title }}</div>
        <div class="input-image-section-body mb-12">
          <ShaplaColumns multiline>
            <ShaplaColumn :tablet="12">
              <div class="mb-2">
                <h4 class="text-base">Position</h4>
                <div class="flex flex-wrap">
                  <div class="w-1/2 p-1">
                    <ShaplaInput type="number" label="Left (mm)" v-model="state.options.position.left"/>
                  </div>
                  <div class="w-1/2 p-1">
                    <ShaplaInput type="number" label="Top (mm)" v-model="state.options.position.top"/>
                  </div>
                </div>
              </div>
            </ShaplaColumn>
            <ShaplaColumn :tablet="12">
              <h4 class="text-base">Image</h4>
              <div class="flex flex-wrap mb-2">
                <div class="w-full p-1">
                  <template v-if="hasImage">
                    <ShaplaImage class="border border-solid border-gray-200" container-width="150px"
                                 container-height="150px">
                      <img :src="state.options.imageOptions.img.src" alt="Main image">
                    </ShaplaImage>
                  </template>
                  <template v-else>
                    <ShaplaFileUploader
                        class="static-card-image-uploader"
                        :url="props.upload_url"
                        @success="handleMainImageUpload"
                        @fail="handleMainImageUploadFail"
                        text-max-upload-limit="Max upload filesize: 5MB"
                        :headers="fileRequestHeaders"
                        :params="{type:'card-logo',card_size:'square',card_type:'section_image'}"
                    />
                    <div v-if="state.upload_error_message.length">
                      <div v-html="state.upload_error_message"
                           class="p-2 text-red-600 border border-solid border-red-600"></div>
                    </div>
                  </template>
                </div>
              </div>
            </ShaplaColumn>
            <ShaplaColumn :tablet="12">
              <h4 class="text-base">Image Options</h4>
              <div class="flex flex-wrap">
                <div class="w-full p-1">
                  <ShaplaSelect
                      label="Align"
                      v-model="state.options.imageOptions.align"
                      :options="[
                          {value: 'left', label: 'Left'},
                          {value: 'center', label: 'Center'},
                          {value: 'right', label: 'Right'},
                      ]"
                  />
                </div>
                <div class="w-1/2 p-1">
                  <ShaplaInput type="number" label="Width (mm)" @update:modelValue="handleImageWidthUpdate"
                               v-model="state.options.imageOptions.width"/>
                </div>
                <div class="w-1/2 p-1">
                  <ShaplaInput label="Height (mm)" @update:modelValue="handleImageHeightUpdate"
                               v-model="state.options.imageOptions.height"/>
                </div>
              </div>
            </ShaplaColumn>
          </ShaplaColumns>
        </div>
        <div class="input-image-section-footer absolute bottom-0 left-0 w-full flex p-2 space-x-2 bg-white">
          <div class="w-1/2">
            <ShaplaButton theme="default" @click="cancel" fullwidth>Cancel</ShaplaButton>
          </div>
          <div class="w-1/2">
            <ShaplaButton theme="primary" @click="confirm" :disabled="!canSubmit" fullwidth>Confirm</ShaplaButton>
          </div>
        </div>
      </div>
    </ShaplaSidenav>
  </div>
</template>
<style lang="scss">
.input-image-section {
  .shapla-sidenav__background,
  .shapla-sidenav__body {
    position: fixed;

    .admin-bar & {
      top: 32px;
      height: calc(100% - 32px);
    }
  }
}

.input-image-section-head {
  font-weight: bold;
  border-bottom: 1px solid rgba(#000, 0.12);
}

.input-image-section-head,
.input-image-section-body,
.input-image-section-footer {
  padding: .5rem;
}
</style>
