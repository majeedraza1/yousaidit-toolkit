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

interface TopicItemInterface {
  slug: string;
  label: string;
  menu_order?: number;
}

interface TopicPropsInterface extends HTMLAttributes<HTMLElement> {
  children: ReactNode
}

interface TopicStateInterface {
  topics: TopicItemInterface[];
  topic: TopicItemInterface;
  showAddNewItemModal: boolean;
  activeTopic: TopicItemInterface;
  activeTopicIndex: number;
  showEditItemModal: boolean;
}

export default class Topic extends Component<TopicPropsInterface, TopicStateInterface> {
  constructor(props: TopicPropsInterface) {
    super(props);

    this.state = {
      topics: [],
      topic: {label: '', slug: '', menu_order: 0},
      activeTopic: {label: '', slug: '', menu_order: 0},
      activeTopicIndex: -1,
      showAddNewItemModal: false,
      showEditItemModal: false,
    }

    this.addNewItem = this.addNewItem.bind(this);
    this.removeItem = this.removeItem.bind(this);
    this.openAddNewModal = this.openAddNewModal.bind(this);
    this.openEditModal = this.openEditModal.bind(this);
    this.updateTopicValue = this.updateTopicValue.bind(this);
    this.updateActiveTopic = this.updateActiveTopic.bind(this);
    this.updateSettings = this.updateSettings.bind(this);
    this.updateActiveTopicToServer = this.updateActiveTopicToServer.bind(this);
  }

  componentDidMount() {
    this.setState({topics: window.StackonetToolkit.topics})
  }

  addNewItem(event: MouseEvent) {
    event.preventDefault();
    const {topics, topic} = this.state;
    topics.push(topic);
    this.setState({topics: topics});
    this.updateSettings().then(() => {
      this.setState({showAddNewItemModal: false});
    });
  }

  removeItem(topic: TopicItemInterface) {
    Dialog.confirm('Are you sure to delete this item?').then(confirmed => {
      if (confirmed) {
        const {topics} = this.state;
        topics.splice(topics.indexOf(topic), 1);
        this.setState({topics: topics});
        this.updateSettings();
      }
    })
  }

  updateSettings() {
    return new Promise(resolve => {
      Spinner.show();
      axios
        .post('ai-content-generator/settings', {topics: this.state.topics})
        .then(response => {
          resolve(response.data.data);
          window.StackonetToolkit.topics = response.data.data.topics;
          this.setState({topics: response.data.data.topics});
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

  updateTopicValue(event: InputEvent) {
    const value = (event.target as HTMLInputElement).value;
    const {topic} = this.state;
    topic.label = value;
    topic.slug = value;
    this.setState({topic: topic})
  }

  openAddNewModal(event: MouseEvent) {
    event.preventDefault();
    this.setState({showAddNewItemModal: true});
  }

  openEditModal(event: MouseEvent, item: TopicItemInterface, index: number) {
    event.preventDefault();
    this.setState({
      showEditItemModal: true,
      activeTopicIndex: index,
      activeTopic: item
    });
  }

  updateActiveTopic(event: InputEvent) {
    const value = (event.target as HTMLInputElement).value;
    const {activeTopic} = this.state;
    activeTopic.label = value;
    activeTopic.slug = value;
    this.setState({activeTopic: activeTopic})
  }

  updateActiveTopicToServer(event: MouseEvent) {
    event.preventDefault();
    const {topics, activeTopic, activeTopicIndex} = this.state;
    topics[activeTopicIndex] = activeTopic;
    this.setState({topics: topics});
    this.updateSettings().then(() => {
      this.setState({
        showEditItemModal: false,
        activeTopic: {label: '', slug: '', menu_order: 0},
        activeTopicIndex: -1
      });
    });
  }

  render() {
    return (
      <div className='border-box-deep'>
        <div className='mb-4'>
          <div className='flex flex-wrap -m-1'>
            {this.state.topics.map((topic, index) => (
              <div className='w-1/2 p-1' key={topic.slug}>
                <div className='p-2 bg-white relative'>
                  <div>
                    {topic.label}
                    <div className='text-xs italic text-gray-400'>{topic.slug}</div>
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
        </div>
        <button className='button' onClick={this.openAddNewModal}>Add New Topic</button>
        <Modal active={this.state.showAddNewItemModal} title='Add New Topic'
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
                <label htmlFor="">Topic label</label>
              </th>
              <td>
                <input type='text' value={this.state.topic.label} onChange={this.updateTopicValue}/>
                <p className="description">Topic label. Must be unique for topic.</p>
              </td>
            </tr>
            </tbody>
          </table>
        </Modal>
        <Modal active={this.state.showEditItemModal} title='Edit Topic'
               onClose={() => this.setState({showEditItemModal: false})}
               footer={(
                 <>
                   <button className='shapla-button' onClick={() => this.setState({showEditItemModal: false})}>Close
                   </button>
                   <button className='shapla-button is-primary' onClick={this.updateActiveTopicToServer}>Update
                   </button>
                 </>
               )}
        >
          <table className="form-table">
            <tbody>
            <tr>
              <th>
                <label htmlFor="">Topic label</label>
              </th>
              <td>
                <input type='text' value={this.state.activeTopic.label} onInput={this.updateActiveTopic}/>
                <p className="description">Topic label. Must be unique for topic.</p>
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
