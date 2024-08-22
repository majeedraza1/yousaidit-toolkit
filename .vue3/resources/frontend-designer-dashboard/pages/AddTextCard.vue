<script setup lang="ts">
import {computed, onMounted, reactive, ref} from "vue";
import {
  DynamicCardImageSectionInterface,
  DynamicCardPayloadInterface,
  DynamicCardTextSectionInterface,
  TextCardBaseInterface,
  TYPE_SECTION_TYPE,
  UploadedAttachmentInterface
} from "../../interfaces/designer-card.ts";
import {ShaplaButton, ShaplaFileUploader, ShaplaImage} from "@shapla/vue-components";
import useDesignerCardStore from "../stores/store-cards.ts";
import SvgIcon from "../components/SvgIcon.vue";
import {Dialog} from "@shapla/vanilla-components";
import InputImageSection from "../components/InputImageSection.vue";
import InputTextSection from "../components/InputTextSection.vue";
import CardOptions from "../components/CardOptions.vue";
import CardOptionsPreview from "../components/CardOptionsPreview.vue";
import ModalAddFont from "../components/ModalAddFont.vue";
import {FontInfoInterface} from "../../interfaces/custom-font.ts";
import {DesignerProfileFontInterface} from "../../interfaces/designer.ts";
import {useRouter} from "vue-router";
import {convertPXtoMM} from "../../utils/helper.ts";
// import cardTestData from '../sample-data/text-card-sample.ts'

