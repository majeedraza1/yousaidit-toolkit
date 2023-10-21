import Notify from "./Notify";
import notificationContainer from "./notification-container";
import notificationItem from "./notification-item";

const container = notificationContainer();
Notify.on((option) => {
  container.append(notificationItem(option));
});

export default Notify;