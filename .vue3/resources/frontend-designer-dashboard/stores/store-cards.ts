import axios from "../../utils/axios";
import {Notify, Spinner} from "@shapla/vanilla-components";
import {defineStore} from "pinia";
import {computed, reactive, toRefs} from "vue";
import {
  DesignerCardModelInterface,
  PhotoCardBaseInterface,
  ServerCardCollectionResponseInterface,
  StandardCardBaseInterface,
  TextCardBaseInterface
} from "../../interfaces/designer-card.ts";
import {FontInfoInterface} from "../../interfaces/custom-font.ts";

interface DesignerCardStateInterface extends ServerCardCollectionResponseInterface {
  activeCardComments: Record<string, any>[];
}

const useDesignerCardStore = defineStore('designer-cards', () => {
  const state = reactive<DesignerCardStateInterface>({
    items: [],
    pagination: {total_items: 0, current_page: 1, per_page: 12},
    maximum_allowed_card: 0,
    can_add_dynamic_card: false,
    total_cards: 0,
    statuses: [],
    activeCardComments: [],
  });

  const designer_id = computed<number>(() => window.DesignerProfile.user.id)

  const getDesignerCards = (per_page: number = 12, current_page: number = 1) => {
    return new Promise(resolve => {
      Spinner.show();
      axios
        .get('designers/' + designer_id.value + '/cards', {
          params: {per_page: per_page, page: current_page}
        })
        .then(response => {
          const data = response.data.data as ServerCardCollectionResponseInterface;
          state.items = data.items;
          state.pagination = data.pagination;
          state.maximum_allowed_card = data.maximum_allowed_card;
          state.can_add_dynamic_card = data.can_add_dynamic_card;
          state.total_cards = data.total_cards;
          state.statuses = data.statuses;
          resolve(data);
        })
        .catch(errors => {
          if (errors.response.data.message) {
            Notify.error(errors.response.data.message, 'Error!');
          }
        })
        .finally(() => {
          Spinner.hide();
        });
    });
  }

  const createStandardCard = (payload: StandardCardBaseInterface) => {
    return new Promise(resolve => {
      Spinner.show();
      axios.post('designers/' + designer_id.value + '/standard-cards', payload)
        .then(response => {
          resolve(response.data.data);
          Notify.success('New card has been submitted.', 'Success!');
        })
        .catch(errors => {
          if (errors.response.data.message) {
            Notify.error(errors.response.data.message, 'Error!');
          }
        })
        .finally(() => {
          Spinner.hide();
        });
    })
  }

  const createMug = (payload: StandardCardBaseInterface) => {
    return new Promise(resolve => {
      Spinner.show();
      axios.post('designers/' + designer_id.value + '/mug', payload)
        .then(response => {
          resolve(response.data.data);
          Notify.success('New card has been submitted.', 'Success!');
        })
        .catch(errors => {
          if (errors.response.data.message) {
            Notify.error(errors.response.data.message, 'Error!');
          }
        })
        .finally(() => {
          Spinner.hide();
        });
    })
  }

  const createPhotoCard = (payload: PhotoCardBaseInterface) => {
    return new Promise(resolve => {
      Spinner.show();
      axios.post('designers/' + designer_id.value + '/photo-cards', payload)
        .then(response => {
          resolve(response.data.data);
          Notify.success('New card has been submitted.', 'Success!');
        })
        .catch(errors => {
          if (errors.response.data.message) {
            Notify.error(errors.response.data.message, 'Error!');
          }
        })
        .finally(() => {
          Spinner.hide();
        });
    })
  }

  const createTextCard = (payload: TextCardBaseInterface) => {
    return new Promise(resolve => {
      Spinner.show();
      axios.post('designers/' + designer_id.value + '/text-cards', payload)
        .then(response => {
          resolve(response.data.data);
          Notify.success('New card has been submitted.', 'Success!');
        })
        .catch(errors => {
          if (errors.response.data.message) {
            Notify.error(errors.response.data.message, 'Error!');
          }
        })
        .finally(() => {
          Spinner.hide();
        });
    })
  }

  const requestLimitExtend = (up_limit_to: number) => {
    return new Promise(resolve => {
      Spinner.show();
      axios.post('designers/extend-card-limit', {up_limit_to: up_limit_to}).then(() => {
        Notify.success('Your message has been sent.', 'Success!');
        resolve(true);
      }).catch(errors => {
        if (errors.response.data.message) {
          Notify.error(errors.response.data.message, 'Error!');
        }
      }).finally(() => {
        Spinner.hide();
      })
    })
  }

  const deleteCard = (card: DesignerCardModelInterface): Promise<DesignerCardModelInterface> => {
    return new Promise(resolve => {
      Spinner.show();
      axios.delete('designers/' + designer_id.value + '/cards/' + card.id, {params: {action: 'delete'}})
        .then(() => {
          resolve(card);
        })
        .catch(errors => {
          if (errors.response.data.message) {
            Notify.error(errors.response.data.message, 'Error!');
          }
        })
        .finally(() => {
          Spinner.hide();
        })
    })
  }

  const getCardComments = (card: DesignerCardModelInterface) => {
    return new Promise(resolve => {
      Spinner.show();
      axios
        .get(`designers/${designer_id.value}/cards/${card.id}/comments`)
        .then(response => {
          const comments = response.data.data.comments;
          state.activeCardComments = comments;
          resolve(comments);
        })
        .catch(errors => {
          if (errors.response.data.message) {
            Notify.error(errors.response.data.message, 'Error!');
          }
        })
        .finally(() => {
          Spinner.hide();
        })
    })
  }

  const submitRequest = (activeCard: DesignerCardModelInterface, request_for: 'pause' | 'remove', message: string) => {
    return new Promise(resolve => {
      Spinner.show();
      axios.put(`designers/${designer_id.value}/cards/${activeCard.id}/requests`, {
        request_for: request_for,
        message: message,
      })
        .then(() => {
          Notify.success('You request has been sent to admin.', 'Request Submitted!')
          resolve(activeCard);
        })
        .catch(errors => {
          if (errors.response.data.message) {
            Notify.error(errors.response.data.message, 'Error!');
          }
        })
        .finally(() => {
          Spinner.hide();
        })
    })
  }

  const createNewFont = (data: FormData | Record<string, string>): Promise<FontInfoInterface> => {
    return new Promise(resolve => {
      Spinner.show();
      axios.post('designers/fonts', data, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      })
        .then(response => {
          resolve(response.data.data as FontInfoInterface);
          Notify.success('Font setting has been updated.', 'Success!');
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

  const deleteImage = (id: number) => {
    return new Promise((resolve, reject) => {
      Spinner.show();
      axios
        .delete(`designers-attachment/${id}`)
        .then(() => {
          resolve(true);
        })
        .catch(errors => {
          reject(errors.response.data);
        })
        .finally(() => {
          Spinner.hide();
        })
    })
  }

  const attachment_upload_url = computed(() => {
    return window.DesignerProfile.restRoot + '/designers/' + designer_id.value + '/attachment'
  })

  return {
    ...toRefs(state),
    designer_id,
    getDesignerCards,
    createStandardCard,
    createMug,
    createPhotoCard,
    createTextCard,
    requestLimitExtend,
    deleteCard,
    submitRequest,
    getCardComments,
    createNewFont,
    deleteImage,
    attachment_upload_url,
  }
});

export default useDesignerCardStore;
