import $ from "jquery";

import './admin-art-work/main.ts'
import './admin-designers/main.ts'
import './admin-font-manager/main.ts'
import './admin-inner-message-editor/main.ts'
import './admin-order-dispatcher/main.ts'
import './admin-reminders/main.ts'
import './admin-settings-ai-content-writer/main.ts'
import './admin-settings-dispatch-timer/main.ts'
import './admin-stability-ai/main.ts'
import './admin-tree-planting/main.ts'
import './scss/admin.scss'

if (typeof $.fn.select2 === "function") {
  $("#inner_message_visible_on_cat").select2();
  $("#other_products_tab_categories").select2();
  $("#card_popup_categories").select2();
  $("#mug_uploader_categories").select2();
}