<script setup lang="ts">
import {Notify, Spinner} from "@shapla/vanilla-components";
import {ShaplaButton, ShaplaColumn, ShaplaColumns, ShaplaImage, ShaplaModal} from "@shapla/vue-components";
import {computed, reactive, ref} from "vue";
import axios from "../../utils/axios.ts";
import {ServerErrorResponseInterface} from "../../utils/CrudOperation.ts";

const emit = defineEmits<{
  aiImageGenerated: [value: any];
}>()

const termsAndConditionsUrl = window.StackonetToolkit.termsAndConditionsUrl;
const stability_ai = window.StackonetToolkit.stability_ai;
const occasions = stability_ai.occasions
const topics = stability_ai.topics;
const recipients = stability_ai.recipients;
const moods = stability_ai.moods;
const style_presets = stability_ai.style_presets;

const card_options = reactive({
  occasion: '',
  recipient: '',
  topic: '',
  custom_topic: '',
  mood: '',
  style_preset: 'cinematic',
})
const showStylePresetModal = ref<boolean>(false);
const canGenerate = computed<boolean>(() => {
  return !!(
      card_options.occasion.length &&
      card_options.recipient.length &&
      card_options.topic.length &&
      card_options.mood.length &&
      card_options.style_preset.length
  )
})

const selectStylePreset = (style_preset_slug: string) => {
  card_options.style_preset = style_preset_slug;
  showStylePresetModal.value = false;
}

const generateAiImage = () => {
  Spinner.show();
  const url = new URL(window.StackonetToolkit.ajaxUrl);
  url.searchParams.set('action', 'yousaidit_ai_image_generator');

  axios
      .post(url.toString(), card_options, {headers: {'Content-Type': 'multipart/form-data'}})
      .then(response => {
        const data = response.data.data;
        window.console.log(data);
        emit('aiImageGenerated', data);
        document.dispatchEvent(new CustomEvent("aiImageGenerated", {
          detail: data,
        }));
      })
      .catch(error => {
        const responseData = error.response.data as ServerErrorResponseInterface;
        if (responseData.message) {
          Notify.error(responseData.message, 'Error!');
        }
      })
      .finally(() => {
        Spinner.hide();
      })
}
</script>

<template>
  <div class="px-2">
    <div class="mb-4">
      <h2 class="text-lg text-center">AI Image Generator</h2>
      <div class="text-center text-sm">Use AI-generated images to compose the ideal card.</div>
      <div class="text-center text-sm">Choose the occasion and recipient to generate the message content.</div>
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
        <label for="style_preset" class="text-center">Style</label>
        <div class="bg-white border border-solid border-gray-200 p-1 flex justify-between">
          <span v-if="card_options.style_preset === 'none'" class="font-bold">No Style</span>
          <template v-for="_style_preset in style_presets">
            <span class="font-bold" v-if="_style_preset.slug === card_options.style_preset">
              {{ _style_preset.label }}
            </span>
          </template>
          <ShaplaButton theme="primary" outline size="small" @click="showStylePresetModal = true">Change</ShaplaButton>
        </div>
      </div>
      <div>
        <label for="Recipient" class="text-center">Mode</label>
        <select id="Recipient" v-model="card_options.mood">
          <option v-for="_mood in moods" :value="_mood.slug" :key="_mood.slug">
            {{ _mood.label }}
          </option>
        </select>
      </div>
      <div class="flex flex-col justify-center mt-4 mb-12 lg:mb-8">
        <ShaplaButton theme="primary" @click.prevent="generateAiImage" :disabled="!canGenerate">Generate</ShaplaButton>
        <div class="mt-2 text-sm">
          By using our AI image generator. you agree to our
          <a class="text-primary font-bold" :href="termsAndConditionsUrl">terms & conditions</a>
        </div>
      </div>
    </div>
  </div>
  <ShaplaModal title="Style" :active="showStylePresetModal" content-size="large"
               @close="showStylePresetModal = false">
    <ShaplaColumns multiline>
      <ShaplaColumn :tablet="3">
        <div class="border-4 border-solid hover:border-gray-400"
             :class="{
          'border-primary':'none' === card_options.style_preset,
          'border-transparent':'none' !== card_options.style_preset,
        }"
             @click="()=> selectStylePreset('none')"
        >
          <ShaplaImage>
            <svg xmlns="http://www.w3.org/2000/svg" width="150" height="150" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                 class="bg-gray-100">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line>
            </svg>
          </ShaplaImage>
          <div class="bg-primary text-on-primary font-bold text-center p-2">No Style</div>
        </div>
      </ShaplaColumn>
      <ShaplaColumn :tablet="3" v-for="_style_preset in style_presets" :key="_style_preset.slug">
        <div class="border-4 border-solid hover:border-gray-400"
             :class="{
          'border-primary':_style_preset.slug === card_options.style_preset,
          'border-transparent':_style_preset.slug !== card_options.style_preset,
        }"
             @click="()=> selectStylePreset(_style_preset.slug)"
        >
          <ShaplaImage>
            <img :src="_style_preset.icon" :alt="_style_preset.label">
          </ShaplaImage>
          <div class="bg-primary text-on-primary font-bold text-center p-2">{{ _style_preset.label }}</div>
        </div>
      </ShaplaColumn>
    </ShaplaColumns>
  </ShaplaModal>
</template>
