import {Component} from "react";
import {
  Dialog,
  DialogContainer,
  Modal,
  NotificationContainer,
  Notify,
  Spinner,
  SpinnerContainer
} from "@shapla/react-components";
import axios from "../../utils/axios";

interface CommonPublicHolidayInterface {
  label: string,
  date_string: string
}

interface CommonPublicHolidaysStateInterface {
  holidays: CommonPublicHolidayInterface[];
  holiday: CommonPublicHolidayInterface;
  activeItem: CommonPublicHolidayInterface;
  stringToDate: string;
  showAddNewItemModal: boolean;
  showEditItemModal: boolean;
  activeItemIndex: number;
}

export default class CommonPublicHolidays extends Component<any, CommonPublicHolidaysStateInterface> {
  constructor(props) {
    super(props);

    this.state = {
      holidays: [],
      holiday: {label: '', date_string: ''},
      activeItem: {label: '', date_string: ''},
      activeItemIndex: -1,
      showAddNewItemModal: false,
      showEditItemModal: false,
      stringToDate: '',
    }

    this.updateSettings = this.updateSettings.bind(this);
    this.addNewItem = this.addNewItem.bind(this);
    this.openEditModal = this.openEditModal.bind(this);
    this.removeItem = this.removeItem.bind(this);
    this.openAddNewModal = this.openAddNewModal.bind(this);
    this.validateDateString = this.validateDateString.bind(this);
    this.updateActiveHolidayToServer = this.updateActiveHolidayToServer.bind(this);
  }

  componentDidMount() {
    this.setState({holidays: window.StackonetToolkit.common_holidays})
  }

