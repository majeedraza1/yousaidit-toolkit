import axios from "../utils/axios";
import {Notify, Spinner} from "@shapla/vanilla-components";
import {defineStore} from "pinia";
import {computed, reactive, toRefs} from "vue";

const useDesignerDashboardStore = defineStore('designer-dashboard', () => {
  const state = reactive({
    designer_id: 0,
    designer: {},
    cards: [],
    cards_statuses: [],
    total_commission: 0,
    unpaid_commission: 0,
    paid_commission: 0,
    unique_customers: 0,
    total_orders: 0,
    commissions: [],
    total_commissions_items: 0,
    revenue_current_page: 1,
  });

  const getDesigner = () => {
    Spinner.show();
    axios.get('designers/' + state.designer_id).then(response => {
      const _data = response.data.data;
      state.designer = _data.designer;
      state.cards_statuses = _data.statuses;
      state.total_commission = _data.total_commission;
      state.unpaid_commission = _data.unpaid_commission;
      state.paid_commission = _data.paid_commission;
      state.unique_customers = _data.unique_customers;
      state.total_orders = _data.total_orders;
    }).catch(errors => {
      if (typeof errors.response.data.message === "string") {
        Notify.error(errors.response.data.message, 'Error!');
      }
    }).finally(() => {
      Spinner.hide();
    });
  }

  const getCommission = (args) => {
    Spinner.show();
    axios.get('designers/' + state.designer_id + '/commissions', {
      params: {
        report_type: args['type'],
        date_from: args['from'],
        date_to: args['to'],
        page: state.revenue_current_page
      }
    })
      .then(response => {
        let data = response.data.data
        state.commissions = data.commissions;
        state.total_commissions_items = data.pagination.total_items;
      })
      .catch(errors => {
        if (errors.response.data.message) {
          Notify.error(errors.response.data.message, 'Error!');
        }
      })
      .finally(() => {
        Spinner.hide();
      });
  }

  return {
    ...toRefs(state),
    getDesigner,
    getCommission,
    card_categories: computed(() => window.DesignerProfile.categories),
    card_tags: computed(() => window.DesignerProfile.tags),
    card_attributes: computed(() => window.DesignerProfile.attributes),
    market_places: computed(() => window.DesignerProfile.marketPlaces),
    card_sizes: computed(() => {
      return window.DesignerProfile.card_sizes.map(size => {
        return {
          value: size.slug,
          label: size.name
        }
      });
    }),
    user: computed(() => window.DesignerProfile.user),
    user_card_categories: computed(() => window.DesignerProfile.user_card_categories),
  }
});

export default useDesignerDashboardStore;
