# Yousaidit Toolkit

# Dependencies

* `WooCommerce Extra Product Options Pro` is required to work inner message system. Create a checkbox field with
  name `custom_message`
* On Eva theme, `eva-scripts` depends on `wpb_composer_front_js`. On designer profile page, this script is not
  available. So it shows broken style on side navigation.
* On Eva theme, in `header-left.php` file, we need to add the following line before `</header>` html tag to show banner:
  `<?php do_action( 'eva_before_header_left_end' ); ?>`
  
  
