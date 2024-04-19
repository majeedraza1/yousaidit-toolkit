<script setup lang="ts">
import {onMounted, reactive} from "vue";
import CrudOperation from "../utils/CrudOperation.ts";
import http from "../utils/axios.ts";
import {SettingInterface, SettingResponseInterface, StabilityAiEngineInterface} from "./interfaces.ts";
import {ShaplaButton} from "@shapla/vue-components";

const crud = new CrudOperation('admin/stability-ai-logs/settings', http);

const state = reactive<{
  readFromServer: boolean;
  editable: boolean;
  message: string;
  style_presets: string[];
  engines: StabilityAiEngineInterface[];
  settings: SettingInterface;
}>({
  readFromServer: false,
  editable: false,
  message: '',
  engines: [],
  style_presets: [],
  settings: {
    api_key: '',
    engine_id: 'stable-diffusion-v1-6',
    style_preset: '',
    default_prompt: '',
    file_naming_method: 'uuid',
    max_allowed_images_for_guest_user: 5,
    max_allowed_images_for_auth_user: 20,
    remove_images_after_days: 30,
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
          <input type="text" class="regular-text" v-model="state.settings.api_key"/>
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
          <textarea class="large-text code" :rows='5' v-model="state.settings.default_prompt"></textarea>
          <p class="description">
            Available placeholders<br>
            <span v-text="`{{title}}`"></span> to get post title
          </p>
        </td>
      </tr>
      <tr>
        <th>Daily maximum allowed images for guest user</th>
        <td>
          <input type="number" class="regular-text" v-model="state.settings.max_allowed_images_for_guest_user"/>
        </td>
      </tr>
      <tr>
        <th>Daily maximum allowed images for auth user</th>
        <td>
          <input type="number" class="regular-text" v-model="state.settings.max_allowed_images_for_auth_user"/>
        </td>
      </tr>
      <tr>
        <th>Keep images on server (in days)</th>
        <td>
          <input type="number" class="regular-text" v-model="state.settings.remove_images_after_days"/>
        </td>
      </tr>
    </table>
    <p class="submit">
      <button class="button button-primary" @click="updateSettings">
        Save Changes
      </button>
    </p>
    <div v-if="!state.editable" class="absolute top-0 left-0 w-full h-full bg-red-200 bg-opacity-25"></div>
  </div>
  <div v-if="state.editable" class="bg-white p-1 rounded border border-solid border-red-600">
    <p class="text-base font-bold m-0">
      You can also define the setting via 'wp-config.php' file.
      Add the following code to 'wp-config.php' file.
    </p>
    <div class="p-2 mt-2">
      <pre class="flex flex-col bg-gray-100 overflow-x-auto m-0">
        <code class="w-full bg-gray-100">define( 'STABILITY_AI_SETTINGS', json_encode( [</code>
        <code class="w-full bg-gray-100">    'api_key' =&gt; '{{ state.settings.api_key }}',</code>
        <code class="w-full bg-gray-100">    'engine_id' =&gt; '{{ state.settings.engine_id }}',</code>
        <code class="w-full bg-gray-100">    'style_preset' =&gt; '{{ state.settings.style_preset }}',</code>
        <code class="w-full bg-gray-100">    'default_prompt' =&gt; '{{ state.settings.default_prompt }}',</code>
        <code class="w-full bg-gray-100">    'max_allowed_images_for_guest_user' =&gt; {{
            state.settings.max_allowed_images_for_guest_user
          }},</code>
        <code class="w-full bg-gray-100">    'max_allowed_images_for_auth_user' =&gt; {{
            state.settings.max_allowed_images_for_auth_user
          }},</code>
        <code class="w-full bg-gray-100">    'remove_images_after_days' =&gt; {{
            state.settings.remove_images_after_days
          }},</code>
        <code class="w-full bg-gray-100">    'file_naming_method' =&gt; '{{
            state.settings.file_naming_method
          }}',</code>
        <code class="w-full bg-gray-100">] ) );</code>
      </pre>
    </div>
  </div>
</template>
