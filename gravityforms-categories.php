<?php
/**
 * Plugin Name:       Categories For Gravity Forms
 * Description:       Using Gravity Forms - Categories WordPress Plugin the admin can categorize Gravity Forms.
 * Version:           1.0.0
 * Author:            AppJetty
 * Author URI:        https://www.appjetty.com/
 * License:           GPLv2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       gravityforms-categories
 * Domain Path:       /languages
 *
 * @link              https://www.appjetty.com/
 * @since             1.0.0
 * @package           Categorised_GForms
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Plugin constants
 */
if ( !defined('GFORMS_CATEGORIES_ADDON_VERSION') ) {
    define( 'GFORMS_CATEGORIES_ADDON_VERSION', '1.0.0' );
}
if ( !defined('GFORMS_CATEGORIES_PLUGIN_NAME') ) {
    define( 'GFORMS_CATEGORIES_PLUGIN_NAME', 'gravityforms-categories' );
}
if ( !defined('GFORMS_CATEGORIES_PLUGIN_FILE') ) {
    define( 'GFORMS_CATEGORIES_PLUGIN_FILE', __FILE__ );
}
if ( !defined('GFORMS_CATEGORIES_PLUGIN_DIR') ) {
    define( 'GFORMS_CATEGORIES_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( !defined('GFORMS_CATEGORIES_PLUGIN_DIR_URL') ) {
    define( 'GFORMS_CATEGORIES_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );
}

/**
 * Gravity Forms - Categorised Forms main class
 */
class GF_GForms_Categories_AddOn_Bootstrap {

    /**
     * Holds any blocker error messages stopping plugin running
     *
     * @var array
     *
     * @since 1.0
     */
    private $notices = array();

    /**
     * The plugin's required Gravity Forms version
     *
     * @var string
     *
     * @since 1.0
     */
    public $required_gf_version = '2.5.0';

    /**
     * The plugin's required WordPress version
     *
     * @var string
     *
     * @since 1.0
     */
    public $required_wp_version = '5.3';

    /**
     * The plugin's required PHP version
     *
     * @var string
     *
     * @since 1.0
     */
    public $required_php_version = '7.3';

    /**
     * Load the plugin
     *
     * @since 4.0
     */
    public function init() {
        add_action( 'plugins_loaded', array( $this, 'load' ) );
        add_action( 'gform_loaded', array( $this, 'addon_load' ) );
    }
 
    public function load() {

        $this->check_wordpress_version();
        $this->check_gravity_forms_version();
        $this->check_php_version();

        /* Check if any errors were thrown, enqueue them and exit early */
        if ( count( $this->notices ) > 0 ) {

            if ( class_exists( 'GFForms' ) && GFForms::is_gravity_page() ) {

                ob_start();
                GFCommon::add_error_message( ob_get_clean() );

                return;
            }

            add_action( 'admin_notices', array( $this, 'display_notices' ) );

            return;
        }

        return;
    }
 
    public function addon_load() {
 
        if ( ! method_exists( 'GFForms', 'include_addon_framework' ) ) {
            return;
        }
 
        require_once( 'includes/class-gf-categories-addon.php' );
 
        GFAddOn::register( 'GFormsCategoriesAddOn' );

    }

    /**
     * Check if WordPress version is compatible
     *
     * @return boolean Whether compatible or not
     *
     * @since 1.0
     */
    public function check_wordpress_version() {
        global $wp_version;

        if ( ! version_compare( $wp_version, $this->required_wp_version, '>=' ) ) {
            $this->notices[] = sprintf( esc_html__( 'WordPress version %s is required: upgrade to the latest version.', 'gravityforms-categories' ), $this->required_wp_version );

            return false;
        }

        return true;
    }

    /**
     * Check if the current version of Gravity Forms is compatible with this add-on
     *
     * @return bool
     *
     * @since 1.0
     */
    public function check_gravity_forms_version() {

        if ( ! class_exists( 'GFCommon' ) ) {
            $this->notices[] = sprintf( esc_html__( 'Gravity Forms Version %s or higher is required to use this add-on.', 'gravityforms-categories' ), $this->required_gf_version,  );

            return false;
        }

        if ( ! version_compare( GFCommon::$version, $this->required_gf_version, '>=' ) ) {
            $this->notices[] = sprintf( esc_html__( 'Gravity Forms version %s or higher is required to use this add-on. Please upgrade Gravity Forms to the latest version.', 'gravityforms-categories' ), $this->required_gf_version );

            return false;
        }

        return true;
    }

    /**
     * Check if PHP version is compatible
     *
     * @return boolean Whether compatible or not
     *
     * @since 1.0
     */
    public function check_php_version() {

        if ( ! version_compare( phpversion(), $this->required_php_version, '>=' ) ) {
            $this->notices[] = sprintf( esc_html__( 'You are running an %1$soutdated version of PHP%2$s. Contact your web hosting provider to update.', 'gravityforms-categories' ), '<a href="https://wordpress.org/support/update-php/">', '</a>' );

            return false;
        }

        return true;
    }

    /**
     * Helper function to easily display error messages
     *
     * @return void
     *
     * @since 1.0
     */
    public function display_notices() {
        ?>
        <div class="error">
            <p>
                <strong><?php esc_html_e( 'Gravity Forms - Form Categories Installation Problem', 'gravityforms-categories' ); ?></strong>
            </p>

            <p><?php esc_html_e( 'The minimum requirements for the Gravity Forms - Form Categories plugin have not been met. Please fix the issue(s) below to continue:', 'gravityforms-categories' ); ?></p>
            <ul style="padding-bottom: 0.5em">
                <?php foreach ( $this->notices as $notice ): ?>
                    <li style="padding-left: 20px;list-style: inside"><?php echo wp_kses_post($notice); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
    }
 
}
$gforms_categories = new GF_GForms_Categories_AddOn_Bootstrap();
$gforms_categories->init();
 
function gf_gforms_categories_addon() {
    return GFormsCategoriesAddOn::get_instance();
}
