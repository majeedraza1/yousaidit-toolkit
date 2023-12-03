<template>
  <div class="layer-options-container">
    <side-navigation :active="active" @close="cancel" position="right">
      <div class="layer-options-inside">
        <div class="layer-options-head">{{ title }}</div>
        <div class="layer-options-body mb-12">
          <columns multiline>
            <column :tablet="12">
              <div class="mb-2">
                <div class="w-full">
                  <select-field label="Section Type" :options="section_types"
                                v-model="options.section_type"/>
                </div>
              </div>
              <div class="mb-2">
                <h4 class="text-base">Position</h4>
                <div class="flex flex-wrap">
                  <div class="w-1/2 p-1">
                    <text-field type="number" label="Left (mm)" v-model="options.position.left"/>
                  </div>
                  <div class="w-1/2 p-1">
                    <text-field type="number" label="Top (mm)" v-model="options.position.top"/>
                  </div>
                </div>
              </div>
            </column>
            <column :tablet="12">
              <div class="mb-2" v-if="'static-text' === this.options.section_type">
                <h4 class="text-base">Content</h4>
                <text-field label="Text" type="textarea" v-model="options.text" rows="2"/>
              </div>
              <div class="mb-2" v-if="'input-text' === this.options.section_type">
                <h4 class="text-base">Content</h4>
                <text-field label="Placeholder" type="textarea" v-model="options.placeholder" rows="2"/>
              </div>
              <div class="mb-2"
                   v-if="-1 !== ['static-text','input-text'].indexOf(this.options.section_type)">
                <h4 class="text-base">Text Options</h4>
                <div class="flex flex-wrap">
                  <div class="w-full p-1">
                    <select-field
                        v-if="'static-text' === this.options.section_type"
                        label="Font Family" v-model="options.textOptions.fontFamily"
                        :options="fonts_for_static_text" label-key="label" value-key="key"
                        :clearable="false"
                    />
                    <select-field
                        v-if="'input-text' === this.options.section_type"
                        label="Font Family" v-model="options.textOptions.fontFamily"
                        :options="fonts_for_dynamic_text" label-key="label" value-key="key"
                        :clearable="false"
                    />
                  </div>
                  <div class="w-1/2 p-1">
                    <select-field label="Align" v-model="options.textOptions.align"
                                  :options="text_aligns" :clearable="false"/>
                  </div>
                  <div class="w-1/2 p-1">
                    <text-field type="number" label="Font Size (pt)"
                                v-model="options.textOptions.size"/>
                  </div>
                  <div class="w-full p-1 flex">
                    <text-field type="number" label="Letter Spacing (pt)" v-model="options.textOptions.spacing"/>
                  </div>
                  <div class="w-full p-1 flex">
                    <div class="w-3/4">
                      <text-field label="Text Color" v-model="options.textOptions.color"/>
                    </div>
                    <div class="w-1/4">
                      <input type="color" v-model="options.textOptions.color"
                             class="h-full border-l-0">
                    </div>
                  </div>
                  <div class="w-full p-1 flex">
                    <div>Rotation</div>
                    <input-slider v-model="options.textOptions.rotation" :min="0" :max="360" :step="5"
                                  :show-reset="false"/>
                  </div>
                </div>
              </div>
              <div class="mb-2"
                   v-if="-1 !== ['static-image','input-image'].indexOf(this.options.section_type)">
                <h4 class="text-base">Image</h4>
                <div class="flex flex-wrap mb-2">
                  <div class="w-full p-1">
                    <featured-image @click:add="show_image_modal = true" @click:clear="clearImage"
                                    :image-url="options.imageOptions.img.src"/>
                  </div>
                </div>
                <h4 class="text-base">Image Options</h4>
                <div class="flex flex-wrap">
                  <div class="w-full p-1">
                    <select-field label="Align" v-model="options.imageOptions.align"
                                  :options="text_aligns"/>
                  </div>
                  <div class="w-1/2 p-1">
                    <text-field type="number" label="Width (mm)" @input="handleImageWidthUpdate"
                                v-model="options.imageOptions.width"/>
                  </div>
                  <div class="w-1/2 p-1">
                    <text-field label="Height" @input="handleImageHeightUpdate"
                                v-model="options.imageOptions.height"/>
                  </div>
                </div>
              </div>
            </column>
          </columns>
        </div>
        <div class="layer-options-footer absolute bottom-0 left-0 w-full flex p-2 space-x-2 bg-white">
          <div class="w-1/2">
            <shapla-button theme="default" @click="cancel" fullwidth>Cancel</shapla-button>
          </div>
          <div class="w-1/2">
            <shapla-button theme="primary" @click="confirm" :disabled="!canSubmit" fullwidth>Confirm
            </shapla-button>
          </div>
        </div>
      </div>
    </side-navigation>
    <media-modal
        v-if="show_image_modal"
        :active="show_image_modal"
        @close="show_image_modal = false"
        :images="mediaImages"
        :url="uploadUrl"
        @select:image="handleCardLogoImageId"
        @before:send="addNonceHeader"
        @success="(file,response)=>refreshMediaList(response,'card-image')"
    />
  </div>
