<template>
  <div class="yousaidit-designer-cards">
    <div class="yousaidit-designer-cards__statuses" v-if="state.cards.length">
      <radio-button
          v-for="_status in state.statuses"
          :key="_status.key"
          theme="primary"
          :label="_status.label"
          :value="_status.key"
          v-model="state.status"
      >{{ _status.label }} ({{ _status.count }})
      </radio-button>
    </div>
    <div class="all-type-cards">
      <columns multiline v-if="filtered_cards.length">
        <column :tablet="6" :desktop="4" :widescreen="3" v-for="_card in filtered_cards" :key="_card.id">
          <card-item
              :item="_card"
              @click:settings="openCardSettingsModal"
              @click:delete="handleDeleteCard"
              @click:comments="showComments"
          >some data
          </card-item>
        </column>
      </columns>
      <p v-if="!filtered_cards.length && state.readFromServer" class="card-not-found">No card found.</p>
    </div>
    <div class="yousaidit-designer-cards__fab">
      <shapla-button theme="primary" size="large" fab @click="state.showCardModal = true">
        <icon-container>+</icon-container>
      </shapla-button>
    </div>
    <modal :active="state.showCardModal" @close="state.showCardModal = false" title="Choose Card Type">
      <div class="space-x-2 flex justify-center">
        <div @click.prevent="chooseCardType('static')"
             class="bg-gray-100 hover:bg-gray-200 cursor-pointer p-4 w-36 h-32 flex justify-center items-center">
          Static Card
        </div>
        <div v-if="state.can_add_dynamic_card" @click.prevent="chooseCardType('dynamic')"
             class="bg-gray-100 hover:bg-gray-200 cursor-pointer p-4 w-36 h-32 flex justify-center items-center">
          Dynamic Card
        </div>
      </div>
    </modal>
    <modal v-if="state.total_cards >= state.maximum_allowed_card" :active="state.modalActive" @close="state.modalActive = false" type="box">
      <div class="m-8">
        <div class="w-full text-center mb-4">
          <h2 class="text-2xl text-primary">We are sorry, you have reached your card limit.</h2>
          <p class="px-16 mb-8">Please fill in the form bellow for a member of our time to review your account
            and up your limits.</p>
        </div>
        <columns>
          <column :tablet="5">
            <input type="text" class="w-full" placeholder="Username or Email" readonly
                   v-model="state.limit_extend_request.username">
          </column>
          <column :tablet="5">
            <input type="text" class="w-full" placeholder="Up Limit To?"
                   v-model="state.limit_extend_request.up_limit_to">
          </column>
          <column :tablet="2">
            <shapla-button theme="primary" fullwidth @click="submitLimitExtendRequest">Send</shapla-button>
          </column>
        </columns>
      </div>
    </modal>
    <modal :active="!!Object.keys(state.activeCard).length" @close="state.activeCard = {}" type="box">
      <div class="box--settings">
        <columns :multiline="true">
          <column :tablet="12">
            <p><strong>Request for</strong></p>
            <columns>
              <column>
                <radio-button theme="secondary" fullwidth value="pause"
                              v-model="state.card_request.request_for">
                  Pause
                </radio-button>
              </column>
              <column>
                <radio-button theme="secondary" fullwidth value="remove"
                              v-model="state.card_request.request_for">
                  Remove
                </radio-button>
              </column>
            </columns>
          </column>
          <column :tablet="12">
            <text-field
                label="Message to admin (optional)"
                type="textarea"
                v-model="state.card_request.message"
            />
          </column>
          <column :tablet="12">
            <shapla-button theme="primary" :disabled="!state.card_request.request_for"
                           @click="handleSubmitRequest">Submit Request
            </shapla-button>
          </column>
        </columns>
      </div>
    </modal>
    <modal :active="!!state.comments.length" title="Comments" @close="state.comments = []">
      <div class="yousaidit-designer-cards__comments"></div>
      <div class="yousaidit-designer-cards__comment" v-for="comment in state.comments">
        {{ comment.content }}
      </div>
    </modal>
  </div>
</template>

