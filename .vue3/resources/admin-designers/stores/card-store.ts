import {defineStore} from "pinia";
import {reactive, toRefs} from "vue";
import {Dialog, Notify, Spinner} from "@shapla/vanilla-components";
import axios from "../../utils/axios.ts";
import {DesignerCardInterface, TYPE_CARD_SIZE} from "../../interfaces/designer.ts";

interface SingleCardServerResponseInterface extends DesignerCardInterface {
  default_commissions: { 'yousaidit': number; 'yousaidit-trade': number; };
  default_sku: Record<TYPE_CARD_SIZE, string>;
  default_price: Record<TYPE_CARD_SIZE, '' | number>;
}

const useAdminDesignerCardStore = defineStore('admin-designer-card', () => {
  const state = reactive<{
    card: DesignerCardInterface | null;
    commission: Record<string, string>
    product_sku?: Record<TYPE_CARD_SIZE, string>;
    product_price?: Record<TYPE_CARD_SIZE, '' | number>;
    commission_type: 'fix' | 'percentage',
    reject_reason: string;
    note_to_designer: string;
    [key: string]: any;
  }>({
    card: null,
    commission: null,
    product_sku: null,
    product_price: null,
    commission_type: 'fix',
    reject_reason: '',
    note_to_designer: '',
  })

  const _updateCard = (_data: SingleCardServerResponseInterface) => {
    state.card = _data;
    if (state.commission === null) {
      state.commission = {};
      let defaults = _data.default_commissions.yousaidit;
      _data.card_sizes.forEach(size => {
        state.commission[size] = defaults.toString();
      });
    }
    if (state.product_sku === null) {
      state.product_sku = _data.default_sku;
    }
    if (state.product_price === null) {
      state.product_price = _data.default_price;
    }
  }

  const getCardById = (card_id: number) => {
    Spinner.show();
    axios.get('designers-cards/' + card_id)
      .then(response => {
        _updateCard(response.data.data as SingleCardServerResponseInterface)
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
  const createProductOnTradeSite = (card_id: number) => {
    Dialog.confirm('Are you sure?').then(confirmed => {
      if (confirmed) {
        Spinner.show();
        axios.post(`/trade-site/${card_id}/create-product`)
          .then(() => {
            Spinner.hide();
            Notify.success('Request has been sent successfully.', 'Success!');
            getCardById(card_id);
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
    })
  }
  const updateCard = (card_id: number, payload: Record<string, any>): Promise<SingleCardServerResponseInterface> => {
    return new Promise(resolve => {
      Spinner.show();
      axios.put(`designers-cards/${card_id}`, payload)
        .then((response) => {
          const data = response.data.data as SingleCardServerResponseInterface;
          _updateCard(data)
          resolve(data);
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
  const updateSku = (card_id: number, card_sku: string) => {
    updateCard(card_id, {card_sku: card_sku}).then(() => {
      Notify.success('Card SKU has been updated.', 'Success!');
    })
  }

  const acceptCard = (card_id: number) => {
    return new Promise(resolve => {
      let data: Record<string, any> = {status: 'accepted'};
      data.commission_type = state.commission_type;
      data.commission = state.commission;
      data.note_to_designer = state.note_to_designer;
      updateCard(card_id, data).then((data) => {
        Notify.success('Card acceptance status has been updated.', 'Success!');
        resolve(data);
      })
    })
  }

  const rejectCard = (card_id: number) => {
    return new Promise(resolve => {
      let data: Record<string, any> = {status: 'rejected'};
      data.reject_reason = state.reject_reason;
      updateCard(card_id, data).then((data) => {
        resolve(data);
        Notify.success('Card acceptance status has been updated.', 'Success!');
      })
    })
  }

  const handleAcceptOrReject = (card_id: number, status: string) => {
    if ('accepted' === status) {
      acceptCard(card_id)
    }
    if ('rejected' === status) {
      rejectCard(card_id)
    }
  }

  const createProduct = (card_id: number) => {
    return new Promise(resolve => {
      Spinner.show();
      axios.post('designers-cards/' + card_id + '/product', {
        product_sku: state.product_sku,
        product_price: state.product_price,
      })
        .then(response => {
          _updateCard(response.data.data)
          resolve(response.data.data)
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

  const changeCommission = (card_id: number) => {
    Spinner.show();
    let commission = state.card.commission;
    updateCard(card_id, {
      commission_type: commission.commission_type ? commission.commission_type : 'fix',
      commission: commission.commission_amount ? commission.commission_amount : {},
      note_to_designer: state.note_to_designer,
      status: 'change_commission',
    }).then(() => {
      Notify.success('Card commission has been updated.', 'Success!');
    })
  }
  const trashCard = (card_id: number) => {
    Dialog.confirm('Are you sure to trash this card?').then(confirmed => {
      if (confirmed) {
        Spinner.show();
        axios.delete('designers-cards/' + card_id)
          .then((response) => {
            _updateCard(response.data.data)
            Notify.success('Card has been trashed.')
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
    })
  }

  const handleCommissionUpdate = (card_id: number, commission, marketplace_commission) => {
    return new Promise(resolve => {
      Spinner.show();
      axios.post('designers-cards/' + card_id + '/commission', {
        commission: commission,
        marketplace_commission: marketplace_commission
      })
        .then((response) => {
          _updateCard(response.data.data)
          resolve(response.data.data)
          Notify.success('Commission has been update successfully.')
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

  const previewDynamicCardPDF = (card_id: string | number) => {
    let url = new URL(window.StackonetToolkit.ajaxUrl);
    url.searchParams.append('action', 'yousaidit_preview_card');
    url.searchParams.append('card_id', card_id.toString());
    url.searchParams.append('_token', Math.random().toString());

    const a = document.createElement('a');
    a.href = url.toString();
    a.target = '_blank'
    a.click();
    a.remove();
  }

  const generateCardImage = (card_id: string | number) => {
    let url = new URL(window.StackonetToolkit.ajaxUrl);
    url.searchParams.append('action', 'yousaidit_save_dynamic_card');
    url.searchParams.append('card_id', card_id.toString());

    const a = document.createElement('a');
    a.href = url.toString();
    a.target = '_blank'
    a.click();
    a.remove();
  }

  return {
    ...toRefs(state),
    getCardById,
    createProductOnTradeSite,
    updateCard,
    updateSku,
    acceptCard,
    rejectCard,
    handleAcceptOrReject,
    createProduct,
    changeCommission,
    trashCard,
    handleCommissionUpdate,
    previewDynamicCardPDF,
    generateCardImage,
  }
});

export default useAdminDesignerCardStore;
