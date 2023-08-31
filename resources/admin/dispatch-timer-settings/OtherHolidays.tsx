import {Component} from "react";
import {Dialog, Modal, Notify, Spinner} from "@shapla/react-components";
import axios from "../../utils/axios";

interface SpecialHolidayInterface {
  label: string,
  date: string
}

interface OtherHolidaysStateInterface {
  special_holidays: Record<string, SpecialHolidayInterface[]>;
  special_holiday: SpecialHolidayInterface;
  showModal: boolean;
}

export default class OtherHolidays extends Component<any, OtherHolidaysStateInterface> {

  constructor(props) {
    super(props);

    this.state = {
      special_holidays: {},
      special_holiday: {label: '', date: ''},
      showModal: false,
    }

    this.removeItem = this.removeItem.bind(this);
    this.updateSettings = this.updateSettings.bind(this);
    this.openAddNewModal = this.openAddNewModal.bind(this);
    this.addNewItem = this.addNewItem.bind(this);
  }

  componentDidMount() {
    this.setState({special_holidays: window.StackonetToolkit.special_holidays})
  }

  updateSettings() {
    return new Promise(resolve => {
      Spinner.show();
      axios
        .post('dispatch-timer/settings', {special_holidays: this.state.special_holidays})
        .then(response => {
          resolve(response.data.data);
          window.StackonetToolkit.special_holidays = response.data.data.special_holidays;
          this.setState({special_holidays: response.data.data.special_holidays});
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

  removeItem(year: string | number, index: number) {
    Dialog.confirm('Are you sure to delete this item?').then(confirmed => {
      if (confirmed) {
        const {special_holidays} = this.state;
        special_holidays[year].splice(index, 1);
        this.setState({special_holidays: special_holidays});
        this.updateSettings();
      }
    })
  }

  openAddNewModal(event: MouseEvent) {
    event.preventDefault();
    this.setState({showModal: true});
  }

  addNewItem(event: MouseEvent) {
    event.preventDefault();
    const {special_holidays, special_holiday} = this.state;
    const year = special_holiday.date.substring(0, 4);
    if (!special_holidays[year]) {
      special_holidays[year] = [];
    }
    special_holidays[year].push({
      label: special_holiday.label,
      date: special_holiday.date
    });
    this.setState({special_holidays: special_holidays});
    this.updateSettings().then(() => {
      this.setState({showModal: false});
    });
  }

  updateHolidayValue(event: InputEvent, key: string) {
    const value = (event.target as HTMLInputElement).value;
    const {special_holiday} = this.state;
    if ('label' === key) {
      special_holiday.label = value;
    }
    if ('date' === key) {
      special_holiday.date = value;
    }
    this.setState({special_holiday: special_holiday})
  }

  render() {
    const {special_holidays, showModal, special_holiday} = this.state;
    return (
      <>
        <div className='border-box-deep flex flex-col'>
          {Object.keys(special_holidays).map((year) => (
            <div>
              <div className='mb-1 font-bold text-lg'>{year}</div>
              <div className='border-box-deep flex flex-wrap'>
                {special_holidays[year].map((item, index) => (
                  <div className='w-1/2 p-1' key={`${year}-${index}`}>
                    <div className='p-2 bg-white relative'>
                      <div>
                        {item.label}
                        <div className='text-xs italic text-gray-400'>{item.date}</div>
                      </div>
                      <div className='absolute top-1 right-1 space-x-2'>
                        <span className='shapla-delete-icon' onClick={() => this.removeItem(year, index)}></span>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          ))}
        </div>
        <div className="mt-2">
          <button className='button' onClick={this.openAddNewModal}>Add Holiday</button>
        </div>
        <Modal active={showModal} title='Add New Holiday'
               onClose={() => this.setState({showModal: false})}
               footer={(
                 <>
                   <button className='shapla-button' onClick={() => this.setState({showModal: false})}>Close
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
                <input type='text' className='regular-text' value={special_holiday.label}
                       onChange={event => this.updateHolidayValue(event, 'label')}/>
              </td>
            </tr>
            <tr>
              <th>
                <label htmlFor="">Date</label>
              </th>
              <td>
                <div className='flex items-center space-x-2'>
                  <input type='date' value={special_holiday.date} className='regular-text'
                         onChange={event => this.updateHolidayValue(event, 'date')}
                  />
                </div>
              </td>
            </tr>
            </tbody>
          </table>
        </Modal>
      </>
    );
  }
}