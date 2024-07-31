<script setup lang="ts">
import {DynamicCardItemInterface, DynamicCardTextSectionInterface,} from "../../interfaces/designer-card.ts";
import {computed, onMounted, PropType, reactive, watch} from "vue";
import {
  ShaplaButton,
  ShaplaColumn,
  ShaplaColumns,
  ShaplaInput,
  ShaplaRangeSlider,
  ShaplaSelect,
  ShaplaSidenav
} from "@shapla/vue-components";
import SvgIcon from "./SvgIcon.vue";
import {DesignerProfileFontInterface} from "../../interfaces/designer.ts";

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
  value: {type: Object as PropType<DynamicCardTextSectionInterface>, required: true},
  active: {type: Boolean, default: false},
  title: {type: String, default: 'Add New Section'},
  images: {type: Array, default: () => []},
  fonts: {type: Array as PropType<DesignerProfileFontInterface[]>, default: () => []},
  mode: {type: String, default: 'create'},
})

const state = reactive<{
  options: DynamicCardTextSectionInterface;
  upload_error_message: string;
}>({
  options: JSON.parse(JSON.stringify(Object.assign({}, defaultOptions))),
  upload_error_message: '',
})

const emit = defineEmits<{
  cancel: [];
  update: [value: DynamicCardTextSectionInterface];
  submit: [value: DynamicCardTextSectionInterface];
  addfont: [];
}>()

const cancel = () => emit('cancel');
const addFont = () => emit('addfont');

const canSubmit = computed<boolean>(() => {
  if (!state.options.section_type.length) {
    return false;
  }
  return !!(
      state.options.position.left &&
      state.options.position.top
  );
})

const text_aligns = [
  {value: 'left', label: 'Left'},
  {value: 'center', label: 'Center'},
  {value: 'right', label: 'Right'},
];

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
              <div class="mb-2">
                <h4 class="text-base">Content</h4>
                <ShaplaInput label="Placeholder" type="textarea" v-model="state.options.placeholder" rows="2"/>
              </div>
            </ShaplaColumn>
            <ShaplaColumn :tablet="12" class="hidden">
              <h4 class="text-base">Custom font</h4>
              <p class="text-xs">If font is not listed, you can add your font.</p>
              <ShaplaButton theme="primary" size="small" outline fullwidth @click="addFont">
                <SvgIcon icon="plus"/>
                <span>Add custom font</span>
              </ShaplaButton>
            </ShaplaColumn>
            <ShaplaColumn :tablet="12">
              <h4 class="text-base">Text Options</h4>
              <div class="flex flex-wrap">
                <div class="w-full p-1">
                  <ShaplaSelect
                      label="Font Family" v-model="state.options.textOptions.fontFamily"
                      :options="fonts" label-key="label" value-key="key" :clearable="false"
                  />
                </div>
                <div class="w-1/2 p-1">
                  <ShaplaSelect label="Align" v-model="state.options.textOptions.align" :options="text_aligns"
                                :clearable="false"/>
                </div>
                <div class="w-1/2 p-1">
                  <ShaplaInput type="number" label="Font Size (pt)" v-model="state.options.textOptions.size"/>
                </div>
                <div class="w-full p-1 flex">
                  <ShaplaInput type="number" label="Letter Spacing (pt)" v-model="state.options.textOptions.spacing"/>
                </div>
                <div class="w-full p-1 flex">
                  <div class="w-3/4">
                    <ShaplaInput label="Text Color" v-model="state.options.textOptions.color"/>
                  </div>
                  <div class="w-1/4">
                    <input type="color" v-model="state.options.textOptions.color"
                           class="h-full border-l-0">
                  </div>
                </div>
                <div class="w-full p-1 flex">
                  <div>Rotation</div>
                  <ShaplaRangeSlider v-model="state.options.textOptions.rotation" :min="0" :max="360" :step="5"
                                     :show-reset="false"/>
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