  updateSettings() {
    return new Promise(resolve => {
      Spinner.show();
      axios
        .post('dispatch-timer/settings', {common_holidays: this.state.holidays})
        .then(response => {
          resolve(response.data.data);
          window.StackonetToolkit.common_holidays = response.data.data.common_holidays;
          this.setState({holidays: response.data.data.common_holidays});
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

  addNewItem(event: MouseEvent) {
    event.preventDefault();
    const {holidays, holiday} = this.state;
    holidays.push(holiday);
    this.setState({holidays: holidays});
    this.updateSettings().then(() => {
      this.setState({showAddNewItemModal: false});
    });
  }

  openAddNewModal(event: MouseEvent) {
    event.preventDefault();
    this.setState({showAddNewItemModal: true});
  }

  openEditModal(event: MouseEvent, item: CommonPublicHolidayInterface, index: number) {
    event.preventDefault();
    this.setState({
      showEditItemModal: true,
      activeItemIndex: index,
      activeItem: item
    });
  }

  removeItem(holiday: CommonPublicHolidayInterface) {
    Dialog.confirm('Are you sure to delete this item?').then(confirmed => {
      if (confirmed) {
        const {holidays} = this.state;
        holidays.splice(holidays.indexOf(holiday), 1);
        this.setState({holidays: holidays});
        this.updateSettings();
      }
    })
  }

  validateDateString(dateString: string, event: MouseEvent | null = null) {
    if (event) {
      event.preventDefault();
    }
    axios
      .post('dispatch-timer/string-to-date', {string: dateString})
      .then(response => {
        this.setState({
          stringToDate: response.data.data.date as string
        })
      })
  }

  updateHolidayValue(event: InputEvent, key: string) {
    const value = (event.target as HTMLInputElement).value;
    const {holiday} = this.state;
    if ('label' === key) {
      holiday.label = value;
    }
    if ('date_string' === key) {
      holiday.date_string = value;
    }
    this.setState({holiday: holiday})
  }

  updateActiveHolidayValue(event: InputEvent, key: string) {
    const value = (event.target as HTMLInputElement).value;
    const {activeItem} = this.state;
    if ('label' === key) {
      activeItem.label = value;
    }
    if ('date_string' === key) {
      activeItem.date_string = value;
    }
    this.setState({activeItem: activeItem})
  }

  updateActiveHolidayToServer(event: MouseEvent) {
    event.preventDefault();
    const {holidays, activeItemIndex, activeItem} = this.state;
    holidays[activeItemIndex] = activeItem;
    this.setState({holidays: holidays});
    this.updateSettings().then(() => {
      this.setState({
        showEditItemModal: false,
        activeItem: {label: '', date_string: ''},
        activeItemIndex: -1
      });
    });
  }

  render() {
    const {holidays, showEditItemModal, showAddNewItemModal, holiday, activeItem, stringToDate} = this.state;
    return (
      <>
        <div className='border-box-deep flex flex-wrap'>
          {holidays.map((topic, index) => (
            <div className='w-1/2 p-1' key={index}>
              <div className='p-2 bg-white relative'>
                <div>
                  {topic.label}
                  <div className='text-xs italic text-gray-400'>{topic.date_string}</div>
                </div>
                <div className='absolute top-1 right-1 space-x-2'>
                    <span className='shapla-icon is-hoverable'
                          onClick={event => this.openEditModal(event, topic, index)}>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" className='w-4 h-4 fill-current'>
                      <path d="M0 0h24v24H0V0z" fill="none"/>
                      <path
                        d="M14.06 9.02l.92.92L5.92 19H5v-.92l9.06-9.06M17.66 3c-.25 0-.51.1-.7.29l-1.83 1.83 3.75 3.75 1.83-1.83c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.2-.2-.45-.29-.71-.29zm-3.6 3.19L3 17.25V21h3.75L17.81 9.94l-3.75-3.75z"/>
                    </svg>
                    </span>
                  <span className='shapla-delete-icon' onClick={() => this.removeItem(topic)}></span>
                </div>
              </div>
            </div>
          ))}
        </div>
        <div className="mt-2">
          <button className='button' onClick={this.openAddNewModal}>Add Common Holiday</button>
        </div>
        <Modal active={showAddNewItemModal} title='Add New Holiday'
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
                <label htmlFor="">Label</label>
              </th>
              <td>
                <input type='text' className='regular-text' value={holiday.label}
                       onChange={event => this.updateHolidayValue(event, 'label')}/>
              </td>
            </tr>
            <tr>
              <th>
                <label htmlFor="">Date string</label>
              </th>
              <td>
                <div className='flex items-center space-x-2'>
                  <input type='text' value={holiday.date_string} className='regular-text'
                         onChange={event => this.updateHolidayValue(event, 'date_string')}
                  />
                  <button className='shapla-button is-primary is-outline is-small'
                          onClick={(event) => this.validateDateString(holiday.date_string, event)}>Validate
                  </button>
                </div>
                {stringToDate && <p className='description'>{stringToDate}</p>}
                <p className="description">English textual datetime description. Examples:</p>
                <p className="description">
                  January 1st<br/>
                  December 25th<br/>
                  last Monday of May<br/>
                  last Monday of August<br/>
                  first Monday of May
                </p>
              </td>
            </tr>
            </tbody>
          </table>
        </Modal>
        <Modal active={showEditItemModal} title='Edit Holiday'
               onClose={() => this.setState({showEditItemModal: false})}
               footer={(
                 <>
                   <button className='shapla-button' onClick={() => this.setState({showEditItemModal: false})}>Close
                   </button>
                   <button className='shapla-button is-primary' onClick={this.updateActiveHolidayToServer}>Save</button>
                 </>
               )}
        >
          <table className="form-table">
            <tbody>
            <tr>
              <th>
                <label htmlFor="">Label</label>
              </th>
              <td>
                <input type='text' className='regular-text' value={activeItem.label}
                       onChange={event => this.updateActiveHolidayValue(event, 'label')}/>
              </td>
            </tr>
            <tr>
              <th>
                <label htmlFor="">Date string</label>
              </th>
              <td>
                <div className='flex items-center space-x-2'>
                  <input type='text' value={activeItem.date_string} className='regular-text'
                         onChange={event => this.updateActiveHolidayValue(event, 'date_string')}
                  />
                  <button className='shapla-button is-primary is-outline is-small'
                          onClick={(event) => this.validateDateString(activeItem.date_string, event)}>Validate
                  </button>
                </div>
                {stringToDate && <p className='description'>{stringToDate}</p>}
                <p className="description">English textual datetime description. Examples:</p>
                <p className="description">
                  January 1st<br/>
                  December 25th<br/>
                  last Monday of May<br/>
                  last Monday of August<br/>
                  first Monday of May
                </p>
              </td>
            </tr>
            </tbody>
          </table>
        </Modal>
        <DialogContainer/>
        <NotificationContainer/>
        <SpinnerContainer/>
      </>
    );
  }
}