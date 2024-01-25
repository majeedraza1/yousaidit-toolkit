<template>
  <tabs centred>
    <tab name="Font" selected>
      <div class="font-normal px-4 text-center">Choose Font Family</div>
      <div class="inner-message-font-families">
        <div
            class="inner-message-font-family"
            v-for="_font in font_families"
            :class="{'is-selected':options.font_family === _font.fontFamily}"
            :style="`font-family:${_font.fontFamily}`"
            @click="setFontFamily(_font)"
        >{{ _font.label }}
        </div>
      </div>
    </tab>
    <tab name="Size">
      <div class="font-normal px-4 text-center">Choose Font Size</div>
      <div class="inner-message-font-sizes flex flex-wrap justify-center p-4">
        <div class="inner-message-font-size" v-for="_size in font_sizes" :key="_size">
          <radio-button :label="_size" :value="_size" v-model="options.font_size"
                        :theme="options.font_size === _size?'primary':'default'"/>
        </div>
      </div>
    </tab>
    <tab name="Align">
      <template v-slot:name>
				<span class="w-6 h-6 inline-flex justify-center items-center">
					<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px">
						<path d="M0 0h24v24H0z" fill="none"/>
						<path d="M7 15v2h10v-2H7zm-4 6h18v-2H3v2zm0-8h18v-2H3v2zm4-6v2h10V7H7zM3 3v2h18V3H3z"/>
					</svg>
				</span>
      </template>
      <div class="font-normal px-4 text-center">Choose Text Alignment</div>
      <div class="inner-message-text-alignments flex flex-wrap p-4">
        <div class="inner-message-text-alignment flex-grow" v-for="_alignment in alignments"
             :key="_alignment.value">
          <radio-button
              fullwidth
              :label="_alignment.label"
              :value="_alignment.value"
              v-model="options.alignment"
              :theme="options.alignment === _alignment.value?'primary':'default'"/>
        </div>
      </div>
    </tab>
    <tab name="Color">
      <template v-slot:name>
        <span class="inline-flex w-6 h-6 bg-black"/>
      </template>
      <div class="font-normal px-4 text-center">Choose Text Color</div>
      <div class="inner-message-colors flex flex-wrap justify-center p-4">
        <div v-for="_color in colors" :key="_color.hex" class="inner-message-color p-3">
          <div @click="setFontColor(_color.hex)" :title="_color.label" :style="`background:${_color.hex}`"
               class="color-box" :class="{'is-active':options.color === _color.hex}">{{ _color }}
          </div>
        </div>
      </div>
    </tab>
    <tab name="Emoji" nav-item-class="is-hidden-mobile">
      <template v-slot:name>
        <span class="inline-flex w-6 h-6 text-xl justify-center items-center">😁</span>
      </template>
      <div class="font-normal px-4 pb-4 text-center">Choose Emoji</div>
      <emoji-picker @select="selectEmoji"/>
    </tab>
    <tab name="AI Writer">
      <template v-slot:name>
        <svg viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd"
             stroke-linejoin="round" stroke-miterlimit="2" class="w-6 h-6 fill-current">
          <path
              d="M474.123 209.81c11.525-34.577 7.569-72.423-10.838-103.904-27.696-48.168-83.433-72.94-137.794-61.414a127.14 127.14 0 00-95.475-42.49c-55.564 0-104.936 35.781-122.139 88.593-35.781 7.397-66.574 29.76-84.637 61.414-27.868 48.167-21.503 108.72 15.826 150.007-11.525 34.578-7.569 72.424 10.838 103.733 27.696 48.34 83.433 73.111 137.966 61.585 24.084 27.18 58.833 42.835 95.303 42.663 55.564 0 104.936-35.782 122.139-88.594 35.782-7.397 66.574-29.76 84.465-61.413 28.04-48.168 21.676-108.722-15.654-150.008v-.172zm-39.567-87.218c11.01 19.267 15.139 41.803 11.354 63.65-.688-.516-2.064-1.204-2.924-1.72l-101.152-58.49a16.965 16.965 0 00-16.687 0L206.621 194.5v-50.232l97.883-56.597c45.587-26.32 103.732-10.666 130.052 34.921zm-227.935 104.42l49.888-28.9 49.887 28.9v57.63l-49.887 28.9-49.888-28.9v-57.63zm23.223-191.81c22.364 0 43.867 7.742 61.07 22.02-.688.344-2.064 1.204-3.097 1.72L186.666 117.26c-5.161 2.925-8.258 8.43-8.258 14.45v136.934l-43.523-25.116V130.333c0-52.64 42.491-95.13 95.131-95.302l-.172.172zM52.14 168.697c11.182-19.268 28.557-34.062 49.544-41.803V247.14c0 6.02 3.097 11.354 8.258 14.45l118.354 68.295-43.695 25.288-97.711-56.425c-45.415-26.32-61.07-84.465-34.75-130.052zm26.665 220.71c-11.182-19.095-15.139-41.802-11.354-63.65.688.516 2.064 1.204 2.924 1.72l101.152 58.49a16.965 16.965 0 0016.687 0l118.354-68.467v50.232l-97.883 56.425c-45.587 26.148-103.732 10.665-130.052-34.75h.172zm204.54 87.39c-22.192 0-43.867-7.741-60.898-22.02a62.439 62.439 0 003.097-1.72l101.152-58.317c5.16-2.924 8.429-8.43 8.257-14.45V243.527l43.523 25.116v113.022c0 52.64-42.663 95.303-95.131 95.303v-.172zM461.22 343.303c-11.182 19.267-28.729 34.061-49.544 41.63V264.687c0-6.021-3.097-11.526-8.257-14.45L284.893 181.77l43.523-25.116 97.883 56.424c45.587 26.32 61.07 84.466 34.75 130.053l.172.172z"
              fill-rule="nonzero"/>
        </svg>
      </template>
      <div class="px-2">
        <div class="mb-4">
          <h2 class="text-lg text-center">AI Message Writer</h2>
          <div class="text-center text-sm">Use AI-generated content to compose the ideal message.</div>
          <div class="text-center text-sm">Choose the occasion and recipient to generate the message
            content.
          </div>
        </div>
        <div class="px-2">
          <div>
            <label for="occasion" class="text-center">Occasion</label>
            <select id="occasion" v-model="card_options.occasion">
              <option v-for="_occasion in occasions" :value="_occasion.slug" :key="_occasion.slug">{{
                  _occasion.label
                }}
              </option>
            </select>
          </div>
          <div>
            <label for="Recipient" class="text-center">Recipient</label>
            <select id="Recipient" v-model="card_options.recipient">
              <option v-for="_recipient in recipients" :value="_recipient.slug" :key="_recipient.slug">
                {{ _recipient.label }}
              </option>
            </select>
          </div>
          <div>
            <label for="Topic" class="text-center">Topic/Interests (optional)</label>
            <select id="Topic" v-model="card_options.topic">
              <option v-for="_topic in topics" :value="_topic.slug" :key="_topic.slug">
                {{ _topic.label }}
              </option>
              <option value="__custom">Custom, Give me to write my own topic</option>
            </select>
            <div class="mt-4" v-if="'__custom' === card_options.topic">
              <input type="text" v-model="card_options.custom_topic" placeholder="Write your topic">
            </div>
          </div>
          <div>
            <label class="text-center">Make it a Poem?</label>
            <div class="flex justify-center">
              <div class="w-8 h-8 flex justify-center">
                <shapla-checkbox v-model="card_options.poem"/>
              </div>
            </div>
          </div>
          <div class="flex justify-center mt-4 mb-12 lg:mb-8">
            <shapla-button theme="primary" @click="generateContent">Write</shapla-button>
          </div>
        </div>
      </div>
    </tab>
  </tabs>
