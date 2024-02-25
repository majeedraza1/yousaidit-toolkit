<template>
	<div class="p-4 md:w-6/12 lg:w-4/12 xl:w-3/12">
		<div class="shadow p-4 bg-white h-full flex flex-col">
			<div>
				<strong>{{ item.width }}</strong>x<strong>{{ item.height }}</strong>
				<small>pdf size</small>
			</div>
			<div>
				<strong>{{ item.card_size }}</strong> card
				<template v-if="item.card_type === 'dynamic'">
					- <span class="text-primary">Dynamic</span>
				</template>
			</div>
			<div>Total <strong>{{ item.items.length }}</strong> Item(s)</div>
			<div>{{ item.inner_message ? 'Contain Inner Message' : '&nbsp;' }}</div>
			<div class="flex-grow"></div>
			<div v-if="item.card_type === 'dynamic' && dynamic_card.generating"
			     class="text-xs border border-primary border-solid p-1">
				Generating: {{ dynamic_card.items_to_generate }}<br>
				<div v-if="dynamic_card.success_items">Success: {{ dynamic_card.success_items }}</div>
				<div v-if="dynamic_card.error_items">Error: {{ dynamic_card.error_items }}</div>
			</div>
			<div class="mt-4 flex space-y-2 flex-wrap">
				<ShaplaButton v-if="item.card_type === 'dynamic' && item.to_generate.length"
				               :class="{'is-loading':dynamic_card.generating}" size="small" fullwidth
				               @click="handleDynamicCardGeneration(item)">Generate Dynamic Card
				</ShaplaButton>
				<ShaplaButton v-if="item.inner_message" theme="default" size="small" fullwidth target="_blank"
				               :href="get_pdf_url('im')">Merge Inner Message
				</ShaplaButton>
				<ShaplaButton theme="secondary" outline size="small" fullwidth target="_blank"
				               :href="get_pdf_url('pdf')">Merge PDF
				</ShaplaButton>
				<ShaplaButton v-if="item.inner_message" theme="primary" size="small" fullwidth target="_blank"
				               :href="get_pdf_url('both')"> Merge PDF & Inner Message
				</ShaplaButton>
			</div>
		</div>
	</div>
</template>

<script lang="ts" setup>
import axios from "../../utils/axios";
import {ShaplaButton} from "@shapla/vue-components";
import {Dialog} from "@shapla/vanilla-components";
import {ref} from "vue";

const dynamicCardDefault = {
	generating: false,
	items_to_generate: 0,
	remaining_items: 0,
	success_items: 0,
	error_items: 0,
}

const emit = defineEmits<{
  "need-force-refresh":[]
}>();
const props = defineProps({
  item: {type: Object},
})

const dynamic_card = ref(JSON.parse(JSON.stringify(dynamicCardDefault)))


const get_pdf_url = (type = 'both')=> {
  let _items = props.item.items.map(el => el.shipStation_order_id);
  let _url = new URL(window.StackonetToolkit.ajaxUrl),
      params = _url.searchParams;
  params.set('action', 'yousaidit_download_pdf');
  params.set('type', type);
  params.set('card_size', props.item.card_size);
  params.set('card_width', props.item.width);
  params.set('card_height', props.item.height);
  params.set('inner_message', props.item.inner_message);
  params.set('card_type', props.item.card_type);
  params.set('ids', _items.toString());
  return _url.toString();
}
const generate_dynamic_pdf = (wc_order_id, wc_order_item_id)=> {
  let _url = new URL(window.StackonetToolkit.ajaxUrl),
      params = _url.searchParams;
  params.set('action', 'generate_dynamic_card_pdf');
  params.set('order_id', wc_order_id);
  params.set('order_item_id', wc_order_item_id);
  return new Promise((resolve, reject) => {
    axios.get(_url.toString()).then(response => {
      resolve(response.data.data);
    }).catch(error => {
      reject(error.response.data);
    })
  })
}
const handleDynamicCardGeneration = (item)=> {
  Dialog.confirm(
      'Generating all dynamic card is a CPU resource consuming task.',
      {title: 'Are you Sure?'}
  )
      .then((confirmed) => {
        if (confirmed) {
          dynamic_card.value.generating = true;
          dynamic_card.value.items_to_generate = item.to_generate.length;
          dynamic_card.value.remaining_items = item.to_generate.length;
          item.to_generate.forEach(_item => {
            generate_dynamic_pdf(_item.wc_order_id, _item.wc_order_item_id).then(() => {
              dynamic_card.value.success_items += 1;
            }).catch(() => {
              dynamic_card.value.error_items += 1;
            }).finally(() => {
              dynamic_card.value.remaining_items -= 1;

              if (dynamic_card.value.remaining_items < 1) {
                dynamic_card.value.generating = false;
                dynamic_card.value = JSON.parse(JSON.stringify(dynamicCardDefault));
                emit('need-force-refresh');
              }
            });
          })
        }
      })
}
</script>
