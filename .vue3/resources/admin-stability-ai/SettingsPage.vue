<script setup lang="ts">
import {onMounted, reactive} from "vue";
import CrudOperation from "../utils/CrudOperation.ts";
import http from "../utils/axios.ts";
import {SettingResponseInterface} from "./interfaces.ts";
import {ShaplaButton} from "@shapla/vue-components";

const crud = new CrudOperation('admin/stability-ai-logs/settings', http);

const state = reactive({
  readFromServer: false,
  editable: false,
  message: '',
  engines: [],
  style_presets: [],
  settings: {
    apiKey: '',
    engine_id: 'stable-diffusion-v1-6',
    style_preset: '',
    imageWidth: 1280,
    imageHeight: 720,
    defaultPrompt: '',
    autoGenerateThumbnail: false,
    generateThumbnailFor: ['post'],
    fileNamingMethod: 'post_title',
  },
});

const getSettings = () => {
  crud.getItems().then((data: SettingResponseInterface) => {
    state.editable = data.editable;
    state.message = data.message;
    state.settings = data.settings;
    state.engines = data.engines;
    state.style_presets = data.style_presets;
  });
}

const updateSettings = () => {
  crud.createItem(state.settings).then((data: SettingResponseInterface) => {
    state.editable = data.editable;
    state.message = data.message;
    state.settings = data.settings;
    state.engines = data.engines;
    state.style_presets = data.style_presets;
  });
}

onMounted(() => {
  getSettings();
})
</script>

<template>
  <div class="flex w-full items-center">
    <h1 class="wp-heading-inline">Stability Api Settings</h1>
    <div class="flex-grow"></div>
    <ShaplaButton theme="primary" size="small" outline @click="getSettings">Refresh</ShaplaButton>
  </div>
  <hr class="wp-header-end"/>
  <div v-if="!state.editable" class="w-full p-4 bg-red-100 border border-solid border-red-600 rounded">
    {{ state.message }}
  </div>
  <div class="relative mb-4">
    <table class="form-table">
      <tr>
        <th>Stability.ai API Key</th>
        <td>
          <input type="text" class="regular-text" v-model="state.settings.apiKey"/>
        </td>
      </tr>
      <tr>
        <th>Engine</th>
        <td>
          <label v-for="engine in state.engines" :key="engine.id"
                 class="flex space-x-2 items-center border border-solid border-gray-400 mb-2 rounded p-1">
            <input type="radio" :value="engine.id" v-model="state.settings.engine_id"/>
            <span>
              <span class="flex space-x-2">
                <strong>{{ engine.name }}</strong>
                <span class="text-gray-400 italic">({{ engine.id }})</span>
              </span>
              <span class="block description">{{ engine.description }}</span>
            </span>
          </label>
        </td>
      </tr>
      <tr>
        <th>Style Preset</th>
        <td>
          <select v-model="state.settings.style_preset">
            <option value=''>None</option>
            <option v-for="preset in state.style_presets" :value='preset'>{{ preset }}</option>
          </select>
        </td>
      </tr>
      <tr>
        <th>Prompt</th>
        <td>
          <textarea class="large-text code" :rows='5' v-model="state.settings.defaultPrompt"></textarea>
          <p class="description">
            Available placeholders<br>
            <span v-text="`{{title}}`"></span> to get post title
          </p>
        </td>
      </tr>
    </table>
    <div v-if="!state.editable" class="absolute top-0 left-0 w-full h-full bg-red-200 bg-opacity-25"></div>
  </div>
  <p class="submit">
    <button class="button button-primary" @click="updateSettings">
      Save Changes
    </button>
  </p>
  <div v-if="state.editable" class="bg-white p-1 rounded border border-solid border-red-600">
    <p class="text-base font-bold m-0">
      You can also define the setting via 'wp-config.php' file.
      Add the following code to 'wp-config.php' file.
    </p>
    <div class="p-2 mt-2">
      <pre class="flex flex-col bg-gray-100 overflow-x-auto m-0">
        <code class="w-full bg-gray-100">define( 'STABILITY_AI_SETTINGS', json_encode( [</code>
        <code class="w-full bg-gray-100">    'apiKey' =&gt; '{{ state.settings.apiKey }}',</code>
        <code class="w-full bg-gray-100">    'engine_id' =&gt; '{{ state.settings.engine_id }}',</code>
        <code class="w-full bg-gray-100">    'style_preset' =&gt; '{{ state.settings.style_preset }}',</code>
        <code class="w-full bg-gray-100">    'imageWidth' =&gt; {{ state.settings.imageWidth }},</code>
        <code class="w-full bg-gray-100">    'imageHeight' =&gt; {{ state.settings.imageHeight }},</code>
        <code class="w-full bg-gray-100">    'defaultPrompt' =&gt; '{{ state.settings.defaultPrompt }}',</code>
        <code class="w-full bg-gray-100">    'fileNamingMethod' =&gt; '{{ state.settings.fileNamingMethod }}',</code>
        <code
            class="w-full bg-gray-100">    'autoGenerateThumbnail' =&gt; {{
            state.settings.autoGenerateThumbnail ? 'true' : 'false'
          }},</code>
        <code class="w-full bg-gray-100">] ) );</code>
      </pre>
    </div>
  </div>
</template>
