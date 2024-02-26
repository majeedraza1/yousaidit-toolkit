import {defineStore} from "pinia";
import {reactive, toRefs} from "vue";
import {PaginationDataInterface} from "../utils/CrudOperation.ts";
import {Dialog, Notify, Spinner} from "@shapla/vanilla-components";
import axios from "../utils/axios.ts";
import {CardStatusInterface, DesignerCardInterface, DesignerInterface} from "../interfaces/designer.ts";

const useAdminDesignerStore = defineStore('admin-designer', () => {
  const state = reactive<{
    designers: DesignerInterface[],
    designers_pagination: PaginationDataInterface;
    designers_search: string;
    designer: null | DesignerInterface;
    designer_cards: DesignerCardInterface[];
    designer_cards_pagination: PaginationDataInterface;
    cards: DesignerCardInterface[];
    cards_statuses: CardStatusInterface[];
    cards_pagination: PaginationDataInterface;
    cards_filter_args: {
      search: string,
      status: string,
      card_type: string,
      designer_id: number,
    }
  }>({
    designers: [],
    designers_pagination: {current_page: 1, total_items: 0, per_page: 100},
    designers_search: '',
    designer: null,
    designer_cards: [],
    designer_cards_pagination: {current_page: 1, total_items: 0, per_page: 100},
    cards: [],
    cards_statuses: [],
    cards_pagination: {current_page: 1, total_items: 0, per_page: 100},
    cards_filter_args: {search: '', status: '', card_type: '', designer_id: 0}
  })

  const getDesigners = (page: number = 1) => {
    Spinner.show();
    axios.get('designers', {
      params: {page: page, per_page: state.designers_pagination.per_page, search: state.designers_search}
    })
      .then(response => {
        let data = response.data.data;
        state.designers = data.items;
        state.designers_pagination = data.pagination;
      })
      .catch(error => {
        const responseData = error.response.data;
        if (responseData.message) {
          Notify.error(responseData.message, 'Error!');
        }
      })
      .finally(() => {
        Spinner.hide();
      })
  }

  const getDesigner = (id: number) => {
    Spinner.show();
    axios.get('designers/' + id)
      .then(response => {
        let data = response.data.data;
        state.designer = data.designer;
        state.designer.total_commission = data.total_commission;
        state.designer.unpaid_commission = data.unpaid_commission;
        state.designer.paid_commission = data.paid_commission;
        state.designer.maximum_allowed_card = data.maximum_allowed_card;
        state.designer.can_add_dynamic_card = data.can_add_dynamic_card;
      })
      .catch(error => {
        const responseData = error.response.data;
        if (responseData.message) {
          Notify.error(responseData.message, 'Error!');
        }
      })
      .finally(() => {
        Spinner.hide();
      })
  }

  const getDesignerCards = (page: number = 1, designer_id: number = 0) => {
    if (!designer_id) {
      designer_id = state.designer.id;
    }
    Spinner.show();
    axios.get('designers/' + designer_id + '/cards',
      {params: {page: page, per_page: state.designer_cards_pagination.per_page}}
    )
      .then(response => {
        let data = response.data.data;
        state.designer_cards = data.items;
        state.designer_cards_pagination = data.pagination;
      })
      .catch(error => {
        const responseData = error.response.data;
        if (responseData.message) {
          Notify.error(responseData.message, 'Error!');
        }
      })
      .finally(() => {
        Spinner.hide();
      })
  }

  const updateDesignerBusinessName = (id: number, business_name: string) => {
    return new Promise(resolve => {
      Spinner.show();
      axios
        .put('designers/' + id, {business_name})
        .then((data) => {
          getDesigner(id);
          resolve(data)
        })
        .catch(error => {
          const responseData = error.response.data;
          if (responseData.message) {
            Notify.error(responseData.message, 'Error!');
          }
        })
        .finally(() => {
          Spinner.hide();
        })
    })
  }

  const updateDesignerCardLimit = (id: number, maximum_allowed_card: number | string) => {
    if (!maximum_allowed_card) {
      return Notify.error('Add new limit first.', 'Error!');
    }
    return new Promise(resolve => {
      Spinner.show();
      axios.post('/admin/designers/', {
        designer_id: id,
        card_limit: maximum_allowed_card,
      })
        .then((data) => {
          getDesigner(id);
          resolve(data)
        })
        .catch(error => {
          const responseData = error.response.data;
          if (responseData.message) {
            Notify.error(responseData.message, 'Error!');
          }
        })
        .finally(() => {
          Spinner.hide();
        })
    })
  }

  const toggleDesignerDynamicCard = (id: number) => {
    Dialog.confirm('Are you sure?').then(() => {
      Spinner.show();
      axios.post('/admin/designers/', {
        designer_id: id,
        can_add_dynamic_card: !state.designer.can_add_dynamic_card ? 'yes' : 'no',
      })
        .then(() => {
          getDesigner(id);
        })
        .catch(error => {
          const responseData = error.response.data;
          if (responseData.message) {
            Notify.error(responseData.message, 'Error!');
          }
        })
        .finally(() => {
          Spinner.hide();
        })
    })
  }

  const getCards = (page: number = 1) => {
    Spinner.show();
    axios.get('designers-cards', {
      params: {
        page: page,
        per_page: state.cards_pagination.per_page,
        ...state.cards_filter_args
      }
    }).then(response => {
      let data = response.data.data;
      state.cards = data.items;
      state.cards_statuses = data.statuses;
      state.cards_pagination = data.pagination;
      Spinner.hide();
    })
      .catch(error => {
        const responseData = error.response.data;
        if (responseData.message) {
          Notify.error(responseData.message, 'Error!');
        }
      })
      .finally(() => {
        Spinner.hide();
      })
  }


  return {
    ...toRefs(state),
    getDesigners,
    getDesigner,
    getDesignerCards,
    updateDesignerBusinessName,
    updateDesignerCardLimit,
    toggleDesignerDynamicCard,
    getCards
  }
});

export default useAdminDesignerStore;
