<script setup lang="ts">
import {ShaplaColumn, ShaplaColumns, ShaplaInput, ShaplaSelect, ShaplaSwitch} from "@shapla/vue-components";
import {computed, onMounted, PropType, ref, watch} from "vue";
import useDesignerDashboardStore from "../store.ts";
import {CardOptionInterface} from "../../interfaces/designer-card.ts";

const store = useDesignerDashboardStore();
const emit = defineEmits<{
  "update:modelValue": [value: CardOptionInterface]
}>();

const props = defineProps({
  modelValue: {type: Object as PropType<CardOptionInterface>, default: () => ({})},
  errors: {type: Object as PropType<Record<string, string[]>>, default: () => ({})},
  isMug: {type: Boolean, default: false},
  showMarketPlaces: {type: Boolean, default: false}
})
const card = ref<CardOptionInterface>({
  title: '',
  description: '',
  sizes: ['square'],
  categories_ids: [],
  tags_ids: [],
  attributes: {},
  market_places: ['yousaidit'],
  rude_card: 'no',
  has_suggest_tags: 'no',
  suggest_tags: '',
})

const categories = computed(() => {
  if (props.isMug) {
    return store.mug_categories;
  }
  return store.card_categories;
})

watch(() => props.modelValue, newValue => {
  card.value = newValue;
}, {deep: true})

watch(() => card.value, (newValue: CardOptionInterface) => {
  emit('update:modelValue', newValue);
})

onMounted(() => {
  card.value = props.modelValue;
})
</script>

<template>
  <ShaplaColumns multiline v-if="Object.keys(card).length">
    <ShaplaColumn :tablet="12">
      <div class="w-full text-center">
        <h2 class="text-xl">Card Details</h2>
      </div>
    </ShaplaColumn>
    <ShaplaColumn :tablet="12">
      <div class="flex items-center">
        <div class="mr-4">
          <ShaplaSwitch v-model="card.rude_card" true-value="yes" false-value="no"/>
        </div>
        <span class="flex-grow">
					<span
              class="bg-primary text-on-primary text-sm py-4 px-2 inline-block">Is your design classed as a rude card?</span>
					<span class="bg-gray-100 text-sm py-4 px-2 inline-block">We class rude cards what contain words, phrases or themes of an adult content: i.e. swearing or innuendos.</span>
				</span>
      </div>
    </ShaplaColumn>
    <ShaplaColumn :tablet="6">
      <ShaplaInput
          type="textarea" v-model="card.title" label="Title"
          :has-error="!!errors.title" :validation-text="errors.title?errors.title[0]:''" :rows="1"
          help-text="Write card title. Card title will be used as product title."
      />
    </ShaplaColumn>
    <ShaplaColumn :tablet="6" style="display: none">
      <ShaplaSelect
          v-model="card.sizes" :options="store.card_sizes" label="Size" multiple
          :has-error="!!errors.sizes" :validation-text="errors.sizes?errors.sizes[0]:''"
          help-text="Choose card size(s). You need to upload file for each selected size on next step."
      />
    </ShaplaColumn>
    <ShaplaColumn :tablet="6">
      <ShaplaSelect
          v-model="card.categories_ids" :options="categories" label="Category"
          label-key="name" value-key="id" multiple
          :searchable="categories.length > 5"
          singular-selected-text="category selected"
          plural-selected-text="categories selected"
          :has-error="!!errors.categories_ids"
          :validation-text="errors.categories_ids?errors.categories_ids[0]:''"
          help-text="Choose card category. Try to choose only one category but not more than three categories."
      />
    </ShaplaColumn>
    <ShaplaColumn :tablet="6">
      <ShaplaSelect
          v-model="card.tags_ids" :options="store.card_tags" label="Tags"
          label-key="name" value-key="id" multiple searchable
          singular-selected-text="tag selected"
          plural-selected-text="tags selected"
          :has-error="!!errors.tags_ids"
          :validation-text="errors.tags_ids?errors.tags_ids[0]:''"
          help-text="Choose card tags. Choose as many tags as you need. Make sure tags are relevant to your card."
      />
    </ShaplaColumn>
    <ShaplaColumn :tablet="6">
      <div class="additional_tags">
        <ShaplaSwitch v-model="card.has_suggest_tags" true-value="yes" false-value="no" label="Suggest a new tag."/>
        <ShaplaInput
            v-if="card.has_suggest_tags === 'yes'" label="Tags" type="textarea" :rows="1"
            help-text="Write your suggested tags, separate by comma if you have multiple suggestion"
            v-model="card.suggest_tags"
        />
      </div>
    </ShaplaColumn>
    <ShaplaColumn :tablet="6" v-for="attribute in store.card_attributes" :key="attribute.attribute_name"
                  v-if="store.card_attributes.length">
      <ShaplaSelect
          v-model="card.attributes[attribute.attribute_name]"
          :options="attribute.options" :label="attribute.attribute_label"
          label-key="name" value-key="id" multiple :searchable="attribute.options.length > 5"
          :has-error="!!(errors.attributes && errors.attributes[attribute.attribute_name])"
          :validation-text="errors.attributes?errors.attributes[0]:''"
      />
    </ShaplaColumn>
    <ShaplaColumn :tablet="12">
      <div class="card-description">
        <h3 class="font-normal text-lg text-primary uppercase">Description</h3>
        <h4 class="font-normal text-sm">Card description will be visible on product detail page. Be creative as
          you want, this is great for SEO.</h4>
        <ShaplaInput
            type="textarea" v-model="card.description"
            label="Description"
            :has-error="!!errors.description"
            :validation-text="errors.description?errors.description[0]:''"
            :rows="5"
        />
      </div>
    </ShaplaColumn>
    <ShaplaColumn :tablet="12" v-if="showMarketPlaces">
      <div class="market-places">
        <h3 class="font-normal text-lg text-primary uppercase">Where to list your card</h3>
        <h4 class="font-normal text-sm">You can choose other market places for us to list your card on</h4>
        <ShaplaColumns multiline>
          <ShaplaColumn :tablet="2" v-for="marketPlace in store.market_places" :key="marketPlace.key">
            <div>
              <div>
                <ShaplaSwitch
                    :value="marketPlace.key"
                    v-model="card.market_places"
                    :readonly="marketPlace.key === 'yousaidit'"
                />
              </div>
              <img :src="marketPlace.logo" :alt="marketPlace.key">
            </div>
          </ShaplaColumn>
        </ShaplaColumns>
      </div>
    </ShaplaColumn>
  </ShaplaColumns>
</template>