</template>

<script>
import {
  _FeaturedImage as FeaturedImage,
  _MediaModal as MediaModal,
  column,
  columns,
  inputSlider,
  modal,
  selectField,
  shaplaButton,
  sideNavigation,
  textField
} from 'shapla-vue-components';

const defaultOptions = {
  label: '',
  section_type: '',
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
    img: {id: '', src: '', width: '', height: ''},
    width: '',
    height: '',
    align: 'left',
  }
};
export default {
  name: "LayerOptions",
  components: {
    sideNavigation, modal, shaplaButton, textField, selectField, columns, column, FeaturedImage, MediaModal,
    inputSlider
  },
  props: {
    active: {type: Boolean, default: false},
    title: {type: String, default: 'Add New Section'},
    value: {},
    images: {type: Array, default: () => []},
    mode: {type: String, default: 'create'}
  },
  watch: {
    value(newValue) {
      if (this.mode === 'edit') {
        this.options = JSON.parse(JSON.stringify(Object.assign({}, defaultOptions, newValue)));
      }
    }
  },
  emits: ['submit'],
  data() {
    return {
      options: JSON.parse(JSON.stringify(Object.assign({}, defaultOptions))),
      show_image_modal: false,
      section_types: [
        {value: 'static-text', label: 'Static Text'},
        {value: 'static-image', label: 'Static Image'},
        {value: 'input-text', label: 'Text Input'},
        {value: 'input-image', label: 'Image Uploader'},
      ],
      text_aligns: [
        {value: 'left', label: 'Left'},
        {value: 'center', label: 'Center'},
        {value: 'right', label: 'Right'},
      ],
    }
  },
  computed: {
    mediaImages() {
      if (this.images.length) {
        return this.images.map(_img => {
          return {
            ..._img,
            src: _img.thumbnail.src || _img.full.src
          }
        })
      }
      return []
    },
    user() {
      return DesignerProfile.user
    },
    uploadUrl() {
      return window.DesignerProfile.restRoot + '/designers/' + this.user.id + '/attachment';
    },
    font_families() {
      return DesignerProfile.fonts;
    },
    fonts_for_static_text() {
      return DesignerProfile.fonts.filter(font => font.for_public || font.for_designer);
    },
    fonts_for_dynamic_text() {
      return DesignerProfile.fonts.filter(font => font.for_public);
    },
    canSubmit() {
      if (!this.options.section_type.length) {
        return false;
      }
      return this.options.position.left && this.options.position.top;
    }
  },
  methods: {
    confirm() {
      this.$emit('submit', this.options);
      this.options = JSON.parse(JSON.stringify(Object.assign({}, defaultOptions)));
    },
    cancel() {
      this.$emit('cancel');
      this.options = JSON.parse(JSON.stringify(Object.assign({}, defaultOptions)));
    },
    refreshMediaList(response, type) {
      this.$emit('upload', response, type);
    },
    addNonceHeader(xhr) {
      xhr.setRequestHeader('X-WP-Nonce', window.DesignerProfile.restNonce);
    },
    handleCardLogoImageId(image) {
      this.options.imageOptions.img = image.full || image.thumbnail;
      this.options.imageOptions.img['id'] = image.id ?? 0;
    },
    clearImage() {
      this.options.imageOptions.img = {id: '', src: '', width: '', height: ''};
    },
    px_to_mm(px) {
      return Math.round(px * 0.2645833333);
    },
    handleImageWidthUpdate(value) {
      this.options.imageOptions.height = this.updateImageWidthHeight('width', value);
    },
    handleImageHeightUpdate(value) {
      this.options.imageOptions.width = this.updateImageWidthHeight('height', value);
    },
    updateImageWidthHeight(type, value) {
      let height = this.px_to_mm(this.options.imageOptions.img.height),
          width = this.px_to_mm(this.options.imageOptions.img.width),
          newHeight = Math.round(value * (height / width)),
          newWidth = Math.round(value * (width / height));
      return type === 'width' ? newHeight : newWidth;
    }
  }
}
</script>

<style lang="scss">
.layer-options-container {
  .shapla-sidenav__background,
  .shapla-sidenav__body {
    position: fixed;
  }
}

.layer-options-inside {
  .admin-bar & {
    margin-top: 32px;
  }
}

.layer-options-head {
  font-weight: bold;
  border-bottom: 1px solid rgba(#000, 0.12);
}

.layer-options-head,
.layer-options-body,
.layer-options-footer {
  padding: .5rem;
}
</style>
