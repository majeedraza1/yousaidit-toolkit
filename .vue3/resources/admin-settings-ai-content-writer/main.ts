import {createApp} from "vue";
import OccasionSettings from "./OccasionSettings.vue";
import RecipientSettings from "./RecipientSettings.vue";
import TopicSettings from "./TopicSettings.vue";

const elOccasion = document.getElementById('ai_content_writer_occasion');
if (elOccasion) {
  const app = createApp(OccasionSettings)
  app.mount(elOccasion);
}
const elRecipient = document.getElementById('ai_content_writer_recipient');
if (elRecipient) {
  const app = createApp(RecipientSettings)
  app.mount(elRecipient);
}
const elTopic = document.getElementById('ai_content_writer_topic');
if (elTopic) {
  const app = createApp(TopicSettings)
  app.mount(elTopic);
}