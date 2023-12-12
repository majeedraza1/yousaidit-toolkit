declare module "*.vue" {
  import Vue from "vue"
  export default Vue
}

// declare as vue component that import from package 'shapla-vue-components'
declare module 'shapla-vue-components' {
  import {Component as VueComponent} from 'vue'
  const components: {
    iconContainer: VueComponent,
    inputSlider: VueComponent,
    modal: VueComponent,
    shaplaButton: VueComponent,
    tab: VueComponent,
    tabs: VueComponent,
    deleteIcon: VueComponent,
    FileUploader: VueComponent,
    imageContainer: VueComponent,
  }
  export = components
}

interface HTMLVideoElementWithCaptureStream extends HTMLVideoElement {
  captureStream(): MediaStream;
}
