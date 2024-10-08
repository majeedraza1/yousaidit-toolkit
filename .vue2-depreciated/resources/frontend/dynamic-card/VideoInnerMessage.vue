<template>
  <div class="flex w-full h-full">
    <div v-if="messageType === ''"
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
    <div v-if="messageType === 'text'" class="dynamic-card--editable-content-container w-full">
      <editable-content
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
      <div v-if="showLengthError" class="has-error p-2 my-4 absolute bottom-0">
        Oops... your message is too long, please keep inside the box.
      </div>
    </div>
    <div v-if="messageType === 'video'" class="flex flex-col justify-center items-center w-full h-full p-4 lg:p-8">
      <template v-if="videos.length < 1">
        <template v-if="videoType==='recorded'">
          <div class="mb-2 space-x-4">
            <shapla-button v-if="!isRecordingStarted" theme="primary" @click="startRecording">Start
              Recording
            </shapla-button>
            <shapla-button v-if="isRecordingStarted" theme="primary" @click="stopRecording">Stop Recording
            </shapla-button>
          </div>

          <div class="w-full">
            <div v-if="isRecordingStarted || isRecordingFinished" class="text-center mb-2 font-bold">
              Preview
            </div>
            <image-container v-show="isRecordingStarted" :width-ratio="1920" :height-ratio="1080">
              <video id="video-recording-preview" width="192" height="108" autoplay muted></video>
            </image-container>
            <image-container v-show="isRecordingFinished && !isRecordingStarted" :width-ratio="1920"
                             :height-ratio="1080">
              <video id="video-recording" width="192" height="108" controls></video>
            </image-container>
            <div class="mt-2 text-center space-x-2">
              <shapla-button v-if="isRecordingFinished" theme="primary" outline @click="cancelRecording">
                Cancel
              </shapla-button>
              <shapla-button v-if="isRecordingFinished" theme="primary"
                             :class="{'is-loading':isRecordingSendingToServer}" @click="useRecording"
              >Use this Recording
              </shapla-button>
            </div>
          </div>
        </template>

        <template v-if="videoType==='uploaded'">
          <template v-if="job_id">
            <div class="mb-2 w-full">
              <progress-bar theme="primary" striped animated/>
            </div>
            <div class="border-4 border-dashed border-primary p-2 text-lg font-bold text-center">
              Your video is being processed. It may take upto a minute. Please be patient.
            </div>
          </template>
          <template v-else>
            <file-uploader
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
              <shapla-button theme="primary" @click="videoType='recorded'">Record Video</shapla-button>
            </div>
          </template>
        </template>
      </template>
      <div v-if="videos.length" :style="{
                minWidth: '50%',
                textAlign: 'center',
                display: 'block',
                width:videos[0].width < videos[0].height?'auto':'100%'
            }">
        <image-container :width-ratio="videos[0].width" :height-ratio="videos[0].height">
          <video :width="videos[0].width" :height="videos[0].height" controls>
            <source :src="videos[0].url" :type="videos[0].type">
          </video>
        </image-container>
        <p class="mt-2">
          <shapla-button size="small" outline theme="primary" @click="clearVideo">Clear Video</shapla-button>
        </p>
      </div>
    </div>
    <template v-if="messageType === 'text' || messageType === 'video'">
      <shapla-button theme="primary" size="small" class="absolute right-2 top-3 text-bold"
                     @click="clearVideoInnerMessage">Back
      </shapla-button>
    </template>
  </div>
</template>

<script>
import EditableContent from "@/frontend/inner-message/EditableContent";
import {FileUploader, imageContainer, progressBar, shaplaButton} from "shapla-vue-components";
import axios from "@/utils/axios";
import {initRecording, stopRecording} from "@/frontend/dynamic-card/recording.ts";
import {Dialog, Notify} from "@shapla/vanilla-components";

