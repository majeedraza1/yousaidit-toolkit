import {Component, HTMLAttributes, ReactNode} from "react";
import axios from "../../utils/axios";
import {
  Dialog,
  DialogContainer,
  Modal,
  NotificationContainer,
  Notify,
  Spinner,
  SpinnerContainer
} from "@shapla/react-components";

interface RecipientItemInterface {
  slug: string;
  label: string;
  menu_order?: number;
}

interface RecipientPropsInterface extends HTMLAttributes<HTMLElement> {
  children: ReactNode
}

interface RecipientStateInterface {
  recipients: RecipientItemInterface[];
  recipient: RecipientItemInterface;
  showAddNewItemModal: boolean;
  activeRecipient: RecipientItemInterface;
  activeRecipientIndex: number;
  showEditItemModal: boolean;
}

export default class Recipient extends Component<RecipientPropsInterface, RecipientStateInterface> {
  constructor(props: RecipientPropsInterface) {
    super(props);

    this.state = {
      recipients: [],
      recipient: {label: '', slug: '', menu_order: 0},
      activeRecipient: {label: '', slug: '', menu_order: 0},
      activeRecipientIndex: -1,
      showAddNewItemModal: false,
      showEditItemModal: false,
    }

    this.addNewItem = this.addNewItem.bind(this);
    this.removeItem = this.removeItem.bind(this);
    this.openAddNewModal = this.openAddNewModal.bind(this);
    this.openEditModal = this.openEditModal.bind(this);
    this.updateRecipientValue = this.updateRecipientValue.bind(this);
    this.updateActiveRecipient = this.updateActiveRecipient.bind(this);
    this.updateSettings = this.updateSettings.bind(this);
    this.updateActiveRecipientToServer = this.updateActiveRecipientToServer.bind(this);
  }

  componentDidMount() {
    this.setState({recipients: window.StackonetToolkit.recipients})
  }

  addNewItem(event: MouseEvent) {
    event.preventDefault();
    const {recipients, recipient} = this.state;
    recipients.push(recipient);
    this.setState({recipients: recipients});
    this.updateSettings().then(() => {
      this.setState({showAddNewItemModal: false});
    });
  }

  removeItem(recipient: RecipientItemInterface) {
    Dialog.confirm('Are you sure to delete this item?').then(confirmed => {
      if (confirmed) {
        const {recipients} = this.state;
        recipients.splice(recipients.indexOf(recipient), 1);
        this.setState({recipients: recipients});
        this.updateSettings();
      }
    })
  }

  updateSettings() {
    return new Promise(resolve => {
      Spinner.show();
      axios
        .post('ai-content-generator/settings', {recipients: this.state.recipients})
        .then(response => {
          resolve(response.data.data);
          window.StackonetToolkit.recipients = response.data.data.recipients;
          this.setState({recipients: response.data.data.recipients});
          Notify.success('Settings have been updated.', 'Success!');
        })
        .catch(error => {
          Notify.error('Settings have been updated.', 'Error!');
        })
        .finally(() => {
          Spinner.hide();
        })
    })
  }

  updateRecipientValue(event: InputEvent) {
    const value = (event.target as HTMLInputElement).value;
    const {recipient} = this.state;
    recipient.label = value;
    recipient.slug = value;
    this.setState({recipient: recipient})
  }

  openAddNewModal(event: MouseEvent) {
    event.preventDefault();
    this.setState({showAddNewItemModal: true});
  }

  openEditModal(event: MouseEvent, item: RecipientItemInterface, index: number) {
    event.preventDefault();
    this.setState({
      showEditItemModal: true,
      activeRecipientIndex: index,
      activeRecipient: item
    });
  }

  updateActiveRecipient(event: InputEvent) {
    const value = (event.target as HTMLInputElement).value;
    const {activeRecipient} = this.state;
    activeRecipient.label = value;
    activeRecipient.slug = value;
    this.setState({activeRecipient: activeRecipient})
  }

  updateActiveRecipientToServer(event: MouseEvent) {
    event.preventDefault();
    const {recipients, activeRecipient, activeRecipientIndex} = this.state;
    recipients[activeRecipientIndex] = activeRecipient;
    this.setState({recipients: recipients});
    this.updateSettings().then(() => {
      this.setState({
        showEditItemModal: false,
        activeRecipient: {label: '', slug: '', menu_order: 0},
        activeRecipientIndex: -1
      });
    });
  }

  render() {
    return (
      <div className='border-box-deep'>
        <div className='mb-4'>
          <div className='flex flex-wrap -m-1'>
            {this.state.recipients.map((recipient, index) => (
              <div className='w-1/2 p-1' key={recipient.slug}>
                <div className='p-2 bg-white relative'>
                  <div>
                    {recipient.label}
                    <div className='text-xs italic text-gray-400'>{recipient.slug}</div>
                  </div>
                  <div className='absolute top-1 right-1 space-x-2'>
                    <span className='shapla-icon is-hoverable'
                          onClick={event => this.openEditModal(event, recipient, index)}>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" className='w-4 h-4 fill-current'>
                      <path d="M0 0h24v24H0V0z" fill="none"/>
                      <path
                        d="M14.06 9.02l.92.92L5.92 19H5v-.92l9.06-9.06M17.66 3c-.25 0-.51.1-.7.29l-1.83 1.83 3.75 3.75 1.83-1.83c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.2-.2-.45-.29-.71-.29zm-3.6 3.19L3 17.25V21h3.75L17.81 9.94l-3.75-3.75z"/>
                    </svg>
                    </span>
                    <span className='shapla-delete-icon' onClick={() => this.removeItem(recipient)}></span>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
        <button className='button' onClick={this.openAddNewModal}>Add New Recipient</button>
        <Modal active={this.state.showAddNewItemModal} title='Add New Recipient'
               onClose={() => this.setState({showAddNewItemModal: false})}
               footer={(
                 <>
                   <button className='shapla-button' onClick={() => this.setState({showAddNewItemModal: false})}>Close
                   </button>
                   <button className='shapla-button is-primary' onClick={this.addNewItem}>Save</button>
                 </>
               )}
        >
          <table className="form-table">
            <tbody>
            <tr>
              <th>
                <label htmlFor="">Recipient label</label>
              </th>
              <td>
                <input type='text' value={this.state.recipient.label} onChange={this.updateRecipientValue}/>
                <p className="description">Recipient label. Must be unique for recipient.</p>
              </td>
            </tr>
            </tbody>
          </table>
        </Modal>
        <Modal active={this.state.showEditItemModal} title='Edit Recipient'
               onClose={() => this.setState({showEditItemModal: false})}
               footer={(
                 <>
                   <button className='shapla-button' onClick={() => this.setState({showEditItemModal: false})}>Close
                   </button>
                   <button className='shapla-button is-primary' onClick={this.updateActiveRecipientToServer}>Update
                   </button>
                 </>
               )}
        >
          <table className="form-table">
            <tbody>
            <tr>
              <th>
                <label htmlFor="">Recipient label</label>
              </th>
              <td>
                <input type='text' value={this.state.activeRecipient.label} onInput={this.updateActiveRecipient}/>
                <p className="description">Recipient label. Must be unique for recipient.</p>
              </td>
            </tr>
            </tbody>
          </table>
        </Modal>
        <NotificationContainer/>
        <DialogContainer/>
        <SpinnerContainer isRootSpinner={true}/>
      </div>
    );
  }
}
