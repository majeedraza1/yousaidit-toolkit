import ReactDOM from "react-dom";
import React, {StrictMode} from "react";
import Occasion from './Occasion'
import Recipient from './Recipient'
import Topic from './Topic'

const elOccasion = document.getElementById('ai_content_writer_occasion');
if (elOccasion) {
  ReactDOM.render(<StrictMode><Occasion/></StrictMode>, elOccasion);
}

const elRecipient = document.getElementById('ai_content_writer_recipient');
if (elRecipient) {
  ReactDOM.render(<StrictMode><Recipient/></StrictMode>, elRecipient);
}

const elTopic = document.getElementById('ai_content_writer_topic');
if (elTopic) {
  ReactDOM.render(<StrictMode><Topic/></StrictMode>, elTopic);
}
