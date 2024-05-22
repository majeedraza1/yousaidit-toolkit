<template>
  <div class="flex w-full h-full">
    <div v-if="state.messageType === ''"
         class="flex flex-col justify-center items-center w-full h-full p-4 lg:p-8 border border-solid border-gray-100">
      <div
          @click="changeType('video')"
          class="border border-solid border-gray-200 hover:border-gray-500 cursor-pointer inline-flex items-center space-x-2 rounded px-4 py-2">
        <div class="w-8 h-8">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="fill-current">
            <g>
              <rect fill="none" height="24" width="24" y="0"/>
            </g>
            <g>
              <g>
                <polygon points="9.5,7.5 9.5,16.5 16.5,12"/>
                <path
                    d="M20,4H4C2.9,4,2,4.9,2,6v12c0,1.1,0.9,2,2,2h16c1.1,0,2-0.9,2-2V6C22,4.9,21.1,4,20,4z M20,18.01H4V5.99h16V18.01z"/>
              </g>
            </g>
          </svg>
        </div>
        <div>
          Add a video message
          <div v-html="videoMessagePriceHTML"></div>
        </div>
      </div>
      <div class="text-sm mt-1 max-w-sm text-center">
        {{ qrCodePlayInfo }}
      </div>

      <div class="text-lg my-8">Or</div>

      <div
          @click="changeType('text')"
          class="border border-solid border-gray-200 hover:border-gray-500 cursor-pointer inline-flex items-center rounded px-4 py-2">
        Add text
      </div>
    </div>
    <div v-if="state.messageType === 'text'" class="dynamic-card--editable-content-container w-full">
      <EditableContent
          placeholder="Please click here to write your message"
          :font-family="innerMessage.font_family"
          :font-size="innerMessage.font_size"
          :text-align="innerMessage.alignment"
          :color="innerMessage.color"
          v-model="innerMessage.message"
          :card-size="card_size"
          :open-ai-editable="openAiEditable"
          @lengthError="onLengthError"
      />
      <div v-if="state.showLengthError" class="has-error p-2 my-4 absolute bottom-0">
        Oops... your message is too long, please keep inside the box.
      </div>
    </div>
    <div v-if="state.messageType === 'video'"
         class="flex flex-col justify-center items-center w-full h-full p-4 lg:p-8">
      <template v-if="state.videos.length < 1">
        <template v-if="state.videoType==='recorded'">
          <div class="mb-2 space-x-4">
            <ShaplaButton v-if="!state.isRecordingStarted" theme="primary" @click="startRecording">Start
              Recording
            </ShaplaButton>
            <ShaplaButton v-if="state.isRecordingStarted" theme="primary" @click="stopRecording">Stop Recording
            </ShaplaButton>
          </div>

          <div class="w-full">
            <div v-if="state.isRecordingStarted || state.isRecordingFinished" class="text-center mb-2 font-bold">
              Preview
            </div>
            <ShaplaImage v-show="state.isRecordingStarted" :width-ratio="1920" :height-ratio="1080">
              <video id="video-recording-preview" width="192" height="108" autoplay muted></video>
            </ShaplaImage>
            <ShaplaImage v-show="state.isRecordingFinished && !state.isRecordingStarted" :width-ratio="1920"
                         :height-ratio="1080">
              <video id="video-recording" width="192" height="108" controls></video>
            </ShaplaImage>
            <div class="mt-2 text-center space-x-2">
              <ShaplaButton v-if="state.isRecordingFinished" theme="primary" outline @click="cancelRecording">
                Cancel
              </ShaplaButton>
              <ShaplaButton v-if="state.isRecordingFinished" theme="primary"
                            :class="{'is-loading':state.isRecordingSendingToServer}" @click="useRecording"
              >Use this Recording
              </ShaplaButton>
            </div>
          </div>
        </template>

        <template v-if="state.videoType==='uploaded'">
          <template v-if="state.job_id">
            <div class="mb-2 w-full">
              <ShaplaProgress theme="primary" striped animated/>
            </div>
            <div class="border-4 border-dashed border-primary p-2 text-lg font-bold text-center">
              Your video is being processed. It may take upto a minute. Please be patient.
            </div>
          </template>
          <template v-else>
            <ShaplaFileUploader
                :url="uploadUrl"
                @success="finishedEvent"
                @failed="handleFileUploadFailed"
                :chunking="true"
                :chunk-size="10000000"
                :headers="headers"
                :text-max-upload-limit="maxUploadLimitText"
            />
            <div v-html="fileUploaderTermsHTML" class="mt-2 max-w-xs text-sm text-center"></div>

            <div class="mt-4" v-if="isRecordingEnabled">
              <ShaplaButton theme="primary" @click="state.videoType='recorded'">Record Video</ShaplaButton>
            </div>
          </template>
        </template>
      </template>
      <div v-if="state.videos.length" :style="{
                minWidth: '50%',
                textAlign: 'center',
                display: 'block',
                width:state.videos[0].width < state.videos[0].height?'auto':'100%'
            }">
        <ShaplaImage :width-ratio="state.videos[0].width" :height-ratio="state.videos[0].height">
          <video :width="state.videos[0].width" :height="state.videos[0].height" controls>
            <source :src="state.videos[0].url" :type="state.videos[0].type">
          </video>
        </ShaplaImage>
        <p class="mt-2">
          <ShaplaButton size="small" outline theme="primary" @click="clearVideo">Clear Video</ShaplaButton>
        </p>
      </div>
    </div>
    <template v-if="state.messageType === 'text' || state.messageType === 'video'">
      <ShaplaButton theme="primary" size="small" class="absolute right-2 top-3 text-bold"
                    @click="clearVideoInnerMessage">Back
      </ShaplaButton>
    </template>
  </div>
