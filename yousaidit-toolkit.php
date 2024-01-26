<?php
/**
 * Plugin Name: Yousaidit Toolkit
 * Description: A powerful WordPress plugin to extend functionality to your WordPress site.
 * Version: 2024.1.22
 * Author: Stackonet Services (Pvt.) Ltd.
 * Author URI: https://stackonet.com
 * Requires at least: 5.5
 * Requires PHP: 7.2
 * Text Domain: yousaidit-toolkit
 * Domain Path: /languages
 * WC requires at least: 4.0
 * WC tested up to: 6.7
 */

defined( 'ABSPATH' ) || exit;

final class YousaiditToolkit {

	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Plugin name slug
	 *
	 * @var string
	 */
	private $plugin_name = 'yousaidit-toolkit';

	/**
	 * Plugin version
	 *
	 * @var string
	 */
	private $version = '2024.1.4';

	/**
	 * Minimum PHP version required
	 *
	 * @var string
	 */
	private $min_php = '7.2';

	/**
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();

			// define constants
			self::$instance->define_constants();

			// Register autoloader
			self::$instance->register_autoloader();

			// Check if PHP version is supported for our plugin
			if ( ! self::$instance->is_supported_php() ) {
				register_activation_hook( __FILE__, [ self::$instance, 'auto_deactivate' ] );
				add_action( 'admin_notices', [ self::$instance, 'php_version_notice' ] );

				return self::$instance;
			}

			if ( ! self::$instance->is_dependencies_resolved() ) {
				add_action( 'admin_notices', [ self::$instance, 'plugin_dependencies_notice' ] );

				return self::$instance;
			}

			// bootstrap main class
			self::$instance->bootstrap_plugin();

			// Register plugin activation activity
			register_activation_hook( __FILE__, [ self::$instance, 'activation' ] );
			register_deactivation_hook( __FILE__, [ self::$instance, 'deactivation' ] );
		}

		return self::$instance;
	}

	/**
	 * Define plugin constants
	 */
	private function define_constants() {
		define( 'YOUSAIDIT_TOOLKIT', $this->plugin_name );
		define( 'YOUSAIDIT_TOOLKIT_VERSION', $this->version );
		define( 'YOUSAIDIT_TOOLKIT_FILE', __FILE__ );
		define( 'YOUSAIDIT_TOOLKIT_PATH', dirname( YOUSAIDIT_TOOLKIT_FILE ) );
		define( 'YOUSAIDIT_TOOLKIT_INCLUDES', YOUSAIDIT_TOOLKIT_PATH . '/includes' );
		define( 'YOUSAIDIT_TOOLKIT_MODULES', YOUSAIDIT_TOOLKIT_PATH . '/modules' );
		define( 'YOUSAIDIT_TOOLKIT_URL', plugins_url( '', YOUSAIDIT_TOOLKIT_FILE ) );
		define( 'YOUSAIDIT_TOOLKIT_ASSETS', YOUSAIDIT_TOOLKIT_URL . '/assets' );
	}

	/**
	 * Load plugin classes
	 */
	private function register_autoloader() {
		if ( file_exists( YOUSAIDIT_TOOLKIT_PATH . '/vendor/autoload.php' ) ) {
			include YOUSAIDIT_TOOLKIT_PATH . '/vendor/autoload.php';
		} else {
			include_once YOUSAIDIT_TOOLKIT_INCLUDES . '/Autoloader.php';

			// instantiate the loader
			$loader = new YouSaidItCards\Autoloader;

			// register the base directories for the namespace prefix
			$loader->add_namespace( 'YouSaidItCards', YOUSAIDIT_TOOLKIT_INCLUDES );
			$loader->add_namespace( 'YouSaidItCards\Modules', YOUSAIDIT_TOOLKIT_MODULES );

			// register the autoloader
			$loader->register();
		}
	}

	/**
	 * Instantiate the required classes
	 *
	 * @return void
	 */
	public function bootstrap_plugin() {
		YouSaidItCards\Plugin::init();
	}

	/**
	 * Run on plugin activation
	 */
	public function activation() {
		do_action( 'yousaidit_toolkit/activation' );
	}