export default {
  name: "VideoInnerMessage",
  components: {
    EditableContent,
    FileUploader,
    imageContainer,
    shaplaButton,
    progressBar,
  },
  props: {
    product_id: {default: 0},
    card_size: {default: ''},
    innerMessage: {default: ''},
    openAiEditable: {type: Boolean, default: false},
  },
  data() {
    return {
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
    }
  },
  computed: {
    isRecordingEnabled() {
      return window.StackonetToolkit.isRecordingEnabled;
    },
    maxUploadLimitText() {
      return window.StackonetToolkit.maxUploadLimitText;
    },
    fileUploaderTermsHTML() {
      return window.StackonetToolkit.fileUploaderTermsHTML;
    },
    videoMessagePriceHTML() {
      return window.StackonetToolkit.videoMessagePriceHTML;
    },
    qrCodePlayInfo() {
      return window.StackonetToolkit.qrCodePlayInfo;
    },
    uploadUrl() {
      return 'dynamic-cards/video';
    },
    isUserLoggedIn() {
      return window.StackonetToolkit.isUserLoggedIn || false;
    },
    headers() {
      const headers = {};
      if (window.StackonetToolkit.restNonce) {
        headers['X-WP-Nonce'] = window.StackonetToolkit.restNonce;
      }
      return headers
    },
  },
  watch: {
    job_id(newValue, oldValue) {
      const clear = () => {
        return new Promise(resolve => {
          if (this.timer_id) {
            clearInterval(this.timer_id);
          }
          resolve(true);
        })
      }
      const everyFifteenSeconds = 15 * 1000;
      if (newValue.length && newValue !== oldValue) {
        clear().then(() => {
          this.timer_id = setInterval(() => {
            if (!this.isCheckingStatus) {
              this.checkJobStatus(newValue);
            }
          }, everyFifteenSeconds)
        })
      } else {
        clear();
      }
    },
    innerMessage: {
      deep: true,
      handler(newValue) {
        if (newValue.type === 'text') {
          this.changeType('text');
        }
      }
    }
  },
  methods: {
    clearVideoData() {
      this.job_id = '';
      this.videoType = 'uploaded';
      this.emitChange('video_id', 0);
      localStorage.removeItem(`__gust_video_${this.product_id}`);
    },
    cancelRecording() {
      this.isRecordingStarted = false;
      this.isRecordingFinished = false;
      this.isRecordingSendingToServer = false;
      this.videoType = 'uploaded';
    },
    startRecording() {
      this.isRecordingStarted = true;
      initRecording();
    },
    stopRecording() {
      let preview = document.querySelector('#video-recording-preview');
      if (preview) {
        stopRecording(preview.srcObject);
      }
      this.isRecordingStarted = false;
      this.isRecordingFinished = true;
    },
    useRecording() {
      this.isRecordingSendingToServer = true;
      const formData = new FormData();
      formData.append('file', this.recordedBlob);

      const xhr = new XMLHttpRequest();
      xhr.open('POST', this.uploadUrl);
      xhr.addEventListener("load", () => {
        let contentType = xhr.getResponseHeader("Content-Type"),
            isJsonResponse = (contentType && contentType.indexOf("application/json") !== -1),
            response = isJsonResponse ? JSON.parse(xhr.responseText) : xhr.responseText;

        this.isRecordingSendingToServer = false;
        if (xhr.status >= 200 && xhr.status < 300) {
          if (response.success) {
            this.videos.unshift(response.data);
            localStorage.setItem(`__gust_video_${this.product_id}`, response.data.id.toString());
            this.emitChange('video_id', response.data.id);
            this.videoType = 'uploaded';
          }
        } else {
          if (response.message) {
            Notify.error(response.message, 'Error!');
          }
        }
      });
      xhr.send(formData);
    },
    emitChange(type, value) {
      this.$emit('change', type, value);
    },
    changeType(type) {
      this.messageType = type;
      this.emitChange('type', type);
      if ('video' === type && this.videos.length) {
        this.emitChange('video_id', this.videos[0].id);
      }
    },
    clearVideoInnerMessage() {
      Dialog.confirm('Are you sure to clear all changes?').then(confirmed => {
        if (confirmed) {
          this.changeType('');
          this.clearVideoData();
          this.emitChange('message', '');
        }
      })
    },
    onLengthError(error) {
      this.showLengthError = error;
    },
    beforeSendEvent(xhr) {
      if (window.StackonetToolkit.restNonce) {
        xhr.setRequestHeader('X-WP-Nonce', window.StackonetToolkit.restNonce);
      }
    },
    finishedEvent(fileObject, response) {
      if (response.success) {
        if (response.data.id) {
          this.videos.unshift(response.data);
          localStorage.setItem(`__gust_video_${this.product_id}`, response.data.id.toString());
          this.emitChange('video_id', response.data.id);
        } else {
          // Show processing status
          if (response.data.job_id) {
            this.job_id = response.data.job_id;
            localStorage.setItem(`__job_id_${this.product_id}`, response.data.job_id);
          }
        }
      }
    },
    handleFileUploadFailed(fileObject, response) {
      if (response.message) {
        Notify.error(response.message, 'Error!');
      }
    },
    fetchVideos() {
      return new Promise(resolve => {
        let config = {};
        if (!this.isUserLoggedIn) {
          const localStorageData = localStorage.getItem(`__gust_video_${this.product_id}`);
          if (localStorageData) {
            config = {params: {videos: [parseInt(localStorageData)]}}
          }
        }
        axios.get(this.uploadUrl, config).then(response => {
          if (response.data.data) {
            this.videos = response.data.data;
          }
          resolve(response.data.data);
        })
      })
    },
    clearVideo() {
      this.videos = [];
      localStorage.removeItem(`__gust_video_${this.product_id}`);
      this.emitChange('video_id', 0);
      this.clearVideoData();
    },
    checkJobStatus(jobId) {
      this.isCheckingStatus = true;
      return new Promise(resolve => {
        axios
            .get('dynamic-cards/video/status', {params: {job_id: jobId}})
            .then(response => {
              let data = response.data.data;
              if (response.status === 200) {
                if (data.id) {
                  this.videos.unshift(data);
                  localStorage.setItem(`__gust_video_${this.product_id}`, data.id.toString());
                  this.emitChange('video_id', data.id);
                  this.job_id = '';
                  localStorage.removeItem(`__job_id_${this.product_id}`);
                }
              }
              resolve(data);
            })
            .catch(error => {
              const errorData = error.response.data;
              if ('adult_content' === errorData.code) {
                Notify.error(errorData.message)

                this.job_id = '';
                localStorage.removeItem(`__job_id_${this.product_id}`);
              }
            })
            .finally(() => {
              this.isCheckingStatus = false;
            })
      })
    }
  },
  mounted() {
    this.fetchVideos().then(data => {
      if (this.messageType === 'video') {
        this.emitChange('video_id', data[0].id);
      }
    });
    document.addEventListener('recordComplete', (event) => {
      this.recordedBlob = event.detail;
    })
    // Check video job
    let job_id = localStorage.getItem(`__job_id_${this.product_id}`);
    if (job_id) {
      this.job_id = job_id;
      this.checkJobStatus(job_id);
    }
  }
}
</script>
