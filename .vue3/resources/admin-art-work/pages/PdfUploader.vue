<template>
  <div class="carousel-slider-control-background-image">
    <ShaplaChip v-if="has_image" deletable @delete="clearImages">{{ value.title }}</ShaplaChip>
    <button ref="button" v-else class="button button--open-modal">{{ buttonText }}</button>
  </div>
</template>

<script lang="ts" setup>
import axios from '../../utils/axios'
import {ShaplaChip} from "@shapla/vue-components";
import {computed, onMounted, ref} from "vue";
import {Dialog} from "@shapla/vanilla-components";

const props = defineProps({
  id: {type: Number, default: 0},
  placeholderText: {type: String, default: 'No File Selected'},
  buttonText: {type: String, default: 'Add PDF'},
  removeButtonText: {type: String, default: 'Remove'},
  modalTitle: {type: String, default: 'Select PDF'},
  modalButtonText: {type: String, default: 'Set PDF'},
  value: {type: Object, default: () => ({})},
})

const emit = defineEmits<{
  input: [value: any];
}>();
const button = ref(null)
const has_image = computed(() => !!props.value.title)

const updatePdfData = (data) => {
  axios.put('products/' + props.id, {
    pdf_id: data.id
  }).then(response => {
    emit('input', response.data.data);
  }).catch(error => {
    console.log(error);
  })
}
const clearImages = () => {
  Dialog.confirm('Are you sure?').then(() => {
    updatePdfData({id: 0});
  })
}

onMounted(() => {
  let uploadBtn = button.value;
  if (uploadBtn) {
    uploadBtn.addEventListener('click', () => {
      let frame = new wp.media.view.MediaFrame.Select({
        title: props.modalTitle,
        multiple: false,
        library: {
          order: 'ASC',
          orderby: 'title',
          type: 'application/pdf',
          search: null,
          uploadedTo: null
        },

        button: {text: props.modalButtonText}
      });

      frame.on('select', function () {
        let collection = frame.state().get('selection'), ids = 0;

        collection.each(function (attachment) {
          ids = attachment.id;
        });

        updatePdfData({id: ids});
      });

      frame.open();
    });
  }
})
</script>
