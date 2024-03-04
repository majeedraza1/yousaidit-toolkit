<template>
  <div class="yousaidit-designer-cards">
    <div class="yousaidit-designer-cards__statuses" v-if="state.cards.length">
      <ShaplaRadio
          v-for="_status in cardStore.statuses"
          :key="_status.key"
          theme="primary"
          :label="_status.label"
          :value="_status.key"
          v-model="state.status"
      >{{ _status.label }} ({{ _status.count }})
      </ShaplaRadio>
    </div>
    <div class="all-type-cards">
      <ShaplaColumns multiline v-if="filtered_cards.length">
        <ShaplaColumn :tablet="6" :desktop="4" :widescreen="3" v-for="_card in filtered_cards" :key="_card.id">
          <CardItem
              :item="_card"
              @click:settings="openCardSettingsModal"
              @click:delete="handleDeleteCard"
              @click:comments="showComments"
          >some data
          </CardItem>
        </ShaplaColumn>
      </ShaplaColumns>
      <p v-if="!filtered_cards.length && state.readFromServer" class="card-not-found">No card found.</p>
    </div>
    <ShaplaModal v-if="cardStore.total_cards >= cardStore.maximum_allowed_card" :active="state.modalActive"
                 @close="state.modalActive = false" type="box">
      <div class="m-8">
        <div class="w-full text-center mb-4">
          <h2 class="text-2xl text-primary">We are sorry, you have reached your card limit.</h2>
          <p class="px-16 mb-8">Please fill in the form bellow for a member of our time to review your account
            and up your limits.</p>
        </div>
        <ShaplaColumns>
          <ShaplaColumn :tablet="5">
            <input type="text" class="w-full" placeholder="Username or Email" readonly
                   v-model="state.limit_extend_request.username">
          </ShaplaColumn>
          <ShaplaColumn :tablet="5">
            <input type="text" class="w-full" placeholder="Up Limit To?"
                   v-model="state.limit_extend_request.up_limit_to">
          </ShaplaColumn>
          <ShaplaColumn :tablet="2">
            <shapla-button theme="primary" fullwidth @click="submitLimitExtendRequest">Send</shapla-button>
          </ShaplaColumn>
        </ShaplaColumns>
      </div>
    </ShaplaModal>
    <ShaplaModal :active="!!Object.keys(state.activeCard).length" @close="state.activeCard = {}" type="box">
      <div class="box--settings">
        <ShaplaColumns :multiline="true">
          <ShaplaColumn :tablet="12">
            <p><strong>Request for</strong></p>
            <ShaplaColumns>
              <ShaplaColumn>
                <ShaplaRadio theme="secondary" fullwidth value="pause"
                             v-model="state.card_request.request_for">
                  Pause
                </ShaplaRadio>
              </ShaplaColumn>
              <ShaplaColumn>
                <ShaplaRadio theme="secondary" fullwidth value="remove"
                             v-model="state.card_request.request_for">
                  Remove
                </ShaplaRadio>
              </ShaplaColumn>
            </ShaplaColumns>
          </ShaplaColumn>
          <ShaplaColumn :tablet="12">
            <ShaplaInput
                label="Message to admin (optional)"
                type="textarea"
                v-model="state.card_request.message"
            />
          </ShaplaColumn>
          <ShaplaColumn :tablet="12">
            <shapla-button theme="primary" :disabled="!state.card_request.request_for"
                           @click="handleSubmitRequest">Submit Request
            </shapla-button>
          </ShaplaColumn>
        </ShaplaColumns>
      </div>
    </ShaplaModal>
    <ShaplaModal :active="!!state.comments.length" title="Comments" @close="state.comments = []">
      <div class="yousaidit-designer-cards__comments"></div>
      <div class="yousaidit-designer-cards__comment" v-for="comment in state.comments">
        {{ comment.content }}
      </div>
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
  ShaplaRadio,
} from '@shapla/vue-components';
import CardItem from "../components/CardItem.vue";
import {Notify} from "@shapla/vanilla-components";
import useDesignerDashboardStore from "../store.js";
import {computed, onMounted, reactive} from "vue";
import useDesignerCardStore from "../stores/store-cards.ts";
import {ServerCardCollectionResponseInterface, ServerCardResponseInterface} from "../../interfaces/designer-card.ts";

const store = useDesignerDashboardStore();
const cardStore = useDesignerCardStore();

const state = reactive({
  readFromServer: false,
  showDynaCardModal: false,
  modalActive: false,
  cards: [],
  maximum_allowed_card: 0,
  can_add_dynamic_card: false,
  total_cards: 0,
  pagination: {
    total_items: 0,
    total_pages: 0,
  },
  pagination_lock: false,
  end: false,
  per_page: 12,
  current_page: 1,
  activeCard: {},
  card_request: {
    request_for: '',
    message: '',
  },
  limit_extend_request: {
    username: '',
    up_limit_to: '',
  },
  statuses: [],
  status: 'all',
  comments: [],
})

const submitLimitExtendRequest = () => {
  if (!state.limit_extend_request.up_limit_to) {
    Notify.error('Please fill in "up limit to" field')
    return;
  }
  cardStore.requestLimitExtend(state.limit_extend_request.up_limit_to).then(() => {
    state.modalActive = false;
  })
}
const handleDeleteCard = (card) => {
  cardStore.deleteCard(card).then(() => {
    state.cards = [];
    getCards();
  })
}
const showComments = (card) => {
  cardStore.getCardComments(card).then(comments => {
    state.comments = comments;
  })
}
const openCardSettingsModal = (card: ServerCardResponseInterface) => {
  state.activeCard = card;
}
const handleSubmitRequest = () => {
  cardStore
      .submitRequest(state.activeCard, state.card_request.request_for, state.card_request.message)
      .then(() => {
        state.activeCard = {};
      })
}
const paginateOnScroll = () => {
  let dashboardContent = document.querySelector<HTMLDivElement>('.shapla-dashboard-content');
  dashboardContent.addEventListener('scroll', () => {
    let offsetHeight = document.body.scrollTop || document.documentElement.scrollTop || dashboardContent.scrollTop,
        cardContainer = document.querySelector<HTMLDivElement>('.all-type-cards'),
        mailListHeight = cardContainer.offsetHeight + cardContainer.offsetTop;

    if (!state.pagination_lock && !state.end && (window.innerHeight + offsetHeight) >= mailListHeight &&
        state.cards.length < state.pagination.total_items && state.current_page < state.pagination.total_pages
    ) {
      state.current_page += 1;
      getCards();
    }
  });
}
const getCards = (merge = true) => {
  state.pagination_lock = true;
  cardStore.getDesignerCards(state.per_page, state.current_page).then((data: ServerCardCollectionResponseInterface) => {
    if (false === merge) {
      state.cards = data.items;
    } else {
      state.cards = state.cards.concat(data.items);
    }
    state.end = (state.cards.length === data.pagination.total_items);
    state.pagination_lock = false;
    state.readFromServer = true;
    state.pagination = data.pagination;
  });
}

onMounted(() => {
  getCards();
  paginateOnScroll();
  state.limit_extend_request.username = store.user.display_name;
  if (cardStore.items.length < 1) {
    cardStore.getDesignerCards();
  }
})

const filtered_cards = computed(() => {
  if (state.status === 'all') {
    return cardStore.items;
  }
  return cardStore.items.filter(card => card.status === state.status);
})
</script>