const store = useDesignerCardStore();
const router = useRouter();
const state = reactive<{
  card: TextCardBaseInterface;
  upload_error_message: string;
  previewWidth: number;
  previewHeight: number;
  stepDone: number;
  sections: (DynamicCardImageSectionInterface | DynamicCardTextSectionInterface)[];
  active_section: DynamicCardImageSectionInterface | DynamicCardTextSectionInterface;
  active_section_index: number;
  show_section_edit_modal: boolean;
  show_add_font_modal: boolean;
  fonts: DesignerProfileFontInterface[];
}>({
  card: {
    main_image_id: 0,
    main_image: null,
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
  sections: [],
  upload_error_message: '',
  previewWidth: 0,
  previewHeight: 0,
  stepDone: 0,
  active_section: null,
  active_section_index: -1,
  show_section_edit_modal: false,
  show_add_font_modal: false,
  fonts: [],
})

const canvasContainer = ref(null);

const hasMainImage = computed<boolean>(() => state.card && state.card.main_image_id > 0);

const calculateWidthAndHeight = () => {
  let innerEL = canvasContainer.value;
  let d = [154, 156];

  if (innerEL) {
    state.previewWidth = innerEL.offsetWidth || (document.body.offsetWidth - 30);
    state.previewHeight = Math.round(state.previewWidth * (d[1] / d[0]));
  }
}

const fileRequestHeaders = computed(() => {
  if (window.DesignerProfile.restNonce) {
    return {'X-WP-Nonce': window.DesignerProfile.restNonce};
  } else {
    return {}
  }
})

const handleMainImageUpload = (fileObject, serverResponse) => {
  const attachment = serverResponse.data.attachment as UploadedAttachmentInterface;
  state.card.main_image_id = attachment.id;
  state.card.main_image = attachment;
}
const handleMainImageUploadFail = (fileObject, serverResponse) => {
  if (serverResponse.message) {
    state.upload_error_message = serverResponse.message;
  }
}

const removeMainImage = () => {
  Dialog.confirm('Are you sure to remove it?').then(confirmed => {
    if (confirmed) {
      store.deleteImage(state.card.main_image_id).finally(() => {
        state.card.main_image_id = 0;
        state.card.main_image = null;
      })
    }
  })
}

const addCardSection = (type: TYPE_SECTION_TYPE) => {
  if (type === "input-text") {
    state.sections.push({
      label: 'Text section',
      section_type: 'input-text',
      position: {left: 0, top: 0},
      text: '',
      placeholder: 'Enter you name',
      textOptions: {
        fontFamily: 'arial',
        size: '16',
        align: 'left',
        color: '#000',
        rotation: 0,
        spacing: 0,
      }
    })
  }
  if (type === 'input-image') {
    state.sections.push({
      label: 'Image section',
      section_type: 'input-image',
      position: {left: 0, top: 0},
      imageOptions: {
        img: null,
        width: 150,
        height: 150,
        align: "center"
      }
    })
  }
}

const dynamicCardPayload = computed<DynamicCardPayloadInterface>(() => {
  const payload: DynamicCardPayloadInterface = {
    card_size: 'square',
    card_bg_type: 'color',
    card_bg_color: '#ffffff',
    card_background: [],
    card_items: state.sections,
  }
  if (state.card && state.card.main_image_id > 0) {
    payload.card_bg_type = 'image';
    payload.card_background = {
      id: state.card.main_image.id,
      src: state.card.main_image.full.src,
      width: state.card.main_image.full.width,
      height: state.card.main_image.full.height,
    }
  }
  return payload;
})

const editSection = (section: DynamicCardImageSectionInterface | DynamicCardTextSectionInterface, index: number) => {
  state.active_section = section;
  state.active_section_index = index;
  state.show_section_edit_modal = true;
}

const deleteSection = (index: number) => {
  Dialog.confirm('Are you sure to delete the section?').then(() => {
    state.sections.splice(index, 1);
  })
}

const updateSection = () => {
  state.active_section = null;
  state.active_section_index = -1;
  state.show_section_edit_modal = false;
}

const onFontAdded = (font: FontInfoInterface) => {
  const fontInfo = {
    label: font.font_family,
    key: font.slug,
    fontUrl: font.url,
    for_public: font.for_public,
    for_designer: font.for_designer
  };
  state.fonts.push(fontInfo);
  window.DesignerProfile.fonts.push(fontInfo);
  state.show_add_font_modal = false;
}

const onSubmit = () => {
  const data: TextCardBaseInterface = {
    dynamic_card_payload: dynamicCardPayload.value,
    ...state.card
  };

  store.createTextCard(data).then(() => {
    router.push({name: 'Cards'});
  })
}

onMounted(() => {
  state.fonts = window.DesignerProfile.fonts;

  // @TODO remove it after testing.
  // state.card = cardTestData.card;
  // state.sections = cardTestData.sections;

  setTimeout(() => calculateWidthAndHeight(), 1000)
})
</script>

<template>
  <h2 class="text-center text-4xl bg-gray-100 p-2 border border-solid border-primary mb-4">Add Text Card</h2>
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
          <h2 class="text-2xl leading-none mb-4">Card Artwork Size</h2>
          <p>To ensure print quality we need the artwork uploaded to be the correct size.</p>
          <p>
            <strong>Size (width x height):</strong> 15.4 x 15.6 cm <span class="hidden color-text-secondary"> or (1819 x 1843 px)</span><br>
            <strong>Resolution:</strong> 300ppi<br>
            <strong>Colour mode:</strong> CMYK<br>
            <strong>Format:</strong> JPEG or PNG
          </p>
        </div>
        <div class="mb-4">
          <h2 class="text-2xl leading-none mb-0">Upload Main Image</h2>
          <p class="mb-4">Please use royalty free image for commercial use.</p>
          <div class="w-full">
            <template v-if="hasMainImage">
              <ShaplaImage class="border border-solid border-gray-200" container-width="150px"
                           container-height="150px">
                <img :src="state.card.main_image.thumbnail.src" alt="Main image">
              </ShaplaImage>
              <div class="mt-2">
                <ShaplaButton theme="primary" size="small" @click="removeMainImage">Remove Image</ShaplaButton>
              </div>
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
              <div v-if="state.upload_error_message.length">
                <div v-html="state.upload_error_message"
                     class="p-2 text-red-600 border border-solid border-red-600"></div>
              </div>
            </template>
          </div>
        </div>
        <div class="mb-4">
          <h2 class="text-2xl leading-none mb-4">Add Customization</h2>
          <div>
            <div class="w-full flex space-x-2 mb-4">
              <ShaplaButton fullwidth outline theme="primary" @click="()=> addCardSection('input-text')">
                <SvgIcon icon="plus"/>
                <span>Add text</span>
              </ShaplaButton>
              <ShaplaButton fullwidth outline theme="primary" @click="()=>addCardSection('input-image')">
                <SvgIcon icon="plus"/>
                <span>Add image</span>
              </ShaplaButton>
            </div>
            <div v-for="(section, index) in state.sections" :key="index">
              <div class="border border-solid border-gray-400 w-full p-2 rounded mb-2 flex items-center space-x-2">
                <SvgIcon icon="sort"/>
                <div class="flex-grow">
                  <div class="font-medium">Section {{ index + 1 }}: {{ section.label }}</div>
                  <div class="text-sm">{{ section.section_type === 'input-image' ? 'Image' : 'Text' }}</div>
                </div>
                <div>
                  <SvgIcon icon="pencil" hoverable @click="editSection(section,index)"/>
                  <SvgIcon icon="delete" hoverable @click="deleteSection(index)"/>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div>
          <ShaplaButton theme="primary" size="large" fullwidth @click="state.stepDone = 1">Next</ShaplaButton>
        </div>
      </div>
    </div>
    <div v-if="1 === state.stepDone" class="flex flex-col items-center">
      <CardOptions v-model="state.card"/>
      <div class="flex justify-center mt-4">
        <ShaplaButton theme="primary" @click="state.stepDone = 2">Next</ShaplaButton>
      </div>
    </div>
    <div v-if="2 === state.stepDone" class="mb-4">
      <CardOptionsPreview :card="state.card"/>
      <div class="flex justify-center mt-4">
        <ShaplaButton theme="primary" @click="onSubmit">Submit</ShaplaButton>
      </div>
    </div>
    <div class="mb-4">&nbsp;</div>
    <template v-if="state.active_section">
      <InputImageSection
          v-if="state.active_section.section_type === 'input-image'"
          :value="state.active_section"
          :upload_url="store.attachment_upload_url"
          :active="state.show_section_edit_modal"
          :title="`Edit Section: ${state.active_section.label}`"
          @cancel="state.show_section_edit_modal = false"
          mode="edit"
          @submit="updateSection"
      />
      <InputTextSection
          v-if="state.active_section.section_type === 'input-text'"
          :value="state.active_section"
          :active="state.show_section_edit_modal"
          :title="`Edit Section: ${state.active_section.label}`"
          :fonts="state.fonts"
          @cancel="state.show_section_edit_modal = false"
          mode="edit"
          @submit="updateSection"
          @addfont="state.show_add_font_modal = true"
      />
      <ModalAddFont
          :active="state.show_add_font_modal"
          @close="state.show_add_font_modal = false"
          @font:added="onFontAdded"
      />
    </template>
  </div>
</template>
