<template>
  <div class="carousel-slider-control-background-image">
    <shapla-chip v-if="has_image" deletable @delete="clearImages">{{ value.title }}</shapla-chip>
    <button v-else class="button button--open-modal">{{ buttonText }}</button>
  </div>
</template>

<script>
import axios from '../../../utils/axios'
import {shaplaChip} from "shapla-vue-components";

export default {
  name: "PdfUploader",
  components: {shaplaChip},
  props: {
    id: {type: Number, default: 0},
    placeholderText: {type: String, default: 'No File Selected'},
    buttonText: {type: String, default: 'Add PDF'},
    removeButtonText: {type: String, default: 'Remove'},
    modalTitle: {type: String, default: 'Select PDF'},
    modalButtonText: {type: String, default: 'Set PDF'},
    value: {
      type: Object, default: () => {
      }
    },
  },
  computed: {
    has_image() {
      return !!this.value.title;
    }
  },
  mounted() {
    let self = this;
    let uploadBtn = this.$el.querySelector('.button--open-modal');
    if (uploadBtn) {
      uploadBtn.addEventListener('click', () => {
        let frame = new wp.media.view.MediaFrame.Select({
          title: self.modalTitle,
          multiple: false,
          library: {
            order: 'ASC',
            orderby: 'title',
            type: 'application/pdf',
            search: null,
            uploadedTo: null
          },

          button: {text: self.modalButtonText}
        });

        frame.on('select', function () {
          let collection = frame.state().get('selection'), ids = 0;

          collection.each(function (attachment) {
            ids = attachment.id;
          });

          self.updatePdfData({id: ids});
        });

        frame.open();
      });
    }
  },
  methods: {
    updatePdfData(data) {
      axios.put('products/' + this.id, {
        pdf_id: data.id
      }).then(response => {
        this.$emit('input', response.data.data);
      }).catch(error => {
        console.log(error);
      })
    },
    clearImages() {
      if (confirm('Are you sure?')) {
        this.updatePdfData({id: 0});
      }
    }
  }
}
</script>
