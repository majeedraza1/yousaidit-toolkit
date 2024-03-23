<template>
  <div class="yousaiditcard_designer_card">
    <h1 class="wp-heading-inline">Card</h1>
    <hr class="wp-header-end">
    <template v-if="store.card">
      <ShaplaColumns>
        <ShaplaColumn :tablet="6">
          <h4>Type: {{ store.card.card_type }}</h4>
          <h4>Status: {{ store.card.status }}</h4>
        </ShaplaColumn>
        <ShaplaColumn :tablet="6">
          <div class="yousaiditcard_designer_card__actions-top">
            <template v-if="'trash' !== store.card.status">
              <template v-if="'processing' === store.card.status">
                <ShaplaButton theme="success" size="small" @click="state.showAcceptConfirmModal = true">Accept
                </ShaplaButton>
                <ShaplaButton theme="error" size="small" @click="state.showRejectConfirmModal = true">Reject
                </ShaplaButton>
              </template>
              <template v-if="'accepted' === store.card.status && !store.card.product_id">
                <ShaplaButton theme="primary" size="small" @click="state.showCreateProductModal = true">Create
                  Product
                </ShaplaButton>
              </template>
              <template
                  v-if="'accepted' === store.card.status && store.card.market_place.indexOf('yousaidit-trade') !== -1">
                <ShaplaButton theme="primary" size="small" outline
                              @click="() => store.createProductOnTradeSite(card_id)">
                  Create Product on Trade site
                </ShaplaButton>
              </template>
              <template v-if="'accepted' === store.card.status && store.card.product_id">
                <ShaplaButton theme="success" size="small" outline @click="state.showUpdateSkuModal = true">
                  Update SKU
                </ShaplaButton>
                <a class="shapla-button is-primary is-small" :href="store.card.product_edit_url" target="_blank">Edit
                  Product</a>
                <a class="shapla-button is-primary is-small is-outline" :href="store.card.product_url" target="_blank">View
                  Product</a>
              </template>
              <template v-if="hasCommissionData">
                <ShaplaButton theme="secondary" size="small" @click="state.showEditCommissionModal = true">Change
                  Commission
                </ShaplaButton>
              </template>
              <ShaplaButton v-if="store.card.card_type === 'dynamic'" theme="secondary" size="small" outline
                            @click="() => store.previewDynamicCardPDF(card_id)">Preview PDF
              </ShaplaButton>
              <ShaplaButton theme="secondary" size="small" outline @click="() => store.generateCardImage(card_id)">
                Generate Image
              </ShaplaButton>
              <ShaplaButton theme="error" size="small" @click="()=> store.trashCard(card_id)">Trash Card</ShaplaButton>
            </template>
          </div>
        </ShaplaColumn>
      </ShaplaColumns>
      <ShaplaTabs>
        <ShaplaTab name="Card Info" selected>
          <div class="yousaiditcard_designer_card__content" v-if="Object.keys(store.card).length">
            <ShaplaColumns multiline>
              <ShaplaColumn :tablet="3"><strong>Title</strong></ShaplaColumn>
              <ShaplaColumn :tablet="9">{{ store.card.card_title }}</ShaplaColumn>
              <ShaplaColumn :tablet="3"><strong>SKU</strong></ShaplaColumn>
              <ShaplaColumn :tablet="9">
                <span v-if="store.card.card_sku">{{ store.card.card_sku }}</span>
                <span v-else>-</span>
              </ShaplaColumn>
              <ShaplaColumn :tablet="3"><strong>Market places</strong></ShaplaColumn>
              <ShaplaColumn :tablet="9">
                <shapla-chip v-for="market_place in store.card.market_place" :key="market_place">
                  {{ market_place }}
                </shapla-chip>
              </ShaplaColumn>
              <ShaplaColumn :tablet="3"><strong>Card Sizes</strong></ShaplaColumn>
              <ShaplaColumn :tablet="9" v-if="card_sizes">
                <template v-for="_size in card_sizes" :key="_size.value">
                  <ShaplaChip v-if="store.card.card_sizes.indexOf(_size.value) !== -1"> {{ _size.label }}</ShaplaChip>
                </template>
              </ShaplaColumn>
              <ShaplaColumn :tablet="3"><strong>Card Categories</strong></ShaplaColumn>
              <ShaplaColumn :tablet="9">
                <shapla-chip v-for="_cat in store.card.categories" :key="_cat.id">{{ _cat.title }}</shapla-chip>
              </ShaplaColumn>
              <ShaplaColumn :tablet="3"><strong>Card Tags</strong></ShaplaColumn>
              <ShaplaColumn :tablet="9">
                <shapla-chip v-for="_tag in store.card.tags" :key="_tag.id"> {{ _tag.title }}</shapla-chip>
              </ShaplaColumn>
              <ShaplaColumn :tablet="3"><strong>Suggested Tags</strong></ShaplaColumn>
              <ShaplaColumn :tablet="9">
                <span v-if="store.card.suggest_tags">{{ store.card.suggest_tags }}</span>
                <span v-else>-</span>
              </ShaplaColumn>
            </ShaplaColumns>
            <ShaplaColumns multiline v-for="_attr in store.card.attributes" :key="_attr.attribute_name">
              <ShaplaColumn :tablet="3"><strong>{{ _attr.attribute_label }}</strong></ShaplaColumn>
              <ShaplaColumn :tablet="9">
                <shapla-chip v-for="_tag in _attr.options" :key="_tag.id"> {{ _tag.title }}</shapla-chip>
              </ShaplaColumn>
            </ShaplaColumns>
            <ShaplaColumns multiline>
              <ShaplaColumn :tablet="3"><strong>Card Image</strong></ShaplaColumn>
              <ShaplaColumn :tablet="9">
                <pdf-image-item
                    :is-multiple="false"
                    :images="store.card.image"
                />
              </ShaplaColumn>
            </ShaplaColumns>
            <ShaplaColumns multiline>
              <ShaplaColumn :tablet="3"><strong>Card Gallery Images</strong></ShaplaColumn>
              <ShaplaColumn :tablet="9">
                <pdf-image-item
                    :is-multiple="true"
                    :images="store.card.gallery_images"
                />
              </ShaplaColumn>
            </ShaplaColumns>
            <ShaplaColumns multiline>
              <ShaplaColumn :tablet="3"><strong>Card PDFs</strong></ShaplaColumn>
              <ShaplaColumn :tablet="9">
                <PdfCardItem
                    v-for="(pdf_data,size_slug) in store.card.pdf_data"
                    :key="size_slug"
                    :header-text="getHeaderText(size_slug)"
                    :items="pdf_data"
                />
              </ShaplaColumn>
            </ShaplaColumns>
          </div>
        </ShaplaTab>
        <ShaplaTab name="Designer Info">
          <ShaplaColumns multiline>
            <ShaplaColumn :tablet="3"><strong>Name</strong></ShaplaColumn>
            <ShaplaColumn :tablet="9">{{ store.card.designer.display_name }}</ShaplaColumn>
          </ShaplaColumns>
          <ShaplaColumns multiline>
            <ShaplaColumn :tablet="3"><strong>Email</strong></ShaplaColumn>
            <ShaplaColumn :tablet="9">{{ store.card.designer.email }}</ShaplaColumn>
          </ShaplaColumns>
          <ShaplaColumns multiline>
            <ShaplaColumn :tablet="3"><strong>Total Cards</strong></ShaplaColumn>
            <ShaplaColumn :tablet="9">{{ store.card.designer.total_cards }}</ShaplaColumn>
          </ShaplaColumns>
        </ShaplaTab>
        <ShaplaTab name="Commission Info" v-if="'accepted' === store.card.status">
          <template v-if="hasCommissionData">
            <ShaplaColumns multiline>
              <ShaplaColumn :tablet="3">Commission type</ShaplaColumn>
              <ShaplaColumn :tablet="9">
                {{ store.card.commission.commission_type }}
              </ShaplaColumn>
            </ShaplaColumns>
            <ShaplaColumns multiline>
              <ShaplaColumn :tablet="3">Commission Amount</ShaplaColumn>
              <ShaplaColumn :tablet="9">
                <div v-for="(amount,key) in store.card.commission.commission_amount">
                  <strong>{{ amount }}</strong> <small>for size: {{ key }}</small>
                </div>
              </ShaplaColumn>
            </ShaplaColumns>
          </template>
          <template v-else>
            No commission information yet
          </template>
        </ShaplaTab>
      </ShaplaTabs>
      <ShaplaModal :active="state.showRejectConfirmModal" @close="state.showRejectConfirmModal = false"
                   :show-close-icon="false" type="box">
        <div style="background: #fff;padding: 1rem;border-radius: 4px;">
          <ShaplaColumns multiline>
            <ShaplaColumn :tablet="12">
              <ShaplaInput
                  type="textarea"
                  label="Reject Reason"
                  help-text="Describe reason of rejection."
                  v-model="store.reject_reason"
              />
            </ShaplaColumn>
            <ShaplaColumn :tablet="12">
              <ShaplaButton theme="primary" :disabled="store.reject_reason.length < 10"
                            @click="()=>store.rejectCard(card_id)"> Confirm Reject
              </ShaplaButton>
            </ShaplaColumn>
          </ShaplaColumns>
        </div>
      </ShaplaModal>
      <ShaplaModal :active="state.showAcceptConfirmModal" @close="state.showAcceptConfirmModal = false"
                   :show-close-icon="false" type="box">
        <div style="background: #fff;padding: 1rem;border-radius: 4px;"
             v-if="store.card && Object.keys(store.card).length">
          <ShaplaColumns multiline>
            <ShaplaColumn :tablet="12" style="display: none">
              <span style="display: block;margin-bottom:8px;">Commission type</span>
              <ShaplaRadio v-model="store.commission_type" value="fix" theme="secondary">Fix</ShaplaRadio>
              <ShaplaRadio v-model="store.commission_type" value="percentage" theme="secondary">Percentage</ShaplaRadio>
            </ShaplaColumn>
            <ShaplaColumn :tablet="12" v-if="store.commission_type">
              <span style="display: block;margin-bottom:8px;">Commission per sale (fix)</span>
              <ShaplaColumns>
                <ShaplaColumn v-for="_size in store.card.card_sizes" :key="_size">
                  <ShaplaInput
                      v-model="store.commission[_size]"
                      :label="`${_size}`"
                  />
                </ShaplaColumn>
              </ShaplaColumns>
            </ShaplaColumn>
            <ShaplaColumn :tablet="12">
              <ShaplaInput
                  type="textarea"
                  label="Note to Designer (option)"
                  v-model="store.note_to_designer"
              />
            </ShaplaColumn>
            <ShaplaColumn :tablet="12">
              <ShaplaButton theme="primary" :disabled="!enableAcceptButton" @click="() => store.acceptCard(card_id)">
                Confirm Accept
              </ShaplaButton>
            </ShaplaColumn>
          </ShaplaColumns>
        </div>
      </ShaplaModal>
      <ShaplaModal :active="state.showCreateProductModal" @close="state.showCreateProductModal = false"
                   :show-close-icon="false" title="Create New Product">
        <ShaplaColumns :multiline="true" v-if="store.card && store.card.card_sizes">
          <template v-for="_size in card_sizes">
            <template v-if="store.card.card_sizes.indexOf(_size.value) !== -1">
              <ShaplaColumn :tablet="3">{{ _size.label }}</ShaplaColumn>
              <ShaplaColumn :tablet="9">
                <ShaplaColumns>
                  <ShaplaColumn :tablet="6">
                    <ShaplaInput
                        label="SKU"
                        v-model="store.product_sku[_size.value]"
                    />
                  </ShaplaColumn>
                  <ShaplaColumn :tablet="6">
                    <ShaplaInput
                        label="Price"
                        v-model="store.product_price[_size.value]"
                    />
                  </ShaplaColumn>
                </ShaplaColumns>
              </ShaplaColumn>
            </template>
          </template>
        </ShaplaColumns>
        <template v-slot:foot>
          <ShaplaButton theme="primary" :disabled="!enableCreateProductButton"
                        @click="()=>store.createProduct(card_id)">
            Create Product
          </ShaplaButton>
        </template>
      </ShaplaModal>
      <ShaplaModal :active="state.showUpdateSkuModal" @close="state.showUpdateSkuModal = false" type="box"
                   content-size="small">
        <div style="background:#fff;padding:1rem;border-radius:4px;">
          <ShaplaColumns multiline>
            <ShaplaColumn :tablet="12">
              <ShaplaInput
                  label="Card SKU"
                  v-model="store.card.card_sku"
              />
            </ShaplaColumn>
            <ShaplaColumn :tablet="12">
              <ShaplaButton theme="primary" @click="()=> store.updateSku(card_id,store.card.card_sku)">Update
              </ShaplaButton>
            </ShaplaColumn>
          </ShaplaColumns>
        </div>
      </ShaplaModal>
      <ModalCardCommission
          v-if="hasCommissionData"
          :active="state.showEditCommissionModal"
          :card_id="store.card.id"
          :card_sizes="store.card.card_sizes"
          :marketplaces="store.card.market_place"
          :value="store.card.commission.commission_amount"
          :commissions="store.card.marketplace_commission"
          @close="state.showEditCommissionModal = false"
          @submit="handleCommissionUpdate"
      />
    </template>
  </div>