	/**
	 * Run on plugin deactivation
	 */
	public function deactivation() {
		do_action( 'yousaidit_toolkit/deactivation' );
	}

	/**
	 * Show notice about PHP version
	 *
	 * @return void
	 */
	public function php_version_notice() {
		if ( $this->is_supported_php() || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$error = __( 'Your installed PHP Version is: ', 'yousaidit-toolkit' ) . PHP_VERSION . '. ';
		$error .= sprintf(
			__( 'The %s plugin requires PHP version %s or greater.', 'yousaidit-toolkit' ),
			'Yousaidit Toolkit', $this->min_php
		);
		?>
		<div class="error">
			<p><?php printf( $error ); ?></p>
		</div>
		<?php
	}

	/**
	 * Bail out if the php version is lower than
	 *
	 * @return void
	 */
	public function auto_deactivate() {
		if ( $this->is_supported_php() ) {
			return;
		}
		deactivate_plugins( plugin_basename( YOUSAIDIT_TOOLKIT_FILE ) );
		$error = '<h1>' . __( 'An Error Occurred', 'yousaidit-toolkit' ) . '</h1>';
		$error .= '<h2>' . __( 'Your installed PHP Version is: ', 'yousaidit-toolkit' ) . PHP_VERSION . '</h2>';
		$error .= '<p>' . sprintf(
				__( 'The %s requires PHP version %s or greater', 'yousaidit-toolkit' ),
				'Yousaidit Toolkit', $this->min_php
			) . '</p>';
		$error .= '<p>' . sprintf(
				__( 'The version of your PHP is %s unsupported and old %s. ', 'yousaidit-toolkit' ),
				'<a href="https://php.net/supported-versions.php" target="_blank"><strong>',
				'</strong></a>'
			);
		$error .= __( 'You should update your PHP software or contact your host regarding this matter.',
				'yousaidit-toolkit' ) . '</p>';
		wp_die( $error, __( 'Plugin Activation Error', 'yousaidit-toolkit' ), array( 'back_link' => true ) );
	}

	/**
	 * Check if the PHP version is supported
	 *
	 * @return bool
	 */
	private function is_supported_php(): bool {
		return version_compare( PHP_VERSION, $this->min_php, '>=' );
	}

	/**
	 * Get plugin dependencies
	 *
	 * @return array
	 */
	public function get_plugin_dependencies(): array {
		$dependencies = [
			[ 'name' => 'WooCommerce', 'file' => 'woocommerce/woocommerce.php' ]
		];

		return apply_filters( 'yousaidit_toolkit/dependencies', $dependencies );
	}

	/**
	 * Show plugin dependencies notice
	 */
	public function plugin_dependencies_notice() {
		$active_plugins = (array) get_option( 'active_plugins', [] );
		$missing        = [];
		foreach ( $this->get_plugin_dependencies() as $dependency ) {
			if ( ! in_array( $dependency['file'], $active_plugins ) ) {
				$missing[] = $dependency['name'];
			}
		}
		$error = '<strong>' . __( 'Yousaidit Toolkit', 'yousaidit-toolkit' ) . '</strong><br>';
		$error .= __( 'The following required plugins are currently inactive or not installed: ', 'yousaidit-toolkit' );
		$error .= '<strong>' . implode( '</strong>, <strong>', $missing ) . '</strong>';
		?>
		<div class="error">
			<p><?php printf( $error ); ?></p>
		</div>
		<?php
	}

	/**
	 * Check if plugin dependencies resolved
	 *
	 * @return bool
	 */
	private function is_dependencies_resolved(): bool {
		$active_plugins = (array) get_option( 'active_plugins', [] );
		foreach ( $this->get_plugin_dependencies() as $dependency ) {
			if ( ! in_array( $dependency['file'], $active_plugins ) ) {
				return false;
			}
		}

		return true;
	}
}

/**
 * Begins execution of the plugin.
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 */
function yousaidit_toolkit() {
	return YousaiditToolkit::init();
}

yousaidit_toolkit();

if ( file_exists( dirname( __FILE__ ) . '/local-development.php' ) ) {
	include_once dirname( __FILE__ ) . '/local-development.php';
}