</template>

<script lang="ts" setup>
import EditableContent from "./EditableContent.vue";
import {ShaplaButton, ShaplaFileUploader, ShaplaImage, ShaplaProgress} from "@shapla/vue-components";
import axios from "../../utils/axios";
import {initRecording, stopRecording as _stopRecording} from "../helpers/recording.ts";
import {Dialog, Notify} from "@shapla/vanilla-components";
import {computed, onMounted, PropType, reactive, watch} from "vue";
import {InnerMessagePropsInterface} from "../../interfaces/inner-message.ts";
import {ServerErrorResponseInterface, ServerSuccessResponseInterface} from "../../utils/CrudOperation.ts";

const emit = defineEmits<{
  change: [type: string, payload: any];
}>()
const props = defineProps({
  product_id: {default: 0},
  card_size: {default: ''},
  innerMessage: {
    type: Object as PropType<InnerMessagePropsInterface>,
    default: () => ({
      message: '',
      font_family: "'Indie Flower', cursive",
      font_size: '18',
      alignment: 'center',
      color: '#1D1D1B',
      type: '',
      video_id: 0,
    })
  },
  openAiEditable: {type: Boolean, default: false},
})

const state = reactive({
  messageType: '',
  recordedBlob: null,
  videoType: 'uploaded',
  showLengthError: false,
  showAddVideoModal: false,
  videos: [],
  isRecordingStarted: false,
  isRecordingFinished: false,
  isRecordingSendingToServer: false,
  job_id: '',
  timer_id: null,
  isCheckingStatus: false,
})

const isRecordingEnabled = window.StackonetToolkit.isRecordingEnabled;
const maxUploadLimitText = window.StackonetToolkit.maxUploadLimitText;
const fileUploaderTermsHTML = window.StackonetToolkit.fileUploaderTermsHTML;
const videoMessagePriceHTML = window.StackonetToolkit.videoMessagePriceHTML;
const qrCodePlayInfo = window.StackonetToolkit.qrCodePlayInfo;
const isUserLoggedIn = window.StackonetToolkit.isUserLoggedIn;

const headers = computed(() => {
  const headers = {};
  if (window.StackonetToolkit.restNonce) {
    headers['X-WP-Nonce'] = window.StackonetToolkit.restNonce;
  }
  return headers
})

const uploadUrl = window.StackonetToolkit.restRoot + '/dynamic-cards/video';

