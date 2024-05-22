<script setup lang="ts">
import {onMounted, reactive} from "vue";
import CrudOperation from "../utils/CrudOperation.ts";
import http from "../utils/axios.ts";
import {SettingInterface, SettingResponseInterface, StabilityAiEngineInterface} from "./interfaces.ts";
import {ShaplaButton, ShaplaTab, ShaplaTabs} from "@shapla/vue-components";
import OccasionSettings from "./sections/OccasionSettings.vue";
import RecipientSettings from "./sections/RecipientSettings.vue";
import ModeSettings from "./sections/ModeSettings.vue";

const crud = new CrudOperation('admin/stability-ai-logs/settings', http);

const state = reactive<{
  readFromServer: boolean;
  editable: boolean;
  message: string;
  api_versions: StabilityAiEngineInterface[];
  settings: SettingInterface;
}>({
  readFromServer: false,
  editable: false,
  message: '',
  api_versions: [],
  settings: {
    api_key: '',
    api_version: 'stable-diffusion-v2',
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
    state.api_versions = data.api_versions;
  });
}

const updateSettings = () => {
  crud.createItem(state.settings).then((data: SettingResponseInterface) => {
    state.editable = data.editable;
    state.message = data.message;
    state.settings = data.settings;
    state.api_versions = data.api_versions;
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
  <ShaplaTabs alignment="center">
    <ShaplaTab name="API Settings" selected>
      <div>
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
              <th>API versions</th>
              <td>
                <label v-for="version in state.api_versions" :key="version.id"
                       class="flex space-x-2 items-center border border-solid border-gray-400 mb-2 rounded p-1">
                  <input type="radio" :value="version.id" v-model="state.settings.api_version"/>
                  <span>
              <span class="flex space-x-2">
                <strong>{{ version.name }}</strong>
                <span class="text-gray-400 italic">({{ version.id }})</span>
              </span>
              <span class="block description">{{ version.description }}</span>
            </span>
                </label>
              </td>
            </tr>
            <tr>
              <th>Prompt</th>
              <td>
                <textarea class="large-text code" :rows='5' v-model="state.settings.default_prompt"></textarea>
                <p class="description">
                  Available placeholders<br>
                  <span v-text="`{{occasion}}`"></span> to get occasion from user choice<br>
                  <span v-text="`{{recipient}}`"></span> to get recipient from user choice<br>
                  <span v-text="`{{mood}}`"></span> to get mood from user choice<br>
                  <span v-text="`{{topic}}`"></span> to get topic from user choice<br>
                  <span v-text="`{{style_preset}}`"></span> to get style preset from user choice<br>
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
      </div>
    </ShaplaTab>
    <ShaplaTab name="Other Settings">
      <table class="form-table">
        <tr>
          <th scope="row"><label for="inner_message_price">Occasion</label></th>
          <td>
            <OccasionSettings/>
          </td>
        </tr>
        <tr>
          <th scope="row"><label for="inner_message_price">Recipient</label></th>
          <td>
            <RecipientSettings/>
          </td>
        </tr>
        <tr>
          <th scope="row"><label for="inner_message_price">Mode</label></th>
          <td>
            <ModeSettings/>
          </td>
        </tr>
      </table>
    </ShaplaTab>
  </ShaplaTabs>
</template>