<script lang="ts" setup>
import axios from "../../utils/axios";
import {
  ShaplaButton as shaplaButton,
  ShaplaColumn as column,
  ShaplaColumns as columns,
  ShaplaIcon as iconContainer,
  ShaplaInput as textField,
  ShaplaModal as modal,
  ShaplaRadio as radioButton
} from '@shapla/vue-components';
import CardItem from "../components/CardItem.vue";
import {Notify, Spinner} from "@shapla/vanilla-components";
import useDesignerDashboardStore from "../store.js";
import {computed, onMounted, reactive} from "vue";

const store = useDesignerDashboardStore();

const state = reactive({
  readFromServer: false,
  showCardModal: false,
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

const chooseCardType = (type) => {
  if ('static' === type) {
    state.modalActive = true
  } else {
    state.showDynaCardModal = true;
  }
  state.showCardModal = false;
}
const submitLimitExtendRequest = () => {
  if (!state.limit_extend_request.up_limit_to) {
    Notify.error('Please fill in "up limit to" field')
    return;
  }
  Spinner.show();
  axios.post('designers/extend-card-limit', state.limit_extend_request).then(() => {
    state.modalActive = false;
    Spinner.hide();
    Notify.success('Your message has been sent.');
  }).catch(errors => {
    console.log(errors);
    Spinner.hide();
  })
}
const handleDeleteCard = (card) => {
  Spinner.show();
  axios.delete('designers/' + store.user.id + '/cards/' + card.id, {
    params: {action: 'delete'}
  }).then(() => {
    Spinner.hide();
    state.cards = [];
    getCards();
  }).catch(errors => {
    console.log(errors);
    Spinner.hide();
  });
}
const showComments = (card) => {
  Spinner.show();
  let url = 'designers/' + store.user.id + '/cards/' + card.id + '/comments';
  axios.get(url).then(response => {
    Spinner.hide();
    state.comments = response.data.data.comments;
  }).catch(errors => {
    Spinner.hide();
    console.log(errors);
  });
}
const openCardSettingsModal = (card) => {
  state.activeCard = card;
}
const handleSubmitRequest = () => {
  Spinner.show();
  let url = 'designers/' + store.user.id + '/cards/' + state.activeCard.id + '/requests';
  axios.put(url, {
    request_for: state.card_request.request_for,
    message: state.card_request.message,
  }).then(() => {
    Spinner.hide();
    Notify.success('You request has been sent to admin.', 'Request Submitted!')
    state.activeCard = {};
  }).catch(errors => {
    Spinner.hide();
    console.log(errors);
  });
}
const paginateOnScroll = () => {
  let dashboardContent = document.querySelector('.shapla-dashboard-content');
  dashboardContent.addEventListener('scroll', () => {
    let offsetHeight = document.body.scrollTop || document.documentElement.scrollTop || dashboardContent.scrollTop,
        cardContainer = document.querySelector('.all-type-cards'),
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
  getItems().then(data => {
    if (false === merge) {
      state.cards = data.items;
    } else {
      state.cards = state.cards.concat(data.items);
    }
    state.statuses = data.statuses;
    state.pagination = data.pagination;
    state.maximum_allowed_card = data.maximum_allowed_card;
    state.can_add_dynamic_card = data.can_add_dynamic_card;
    state.total_cards = data.total_cards;
    state.end = (state.cards.length === data.pagination.totalCount);
    state.pagination_lock = false;
    state.readFromServer = true;
  });
}
const getItems = () => {
  return new Promise(resolve => {
    axios.get('designers/' + store.user.id + '/cards', {
      params: {
        per_page: state.per_page,
        page: state.current_page,
      }
    }).then(response => {
      resolve(response.data.data);
    }).catch(errors => {
      console.log(errors);
    });
  });
}

onMounted(() => {
  getCards();
  paginateOnScroll();
  state.limit_extend_request.username = store.user.display_name;
})

const filtered_cards = computed(() => {
  if (state.status === 'all') {
    return state.cards;
  }
  return state.cards.filter(card => card.status === state.status);
})
</script>
