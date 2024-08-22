# Yousaidit Toolkit

# Upgrade to Vue 3

| Module                                                  | Status  | Re-test |
|---------------------------------------------------------|---------|---------|
| Admin Art Work                                          | &check; |         |
| Admin Designer                                          | &check; |         |
| Admin Font Manager                                      | &check; |         |
| Admin Inner message editor                              | &check; |         |
| Admin Order Dispatcher                                  | &check; |         |
| Admin Reminder                                          | &check; |         |
| Admin Settings: Dispatch Timer                          | &check; |         |
| Admin Settings: Inner Message AI content writer         | &check; |         |
| Admin Tree Planting                                     | &check; |         |
| Frontend Designer Dashboard                             | &check; |         |
| Frontend MyAccount Reminder                             | &check; |         |
| Frontend Single Product: variation swatches             | &check; |         |
| Frontend Single Product: Inner Message (& Dynamic card) | &check; |         |
| Frontend Shop: Inner Message (& Dynamic card) Popup     | &check; |         |
| Frontend Cart: Inner Message (& Dynamic card) Popup     | &check; |         |
| Frontend Shop: Rude Card                                | &check; | &check; |
| Frontend Multi-step checkout                            | &check; | &check; |

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