const clearVideoData = () => {
  state.job_id = '';
  state.videoType = 'uploaded';
  emitChange('video_id', 0);
  localStorage.removeItem(`__gust_video_${props.product_id}`);
}
const cancelRecording = () => {
  state.isRecordingStarted = false;
  state.isRecordingFinished = false;
  state.isRecordingSendingToServer = false;
  state.videoType = 'uploaded';
}
const startRecording = () => {
  state.isRecordingStarted = true;
  initRecording();
}
const stopRecording = () => {
  let preview = document.querySelector<HTMLVideoElementWithCaptureStream>('#video-recording-preview');
  if (preview) {
    _stopRecording(preview.srcObject as MediaStream);
  }
  state.isRecordingStarted = false;
  state.isRecordingFinished = true;
}
const useRecording = () => {
  state.isRecordingSendingToServer = true;
  const formData = new FormData();
  formData.append('file', state.recordedBlob);

  const xhr = new XMLHttpRequest();
  xhr.open('POST', uploadUrl);
  xhr.addEventListener("load", () => {
    let contentType = xhr.getResponseHeader("Content-Type"),
        isJsonResponse = (contentType && contentType.indexOf("application/json") !== -1),
        response = isJsonResponse ? JSON.parse(xhr.responseText) : xhr.responseText;

    state.isRecordingSendingToServer = false;
    if (xhr.status >= 200 && xhr.status < 300) {
      if (response.success) {
        state.videos.unshift(response.data);
        localStorage.setItem(`__gust_video_${props.product_id}`, response.data.id.toString());
        emitChange('video_id', response.data.id);
        state.videoType = 'uploaded';
      }
    } else {
      if (response.message) {
        Notify.error(response.message, 'Error!');
      }
    }
  });
  xhr.send(formData);
}
const emitChange = (type: string, value: any) => {
  emit('change', type, value);
}
const changeType = (type: 'video' | 'text' | '') => {
  state.messageType = type;
  emitChange('type', type);
  if ('video' === type && state.videos.length) {
    emitChange('video_id', state.videos[0].id);
  }
}
const clearVideoInnerMessage = () => {
  Dialog.confirm('Are you sure to clear all changes?').then(confirmed => {
    if (confirmed) {
      changeType('');
      clearVideoData();
      emitChange('message', '');
    }
  })
}
const onLengthError = (error: boolean) => {
  state.showLengthError = error;
}
const finishedEvent = (fileObject: Record<string, string>, response: ServerSuccessResponseInterface) => {
  if (response.success) {
    if (response.data.id) {
      state.videos.unshift(response.data);
      localStorage.setItem(`__gust_video_${props.product_id}`, response.data.id.toString());
      emitChange('video_id', response.data.id);
    } else {
      // Show processing status
      if (response.data.job_id) {
        state.job_id = response.data.job_id;
        localStorage.setItem(`__job_id_${props.product_id}`, response.data.job_id);
      }
    }
  }
}
const handleFileUploadFailed = (fileObject: Record<string, string>, response: ServerErrorResponseInterface) => {
  if (response.message) {
    Notify.error(response.message, 'Error!');
  }
}
const fetchVideos = () => {
  return new Promise(resolve => {
    let config = {};
    if (!isUserLoggedIn) {
      const localStorageData = localStorage.getItem(`__gust_video_${props.product_id}`);
      if (localStorageData) {
        config = {params: {videos: [parseInt(localStorageData)]}}
      }
    }
    axios.get(uploadUrl, config).then(response => {
      if (response.data.data) {
        state.videos = response.data.data;
      }
      resolve(response.data.data);
    })
  })
}
const clearVideo = () => {
  state.videos = [];
  localStorage.removeItem(`__gust_video_${props.product_id}`);
  emitChange('video_id', 0);
  clearVideoData();
}
const checkJobStatus = (jobId: string) => {
  state.isCheckingStatus = true;
  return new Promise(resolve => {
    axios
        .get('dynamic-cards/video/status', {params: {job_id: jobId}})
        .then(response => {
          let data = response.data.data;
          if (response.status === 200) {
            if (data.id) {
              state.videos.unshift(data);
              localStorage.setItem(`__gust_video_${props.product_id}`, data.id.toString());
              emitChange('video_id', data.id);
              state.job_id = '';
              localStorage.removeItem(`__job_id_${props.product_id}`);
            }
          }
          resolve(data);
        })
        .catch(error => {
          const errorData = error.response.data;
          if ('adult_content' === errorData.code) {
            Notify.error(errorData.message)

            state.job_id = '';
            localStorage.removeItem(`__job_id_${props.product_id}`);
          }
        })
        .finally(() => {
          state.isCheckingStatus = false;
        })
  })
}

watch(() => state.job_id, (newValue, oldValue) => {
  const clear = () => {
    return new Promise(resolve => {
      if (state.timer_id) {
        clearInterval(state.timer_id);
      }
      resolve(true);
    })
  }
  const everyFifteenSeconds = 15 * 1000;
  if (newValue.length && newValue !== oldValue) {
    clear().then(() => {
      state.timer_id = setInterval(() => {
        if (!state.isCheckingStatus) {
          checkJobStatus(newValue);
        }
      }, everyFifteenSeconds)
    })
  } else {
    clear();
  }
})

watch(() => props.innerMessage, (newValue: InnerMessagePropsInterface) => {
  if (newValue.type === 'text') {
    changeType('text');
  }
})

onMounted(() => {

  fetchVideos().then(data => {
    if (state.messageType === 'video') {
      emitChange('video_id', data[0].id);
    }
  });
  document.addEventListener('recordComplete', (event: CustomEvent) => {
    state.recordedBlob = event.detail;
  })
  // Check video job
  let job_id = localStorage.getItem(`__job_id_${props.product_id}`);
  if (job_id) {
    state.job_id = job_id;
    checkJobStatus(job_id);
  }
})
</script>
