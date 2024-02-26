<template>
  <ShaplaModal :active="active" @close="$emit('close')" title="Commission per sale (fix)">
    <div>
      <div class="flex space-x-4 w-full my-4 items-center">
        <div class="col-marketplace">Default Commission</div>
        <div v-for="size in card_sizes">
          <ShaplaInput :label="`${size}`" v-model="commission[size]"/>
        </div>
      </div>
      <ShaplaToggle name="Commission for marketplace" subtext="Leave empty to use default commission."
                    v-if="Object.keys(marketplace_commission).length">
        <div class="flex space-x-4 w-full my-4 items-center" v-for="marketplace in marketplaces" :key="marketplace">
          <div class="col-marketplace">{{ marketplace }}</div>
          <div v-for="size in card_sizes" :key="size">
            <ShaplaInput
                :label="`${size}`"
                :value="marketplace_commission[marketplace][size]"
                @input="updateMarketPlaceCommission($event,marketplace,size)"
            />
          </div>
        </div>
      </ShaplaToggle>
    </div>

    <div class="mt-4">
      <ShaplaInput
          type="textarea"
          label="Note to Designer (option)"
          v-model="note_to_designer"
      />
    </div>

    <template v-slot:foot>
      <ShaplaButton theme="primary" @click="updateCommission">Update</ShaplaButton>
    </template>
  </ShaplaModal>
</template>

<script lang="ts" setup>
import {ShaplaButton, ShaplaInput, ShaplaModal, ShaplaToggle} from '@shapla/vue-components';
import {onMounted, reactive, watch} from "vue";

const props = defineProps({
  active: {type: Boolean, default: false},
  card_id: {type: [String, Number], default: 0},
  card_sizes: {type: Array, default: () => []},
  marketplaces: {type: Array, default: () => []},
  value: {
    type: Object, default: () => {
    }
  },
  commissions: {}
})
const state = reactive({
  commission: {},
  marketplace_commission: {},
  note_to_designer: '',
})
const emit = defineEmits<{
  close: [],
  input: [value: any];
  submit: [comission: any, marketplace_commission: any];
}>()

watch(() => state.commission, newValue => emit('input', newValue), {deep: true});
watch(() => props.value, newValue => state.commission = newValue, {deep: true});

const getDefaultMarketPlaceCommission = (cards, marketplaces) => {
  let sizeObject = {}, marketplaceObject = {};
  cards.forEach(el => sizeObject[el] = '')
  marketplaces.forEach(el => marketplaceObject[el] = sizeObject);
  return JSON.parse(JSON.stringify(marketplaceObject));
}

const updateCommission = () => {
  emit('submit', state.commission, state.marketplace_commission);
}

const updateMarketPlaceCommission = (event, marketplace, size) => {
  state.marketplace_commission[marketplace][size] = event;
}

onMounted(() => {
  state.commission = props.value;
  state.marketplace_commission = Object.assign(
      getDefaultMarketPlaceCommission(props.card_sizes, props.marketplaces),
      state.commissions
  );
})
</script>
