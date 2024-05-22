<template>
  <div>
    <h1 class="wp-heading-inline">Designer</h1>
    <hr class="wp-header-end">
    <div style="margin-bottom: 1rem;display: flex; justify-content: flex-end;">

    </div>
    <ShaplaTabs alignment="center">
      <ShaplaTab name="Info" selected>
        <div v-if="designer" style="background: #fff;padding: 16px;">
          <ShaplaColumns multiline>
            <ShaplaColumn :tablet="3">ID</ShaplaColumn>
            <ShaplaColumn :tablet="9">{{ designer.id }}</ShaplaColumn>
          </ShaplaColumns>
          <ShaplaColumns multiline>
            <ShaplaColumn :tablet="3">Name</ShaplaColumn>
            <ShaplaColumn :tablet="9">{{ designer.display_name }}</ShaplaColumn>
          </ShaplaColumns>
          <ShaplaColumns multiline>
            <ShaplaColumn :tablet="3">Email</ShaplaColumn>
            <ShaplaColumn :tablet="9">
              <div>{{ designer.email }}</div>
              <a :href="designer.profile_edit_url" target="_blank">View on user table</a>
            </ShaplaColumn>
          </ShaplaColumns>
          <ShaplaColumns multiline>
            <ShaplaColumn :tablet="3">Business Name</ShaplaColumn>
            <ShaplaColumn :tablet="9">
              <div>{{ designer.business_name }}</div>
              <a href="" @click.prevent="state.showEditModal = true">{{ designer.business_name ? 'Edit' : 'Add' }}</a>
            </ShaplaColumn>
          </ShaplaColumns>
          <ShaplaColumns multiline>
            <ShaplaColumn :tablet="3">Commission (unpaid)</ShaplaColumn>
            <ShaplaColumn :tablet="9">{{ designer.unpaid_commission }}</ShaplaColumn>
          </ShaplaColumns>
          <ShaplaColumns multiline>
            <ShaplaColumn :tablet="3">Commission (paid)</ShaplaColumn>
            <ShaplaColumn :tablet="9">{{ designer.paid_commission }}</ShaplaColumn>
          </ShaplaColumns>
          <ShaplaColumns multiline>
            <ShaplaColumn :tablet="3">Total Commission</ShaplaColumn>
            <ShaplaColumn :tablet="9">{{ designer.total_commission }}</ShaplaColumn>
          </ShaplaColumns>
          <ShaplaColumns multiline>
            <ShaplaColumn :tablet="3">Card Limit</ShaplaColumn>
            <ShaplaColumn :tablet="9">
              <div>
                <div>{{ designer.maximum_allowed_card }}</div>
                <div><a href="" @click.prevent="state.showCardLimitModal = true">Increase</a></div>
              </div>
            </ShaplaColumn>
          </ShaplaColumns>
          <ShaplaColumns multiline>
            <ShaplaColumn :tablet="3">Is dynamic card allowed?</ShaplaColumn>
            <ShaplaColumn :tablet="9">
              <div>
                <div>
                  {{ designer.can_add_dynamic_card ? 'Yes' : 'No' }}
                  <a href="" @click.prevent="()=>store.toggleDesignerDynamicCard(designer.id)">
                    {{ designer.can_add_dynamic_card ? 'Disallow' : 'Allow' }}
                  </a>
                </div>
                <div><strong>Note:</strong> Dynamic card always allowed for admin user.</div>
              </div>
            </ShaplaColumn>
          </ShaplaColumns>
        </div>
      </ShaplaTab>
      <ShaplaTab name="Cards">
        <div class="mb-2">
          <ShaplaTablePagination
              :current-page="store.designer_cards_pagination.current_page"
              :total-items="store.designer_cards_pagination.total_items"
              :per-page="store.designer_cards_pagination.per_page"
              @paginate="store.getDesignerCards"
          />
        </div>
        <ShaplaTable
            :items="store.designer_cards"
            :columns="columns"
            :actions="[{label:'View',key:'view'}]"
            @click:action="onCardActionClick"
        />
        <div class="mt-2">
          <ShaplaTablePagination
              :current-page="store.designer_cards_pagination.current_page"
              :total-items="store.designer_cards_pagination.total_items"
              :per-page="store.designer_cards_pagination.per_page"
              @paginate="store.getDesignerCards"
          />
        </div>
      </ShaplaTab>
      <ShaplaTab name="Commissions">
        <div class="mb-2">
          <ShaplaTablePagination
              :current-page="store.commissions_pagination.current_page"
              :total-items="store.commissions_pagination.total_items"
              :per-page="store.commissions_pagination.per_page"
              @paginate="store.getDesignerCommissions"
          />
        </div>
        <ShaplaTable
            :items="store.commissions"
            :columns="commissions_columns"
        />
        <div class="mt-2">
          <ShaplaTablePagination
              :current-page="store.commissions_pagination.current_page"
              :total-items="store.commissions_pagination.total_items"
              :per-page="store.commissions_pagination.per_page"
              @paginate="store.getDesignerCommissions"
          />
        </div>
      </ShaplaTab>
    </ShaplaTabs>


    <ShaplaModal v-if="store.designer" :active="state.showEditModal" @close="state.showEditModal = false"
                 content-size="small"
                 title="Edit Profile">
      <ShaplaColumns multiline>
        <ShaplaColumn>
          <ShaplaInput
              label="Business Name"
              v-model="store.designer.business_name"
          />
        </ShaplaColumn>
      </ShaplaColumns>
      <template v-slot:foot>
        <ShaplaButton theme="primary" @click="()=>store.updateDesignerBusinessName(designer.id,designer.business_name)">
          Update
        </ShaplaButton>
      </template>
    </ShaplaModal>
    <ShaplaModal v-if="store.designer" :active="state.showCardLimitModal" @close="state.showCardLimitModal = false"
                 title="Extend Card Limit">
      <ShaplaColumns multiline>
        <ShaplaColumn :tablet="3">Current Limit</ShaplaColumn>
        <ShaplaColumn :tablet="9">{{ designer.maximum_allowed_card }}</ShaplaColumn>
      </ShaplaColumns>
      <ShaplaColumns multiline>
        <ShaplaColumn :tablet="3">New Limit</ShaplaColumn>
        <ShaplaColumn :tablet="9">
          <input type="text" v-model="state.maximum_allowed_card">
        </ShaplaColumn>
      </ShaplaColumns>
      <template v-slot:foot>
        <ShaplaButton theme="primary"
                      @click="() => store.updateDesignerCardLimit(designer.id,state.maximum_allowed_card)">
          Update
        </ShaplaButton>
      </template>
    </ShaplaModal>
  </div>
