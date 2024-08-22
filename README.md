# Yousaidit Toolkit

A powerful WordPress plugin to extend functionality to your WordPress site.

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

* On Eva theme, `eva-scripts` depends on `wpb_composer_front_js`. On designer profile page, this script is not
  available. So it shows broken style on side navigation.
* On Eva theme, in `header-left.php` file, we need to add the following line before `</header>` html tag to show banner:
  `<?php do_action( 'eva_before_header_left_end' ); ?>`
