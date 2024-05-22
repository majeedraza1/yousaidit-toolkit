import axios from "../../utils/axios";
import {Notify, Spinner} from "@shapla/vanilla-components";
import {defineStore} from "pinia";
import {computed, reactive, toRefs} from "vue";
import {DesignerInterface, UploadedAttachmentInterface} from "../../interfaces/designer.ts";


const useDesignerProfileStore = defineStore('designer-profile', () => {
  const state = reactive<{
    designer: DesignerInterface;
    images: UploadedAttachmentInterface[];
    current_password: string;
    new_password: string;
    confirm_password: string;
  }>({
    designer: {
      id: 0,
      display_name: '',
      first_name: '',
      last_name: '',
      paypal_email: '',
      description: '',
      user_url: '',
      location: '',
      business_name: '',
      formatted_address: '',
      user_login: '',
      profile_base_url: '',
      instagram_url: '',
      business_address: {
        address_1: '',
        address_2: '',
        city: '',
        post_code: '',
        country: '',
        state: '',
      },
      vat_registration_number: '',
      vat_certificate_issue_date: '',
      avatar_url: '',
      cover_photo_url: '',
      email: '',
      card_logo_url: '',
      total_cards: 0,
      maximum_allowed_card: 0,
      can_add_dynamic_card: false,
      total_sales: 0,
      avatar_id: 0,
      card_logo_id: 0,
      cover_photo_id: 0
    },
    images: [],
    current_password: '',
    new_password: '',
    confirm_password: '',
  });

  const designer_id = window.DesignerProfile.user.id

  const attachment_upload_url = computed(() => {
    return window.DesignerProfile.restRoot + '/designers/' + designer_id + '/attachment'
  })

  const displayNameOptions = computed<string[]>(() => {
    let options = [];
    if (!state.designer) {
      return options;
    }

    if (state.designer.first_name) {
      options.push(state.designer.first_name);
    }

    if (state.designer.last_name) {
      options.push(state.designer.last_name);
    }

    if (state.designer.first_name && state.designer.last_name) {
      options.push(`${state.designer.first_name} ${state.designer.last_name}`);
    }

    if (state.designer.business_name) {
      options.push(state.designer.business_name);
    }

    if (state.designer.display_name && options.indexOf(state.designer.display_name) === -1) {
      options.push(state.designer.display_name);
    }

    return options;
  })

  const getUserData = () => {
    return new Promise(resolve => {
      Spinner.show();
      axios.get('designers/' + designer_id).then(response => {
        Spinner.hide();
        let data = response.data.data;
        state.designer = data.designer;
        resolve(data.designer)
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

  const getUserUploadedImages = () => {
    Spinner.show();
    axios
      .get('designers/' + designer_id + '/attachment', {
        params: {
          mime_types: ['image/jpeg', 'image/png']
        }
      })
      .then(response => {
        Spinner.hide();
        state.images = response.data.data;
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

  const update = (data: Record<string, any>) => {
    Spinner.show();
    axios.put('designers/' + designer_id, data)
      .then(response => {
        Notify.success('Profile updated.');
        let data = response.data.data;
        state.designer = data.designer;
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

  const updateProfileUrl = () => {
    let currentLogin = state.designer.user_login;
    Spinner.show();
    axios
      .put('designers/' + designer_id, {user_login: state.designer.user_login})
      .then(response => {
        Notify.success('Profile updated.');
        state.designer = response.data.data.designer;
        window.location.reload();
      })
      .catch(errors => {
        state.designer.user_login = currentLogin;
        if (typeof errors.response.data.message === "string") {
          Notify.error(errors.response.data.message, 'Error!');
        }
      })
      .finally(() => {
        Spinner.hide();
      });
  }

  return {
    ...toRefs(state),
    designer_id,
    user: window.DesignerProfile.user,
    uploadUrl: attachment_upload_url,
    displayNameOptions,
    getUserData,
    getUserUploadedImages,
    update,
    updateProfileUrl,
  }
});

export default useDesignerProfileStore;