</template>

<script lang="ts" setup>
import {
  ShaplaButton,
  ShaplaColumn,
  ShaplaColumns,
  ShaplaInput,
  ShaplaModal,
  ShaplaTab,
  ShaplaTable,
  ShaplaTablePagination,
  ShaplaTabs
} from '@shapla/vue-components';
import {computed, onMounted, reactive} from "vue";
import useAdminDesignerStore from "../store.ts";
import {useRoute, useRouter} from "vue-router";
import {DesignerInterface} from "../../interfaces/designer.ts";
import {PaginationDataInterface} from "../../utils/CrudOperation.ts";
import {DesignerCardModelInterface} from "../../interfaces/designer-card.ts";

const store = useAdminDesignerStore();
const route = useRoute();
const router = useRouter();

const state = reactive({
  id: 0,
  designer: {},
  pagination: {},
  cards: [],
  showEditModal: false,
  showCardLimitModal: false,
  maximum_allowed_card: '',
})

const designer = computed<DesignerInterface>(() => store.designer);
const pagination = computed<PaginationDataInterface>(() => store.card_pagination);

const columns = [
  {key: 'card_title', label: 'Title'},
  {key: 'card_type', label: 'Type'},
  {key: 'status', label: 'Status'},
  {key: 'card_sku', label: 'SKU'},
  {key: 'total_sale', label: 'Total Sales', numeric: true},
]

const commissions_columns = [
  {key: 'order_id', label: 'Order'},
  {key: 'product_title', label: 'Product Title'},
  {key: 'card_size', label: 'Card Size'},
  {key: 'marketplace', label: 'Marketplace'},
  {key: 'created_at', label: 'Sale Date'},
  {key: 'payment_status', label: 'Payment Status'},
  {key: 'order_quantity', label: 'Qty', numeric: true},
  {key: 'total_commission', label: 'Total Commission', numeric: true},
];

const onCardActionClick = (action: string, item: DesignerCardModelInterface) => {
  if ('view' === action) {
    router.push({name: 'Card', params: {id: item.id}})
  }
}

onMounted(() => {
  state.id = parseInt(route.params.id);
  if (state.id) {
    store.getDesigner(state.id);
    store.getDesignerCards(1, state.id)
    store.getDesignerCommissions(1, state.id)
  }
})
</script>
