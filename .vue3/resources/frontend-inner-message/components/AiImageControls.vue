<script setup lang="ts">
import {Notify, Spinner} from "@shapla/vanilla-components";
import {ShaplaButton} from "@shapla/vue-components";
import {reactive} from "vue";
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

const card_options = reactive({
  occasion: '',
  recipient: '',
  topic: '',
  custom_topic: '',
  mood: '',
})

const generateAiImage = () => {
  Spinner.show();
  const url = new URL(window.StackonetToolkit.ajaxUrl);
  url.searchParams.set('action', 'yousaidit_ai_image_generator');
  // url.searchParams.set('occasion', card_options.occasion);
  // url.searchParams.set('recipient', card_options.recipient);
  // url.searchParams.set('topic', card_options.topic);
  // url.searchParams.set('custom_topic', card_options.custom_topic);
  // url.searchParams.set('mood', card_options.mood);

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
        <label for="Recipient" class="text-center">Mode</label>
        <select id="Recipient" v-model="card_options.mood">
          <option v-for="_mood in moods" :value="_mood.slug" :key="_mood.slug">
            {{ _mood.label }}
          </option>
        </select>
      </div>
      <div class="flex flex-col justify-center mt-4 mb-12 lg:mb-8">
        <ShaplaButton theme="primary" @click.prevent="generateAiImage">Generate</ShaplaButton>
        <div class="mt-2 text-sm">
          By using our AI image generator. you agree to our
          <a class="text-primary font-bold" :href="termsAndConditionsUrl">terms & conditions</a>
        </div>
      </div>
    </div>
  </div>
</template>
