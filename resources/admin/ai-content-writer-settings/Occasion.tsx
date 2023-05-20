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

interface OccasionItemInterface {
  slug: string;
  label: string;
  menu_order?: number;
}

interface OccasionPropsInterface extends HTMLAttributes<HTMLElement> {
  children: ReactNode
}

interface OccasionStateInterface {
  occasions: OccasionItemInterface[];
  occasion: OccasionItemInterface;
  showAddNewItemModal: boolean;
  activeOccasion: OccasionItemInterface;
  activeOccasionIndex: number;
  showEditItemModal: boolean;
}

export default class Occasion extends Component<OccasionPropsInterface, OccasionStateInterface> {
  constructor(props: OccasionPropsInterface) {
    super(props);

    this.state = {
      occasions: [],
      occasion: {label: '', slug: '', menu_order: 0},
      activeOccasion: {label: '', slug: '', menu_order: 0},
      activeOccasionIndex: -1,
      showAddNewItemModal: false,
      showEditItemModal: false,
    }

    this.addNewItem = this.addNewItem.bind(this);
    this.removeItem = this.removeItem.bind(this);
    this.openAddNewModal = this.openAddNewModal.bind(this);
    this.openEditModal = this.openEditModal.bind(this);
    this.updateOccasionValue = this.updateOccasionValue.bind(this);
    this.updateActiveOccasion = this.updateActiveOccasion.bind(this);
    this.updateSettings = this.updateSettings.bind(this);
    this.updateActiveOccasionToServer = this.updateActiveOccasionToServer.bind(this);
  }

  componentDidMount() {
    this.setState({occasions: window.StackonetToolkit.occasions})
  }

  addNewItem(event: MouseEvent) {
    event.preventDefault();
    const {occasions, occasion} = this.state;
    occasions.push(occasion);
    this.setState({occasions: occasions});
    this.updateSettings().then(() => {
      this.setState({showAddNewItemModal: false});
    });
  }

  removeItem(occasion: OccasionItemInterface) {
    Dialog.confirm('Are you sure to delete this item?').then(confirmed => {
      if (confirmed) {
        const {occasions} = this.state;
        occasions.splice(occasions.indexOf(occasion), 1);
        this.setState({occasions: occasions});
        this.updateSettings();
      }
    })
  }

  updateSettings() {
    return new Promise(resolve => {
      Spinner.show();
      axios
        .post('ai-content-generator/settings', {occasions: this.state.occasions})
        .then(response => {
          resolve(response.data.data);
          window.StackonetToolkit.occasions = response.data.data.occasions;
          this.setState({occasions: response.data.data.occasions});
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

  updateOccasionValue(event: InputEvent) {
    const value = (event.target as HTMLInputElement).value;
    const {occasion} = this.state;
    occasion.label = value;
    occasion.slug = value;
    this.setState({occasion: occasion})
  }

  openAddNewModal(event: MouseEvent) {
    event.preventDefault();
    this.setState({showAddNewItemModal: true});
  }

  openEditModal(event: MouseEvent, item: OccasionItemInterface, index: number) {
    event.preventDefault();
    this.setState({
      showEditItemModal: true,
      activeOccasionIndex: index,
      activeOccasion: item
    });
  }

  updateActiveOccasion(event: InputEvent) {
    const value = (event.target as HTMLInputElement).value;
    const {activeOccasion} = this.state;
    activeOccasion.label = value;
    activeOccasion.slug = value;
    this.setState({activeOccasion: activeOccasion})
  }

  updateActiveOccasionToServer(event: MouseEvent) {
    event.preventDefault();
    const {occasions, activeOccasion, activeOccasionIndex} = this.state;
    occasions[activeOccasionIndex] = activeOccasion;
    this.setState({occasions: occasions});
    this.updateSettings().then(() => {
      this.setState({
        showEditItemModal: false,
        activeOccasion: {label: '', slug: '', menu_order: 0},
        activeOccasionIndex: -1
      });
    });
  }

  render() {
    return (
      <div className='border-box-deep'>
        <div className='mb-4'>
          <div className='flex flex-wrap -m-1'>
            {this.state.occasions.map((occasion, index) => (
              <div className='w-1/2 p-1' key={occasion.slug}>
                <div className='p-2 bg-white relative'>
                  <div>
                    {occasion.label}
                    <div className='text-xs italic text-gray-400'>{occasion.slug}</div>
                  </div>
                  <div className='absolute top-1 right-1 space-x-2'>
                    <span className='shapla-icon is-hoverable'
                          onClick={event => this.openEditModal(event, occasion, index)}>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" className='w-4 h-4 fill-current'>
                      <path d="M0 0h24v24H0V0z" fill="none"/>
                      <path
                        d="M14.06 9.02l.92.92L5.92 19H5v-.92l9.06-9.06M17.66 3c-.25 0-.51.1-.7.29l-1.83 1.83 3.75 3.75 1.83-1.83c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.2-.2-.45-.29-.71-.29zm-3.6 3.19L3 17.25V21h3.75L17.81 9.94l-3.75-3.75z"/>
                    </svg>
                    </span>
                    <span className='shapla-delete-icon' onClick={() => this.removeItem(occasion)}></span>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
        <button className='button' onClick={this.openAddNewModal}>Add New Occasion</button>
        <Modal active={this.state.showAddNewItemModal} title='Add New Occasion'
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
                <label htmlFor="">Occasion label</label>
              </th>
              <td>
                <input type='text' value={this.state.occasion.label} onChange={this.updateOccasionValue}/>
                <p className="description">Occasion label. Must be unique for occasion.</p>
              </td>
            </tr>
            </tbody>
          </table>
        </Modal>
        <Modal active={this.state.showEditItemModal} title='Edit Occasion'
               onClose={() => this.setState({showEditItemModal: false})}
               footer={(
                 <>
                   <button className='shapla-button' onClick={() => this.setState({showEditItemModal: false})}>Close
                   </button>
                   <button className='shapla-button is-primary' onClick={this.updateActiveOccasionToServer}>Update
                   </button>
                 </>
               )}
        >
          <table className="form-table">
            <tbody>
            <tr>
              <th>
                <label htmlFor="">Occasion label</label>
              </th>
              <td>
                <input type='text' value={this.state.activeOccasion.label} onInput={this.updateActiveOccasion}/>
                <p className="description">Occasion label. Must be unique for occasion.</p>
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