</template>

<script lang="ts" setup>
import {
  ShaplaButton,
  ShaplaChip,
  ShaplaColumn,
  ShaplaColumns,
  ShaplaInput,
  ShaplaModal,
  ShaplaRadio,
  ShaplaTab,
  ShaplaTabs,
  ShaplaToggle,
  ShaplaToggles
} from '@shapla/vue-components';
import PdfCardItem from "../components/PdfCardItem.vue";
import PdfImageItem from "../components/PdfImageItem.vue";
import ModalCardCommission from "../components/ModalCardCommission.vue";
import {useRoute} from "vue-router";
import useAdminDesignerCardStore from "../stores/card-store.ts";
import {computed, onMounted, reactive, ref} from "vue";

const route = useRoute();
const store = useAdminDesignerCardStore();
const card_id = ref<number>(0)
const state = reactive({
  showRejectConfirmModal: false,
  showAcceptConfirmModal: false,
  showCreateProductModal: false,
  showEditCommissionModal: false,
  showUpdateSkuModal: false,
})

const hasCommissionData = computed(() => !!(store.card && Object.keys(store.card.commission).length))
const enableAcceptButton = computed(() => {
  let _value = [];
  for (let [key, value] of Object.entries(store.commission)) {
    if (value.length) {
      _value.push(value);
    }
  }
  return _value.length === store.card.card_sizes.length;
})
const enableCreateProductButton = computed(() => {
  let _value = [], _pValue = [];
  if (!(store.card.card_sizes)) {
    return false;
  }
  if (store.product_sku) {
    for (let [key, value] of Object.entries(store.product_sku)) {
      if (value.length) {
        _value.push(value);
      }
    }
  }
  if (store.product_price) {
    for (let [key, value] of Object.entries(store.product_price)) {
      if (value.length) {
        _pValue.push(value);
      }
    }
  }
  return _pValue.length === store.card.card_sizes.length && _value.length === store.card.card_sizes.length;
})
const card_categories = computed(() => window.DesignerProfile.categories);
const card_tags = computed(() => window.DesignerProfile.tags);
const card_attributes = computed(() => window.DesignerProfile.attributes);
const card_sizes = computed(() => {
  return window.DesignerProfile.card_sizes.map(size => {
    return {
      value: size.slug,
      label: size.name
    }
  });
})

const handleCommissionUpdate = (commissions, marketplace_commissions) => {
  store.handleCommissionUpdate(card_id.value, commissions, marketplace_commissions);
}

const getHeaderText = (size_slug: string) => {
  if (card_sizes) {
    let item = card_sizes.value.find(size => size.value === size_slug);
    return item.label;
  }
  return '';
}

onMounted(() => {
  card_id.value = parseInt(route.params.id);
  store.getCardById(card_id.value);
})
</script>
