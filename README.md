# Yousaidit Toolkit

# Upgrade to Vue 3

| Module                                                  | Status  |
|---------------------------------------------------------|---------|
| Admin Art Work                                          | &check; |
| Admin Designer                                          | &check; |
| Admin Font Manager                                      | &check; |
| Admin Inner message editor                              | &check; |
| Admin Order Dispatcher                                  | &check; |
| Admin Reminder                                          | &check; |
| Admin Settings: Dispatch Timer                          | &check; |
| Admin Settings: Inner Message AI content writer         | &check; |
| Admin Tree Planting                                     | &check; |
| Frontend Designer Dashboard                             | &check; |
| Frontend MyAccount Reminder                             | &check; |
| Frontend Single Product: Inner Message (& Dynamic card) | &check; |
| Frontend Single Product: variation swatches             | &check; |
| Frontend Shop: Inner Message (& Dynamic card) Popup     | &check; |
| Frontend Shop: Rude Card                                | &check; |
| Frontend Multi-step checkout                            | &check; |

# Dependencies

* `WooCommerce Extra Product Options Pro` is required to work inner message system. Create a checkbox field with
  name `custom_message`
* On Eva theme, `eva-scripts` depends on `wpb_composer_front_js`. On designer profile page, this script is not
  available. So it shows broken style on side navigation.
* On Eva theme, in `header-left.php` file, we need to add the following line before `</header>` html tag to show banner:
  `<?php do_action( 'eva_before_header_left_end' ); ?>`

There are few directories that need to be place inside **wp-content/uploads** directory.

* `emoji-assets-6.0.0`
* `envelope-colours`
* `yousaidit-web-fonts`

# Custom Database Tables

The plugin create and user the following tables to handle it functionality.

* `sessions`
* `user_address`
* `user_session`
* `user_social_auth_provider`
* `ship_station_orders`
* `ship_station_orders_items`
* `ship_station_orders_addresses`
* `designer_cards`
* `designer_commissions`
* `designer_payments`
* `designer_payment_items`

# Local development setting

User: John's Cards
Email: kelsojohn10@sky.com
password: kelsojohn10@sky.com

# Testing Single Inner message PDF

Open the URL in browser replacing `order_id` and `item_id`

```html
/wp-admin/admin-ajax.php?
order_id=37789
&item_id=53227
&mode=pdf
&action=yousaidit_single_im_card
```

## Reminder Module

**Admin Menu**

* Reminders: All reminders created by users are viewable here.
* Reminders Groups: All reminder groups viewable here.
    * Create/Edit: Create or edit reminder group.
        * Title: Title of the reminder group. It will show to user to create reminder.
        * Product categories: WooCommerce product categories. It will be used when sending email to user for reminder.
        * Date: Default date of occasion. Only set when date is fixed. e.g. Valentine's Day is always on 14th February.
        * Call to Action: Custom url to redirect user when clicking on 'Shop cards' button on reminder.
        * Menu Order: Order of the reminder group.
* Reminders Queue: All reminders queue viewable here.

**My-Account Menu**

* Reminders: All reminders created by the user are viewable here.
    * Create/Edit: Create or edit reminder.

**How does it work?**

It registers three cron events.

* One to check due reminders (when remind_date <= current_date) two times in a day and push them to background task to
  add on 'Reminders Queue' list.
* Second to check due reminders queue (when remind_date <= current_date < occasion_date ) and set background task to
  send email to user.
* Third to check expired (recurring) reminders and set background task to change occasion_date and remind_date to next
  year.
