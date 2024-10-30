<?php

/**
 * Plugin Name: CPT Single Redirects
 * Text Domain: cpt_single_redirects
 * Description: This plugin allows the redirection of CPT Single Templates to any URL.
 * Plugin URI: https://github.com/cortesfrau/cpt-single-redirects/
 * Version: 1.1.0
 * Author: Lluís Cortès
 * Author URI: https://lluiscortes.com
 * License: GPLv2 or later
 * Domain Path: /languages
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


// Constants
define( 'CPTSR_VERSION', '1.1.0' );
define( 'CPTSR_BASE', __FILE__ );
define( 'CPTSR_PATH', __DIR__ );
define( 'CPTSR_URL', plugins_url( '', CPTSR_BASE ) );
define( 'CPTSR_BASENAME', plugin_basename( __FILE__ ) );


// Plugin Main Class
class CPT_Single_Redirects {

  // Construct
  public function __construct() {

    // Admin menu
    add_action('admin_menu', [$this, 'settings_page'] );

    // Register settings
    add_action( 'admin_init', [$this, 'register_settings'] );

    // Template redirection
    add_action( 'template_redirect', [$this, 'template_redirection'] );

    // Admin Scripts & Styles
    add_action( 'admin_enqueue_scripts', [$this, 'admin_scripts_styles'] );

    // Actions links in plugins admin screen
    add_filter( 'plugin_action_links_' . CPTSR_BASENAME, [$this, 'add_action_links']);

  }


  // Admin Scripts & Styles
  function admin_scripts_styles( $hook ) {

    // Do not load if we are not in the plugin settings page
    if ( !strstr( $hook, 'cpt-single-redirects' ) ) {
      return;
    }

    wp_enqueue_style( 'admin-css', CPTSR_URL . '/css/cptsr-admin.css', array(), CPTSR_VERSION );
  }


  // Admin menu
  public function settings_page() {
    add_submenu_page(
      'options-general.php',
      'CPT Single Redirects',
      'CPT Single Redirects',
      'manage_options',
      'cpt-single-redirects',
      [$this, 'settings_content']
    );
  }


  // Actions links in plugins admin screen
  public function add_action_links( $links ) {
    $links[] = '<a href="' . admin_url( 'options-general.php?page=cpt-single-redirects' ) . '">' . __( 'Settings', 'cpt_single_redirects' ) . '</a>';
    return $links;
  }


  // Register settings
  public function register_settings() {
    register_setting( 'cpt-single-redirects-settings', 'cpt_single_redirects' );
  }


  // Get Settings
  public function get_settings() {
    return get_option( 'cpt_single_redirects' );
  }


  // Get CTP Objects
  public function get_cpt_objects() {

    // Custom post types created by widely used plugins that we want to ignore
    $cpt_to_ignore = [
      'acf-field-group',
      'acf-field',
      'wpcf7_contact_form',
    ];

    // Registered custom post types
    $args = [
      '_builtin' => false,
    ];
    $custom_post_types = get_post_types( $args );

    // CPT Objects
    $cpt_objects = [];
    foreach ( $custom_post_types as $slug ) {
      if ( !in_array( $slug, $cpt_to_ignore ) ) {
        $cpt_objects[] = get_post_type_object($slug);
      }
    }

    return $cpt_objects;
  }


  // Settings page content
  public function settings_content() {

    // Settings Data
    $cpt_single_redirects = $this->get_settings();

    ?>

    <div class="wrap">
      <h1>CPT Single Redirects</h1>
      <p><?php echo __( 'Here you can set up the desired redirection for the single template of registered custom post types. Leave blank if you do not want to set any redirection.', 'cpt_single_redirects' ); ?></p>

      <form method="post" action="options.php">

        <?php settings_fields( 'cpt-single-redirects-settings' ); ?>
        <?php do_settings_sections( 'cpt-single-redirects-settings' ); ?>

        <table id="cpt-single-redirects-table">

          <tr>
            <th><?php echo __( 'Custom Post Type', 'cpt_single_redirects' ); ?></th>
            <th><?php echo __( 'Redirection URL', 'cpt_single_redirects' ); ?></th>
          </tr>

          <?php foreach ( $this->get_cpt_objects() as $cpt ) {

            // Setting variables
            $cpt_label          = $cpt->label;
            $cpt_slug           = $cpt->name;
            $redirection_value  = empty( $cpt_single_redirects[$cpt_slug] ) ? '' : $cpt_single_redirects[$cpt_slug];

            ?>

            <tr>
              <td><label for="<?php echo $cpt_slug . '-redirect'; ?>"><?php echo $cpt_label; ?></label></td>
              <td><input type="url" name="cpt_single_redirects[<?php echo  $cpt_slug; ?>]" id="<?php echo $cpt_slug . '-redirect'; ?>" value="<?php echo $redirection_value; ?>"></td>
            </tr>

          <?php } ?>

        </table>

        <?php submit_button(); ?>

      </form>
    </div>

  <?php }


  // Template redirection
  public function template_redirection() {

    // Settings Data
    $cpt_single_redirects = $this->get_settings();

    // Redirection for each CPT
    foreach ( $cpt_single_redirects as $cpt_slug => $redirection ) {
      if ( is_singular( $cpt_slug ) ) {
        wp_redirect( $redirection, 301 );
      }
    }

    return;
  }

}


// Instantiation
$cpt_single_redirects = new CPT_Single_Redirects();