</template>

<script>
import {radioButton, shaplaButton, shaplaCheckbox, tab, tabs} from 'shapla-vue-components'
import EmojiPicker from "./EmojiPicker";
import fontFamilies from "./font-family";
import colors from "./colors";
import http from "@/utils/axios";
import {Notify, Spinner} from "@shapla/vanilla-components";

export default {
  name: "EditorControls",
  components: {tabs, tab, radioButton, EmojiPicker, shaplaButton, shaplaCheckbox},
  props: {
    value: {
      type: Object, default: () => {
        return {
          font_family: '',
          font_size: '',
          alignment: '',
          color: '',
        }
      }
    }
  },
  data() {
    return {
      options: {
        font_family: '',
        font_size: '',
        alignment: '',
        color: '',
      },
      font_sizes: ['12', '14', '16', '18', '20', '22', '24', '26', '28', '30', '32', '34', '36', '38', '40'],
      alignments: [
        {label: 'Left', value: 'left'},
        {label: 'Center', value: 'center'},
        {label: 'Right', value: 'right'},
      ],
      card_options: {
        occasion: '',
        recipient: '',
        topic: '',
        custom_topic: '',
        poem: false
      }
    }
  },
  computed: {
    font_families() {
      return window.YousaiditFontsList.filter(font => font.for_public);
    },
    colors() {
      return colors;
    },
    occasions() {
      return window.StackonetToolkit.occasions;
    },
    topics() {
      return window.StackonetToolkit.topics;
    },
    recipients() {
      return window.StackonetToolkit.recipients;
    }
  },
  watch: {
    value: {
      deep: true,
      handler(newValue) {
        this.options = newValue;
      }
    },
    options: {
      deep: true,
      handler(newValue) {
        this.$emit('input', newValue);
      }
    }
  },
  methods: {
    setFontFamily(font) {
      this.options.font_family = font.fontFamily;
      this.emitChange('font-family', font);
    },
    setFontColor(hexColor) {
      this.options.color = hexColor;
      this.emitChange('color', hexColor);
    },
    selectEmoji(emoji) {
      this.emitChange('emoji', emoji);
    },
    emitChange(type, value) {
      this.$emit('change', {key: type, payload: value});
    },
    generateContent() {
      Spinner.show();
      http
          .post('ai-content-generator', this.card_options)
          .then(response => {
            const data = response.data.data;
            this.$emit('generateContent', {
              options: this.card_options,
              lines: data.message
            });
            document.dispatchEvent(new CustomEvent("openAiContent", {
              detail: {
                lines: data.message
              },
            }));
          })
          .catch(errors => {
            if (typeof errors.response.data.message === "string") {
              Notify.error(errors.response.data.message, 'Error!');
            }
          })
          .finally(() => {
            Spinner.hide();
          })
    }
  },
  mounted() {
    this.options = this.value;
  }
}
</script>
