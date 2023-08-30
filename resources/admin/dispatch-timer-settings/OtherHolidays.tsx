import {Component} from "react";
import {Dialog, Notify, Spinner} from "@shapla/react-components";
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

  render() {
    const {special_holidays} = this.state;
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

      </>
    );
  }
}