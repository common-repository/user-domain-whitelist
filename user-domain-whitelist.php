<?php
/*
Plugin Name: User Domain Whitelist/Backlist
Plugin URI:
Version: v1.5.1
Author: Warren Harrison
Text Domain: user-domain-whitelist
Domain Path: /languages
Description: Limit user registration to a defined whitelist of domains or block user registrations from a defined blacklist of domains

*/

if( !class_exists( 'HMUserDomainWhitelist' ) ){

  class HMUserDomainWhitelist{

    var $isEnabledOptions = array( 'Yes', 'No' );
    var $enabled;
    var $adminOptionsName = "HMUserDomainWhitelistOptions";

    function HMUserDomainWhitelist(){

    }

    function getAdminOptions() {
      $adminOptions = array(
        'mode' => 'white',
        'domain_whitelist' => '',
        'domain_blacklist' => '',
        'bad_domain_message' => __('The email address entered is not within an allowed domain.', 'user-domain-whitelist')
      );
      $pluginOptions = get_option( $this->adminOptionsName );
      if( !empty( $pluginOptions ) ){
        foreach( $pluginOptions as $key => $value ){
          $adminOptions[$key] = $value;
        }
      }
      update_option( $this->adminOptionsName, $adminOptions );
      return $adminOptions;
    }

    function init() {
      $plugin_dir = basename(dirname(__FILE__));
      load_plugin_textdomain( 'user-domain-whitelist', false, basename( dirname( __FILE__ ) ) . '/languages' );
      $this->getAdminOptions();
    }

    function displayAdminPage(){
      $pluginOptions = $this->getAdminOptions();
      if( isset( $_POST['update_HMUserDomainWhitelist'] ) ){

        if (!isset($_POST['HMUserDomainWhitelist_update_setting'])) die("<p>I don't think so.</p>");
        if (!wp_verify_nonce($_POST['HMUserDomainWhitelist_update_setting'],'update_settings')) die("<p>I don't think so.</p>");

        if( isset( $_POST['mode'] ) ){
          $pluginOptions['mode'] = $_POST['mode'];
        }
        if( isset( $_POST['domain_whitelist'] ) ){
          $pluginOptions['domain_whitelist'] = $_POST['domain_whitelist'];
        }
        if( isset( $_POST['domain_blacklist'] ) ){
          $pluginOptions['domain_blacklist'] = $_POST['domain_blacklist'];
        }
        if( isset( $_POST['bad_domain_message'] ) ){
          $pluginOptions['bad_domain_message'] = $_POST['bad_domain_message'];
        }
        update_option( $this->adminOptionsName, $pluginOptions );
        ?>
        <div class="updated"><p><strong><?php _e("Settings Updated.", "HMUserDomainWhitelist", 'user-domain-whitelist');?></strong></p></div>
        <?php
      }
      ?>
      <style>
      textarea{
        width: 40em;
      }
      .domain-list{
        height: 10em;
      }
      </style>
      <div class="wrap">
      <form method="post" action="<?php echo $_SERVER['REQUEST_URI'] ?>" >
        <h2><?php _e('User Domain Whitelist/Blacklist', 'user-domain-whitelist'); ?></h2>
        <p><?php _e('This plugin limits user registration to only registrants with an email address from the domain white list below <strong>OR</strong> prevents registrants with an email address from the domain black list below from registering. For example, <em>hortense@example.com</em> would only be allowed to register if <em>example.com</em> appeared in the domain white list. Conversely,  <em>hortense@example.com</em> would <strong>not</strong> be allowed to register if <em>example.com</em> appeared in the domain black list. Anyone attempting to register using an email address outside the white list or inside te black list will receive the error message below.', 'user-domain-whitelist'); ?></p>
        <p><?php _e('Obviously you will either use the white list or the black list below, there is no value in placing values in both lists.', 'user-domain-whitelist'); ?></p>
        <p><label><?php _e('Mode', 'user-domain-whitelist'); ?>: </label><br />
          <?php
          $modeStatus = array(
            'white' => '',
            'black' => ''
          );
          $modeStatus[$pluginOptions['mode']] = 'checked="true"';
          ?>
          <input type="radio" name="mode" <?php echo $modeStatus['white']; ?> value="white" /><?php _e('Whitelist', 'user-domain-whitelist'); ?>
          <input type="radio" name="mode" <?php echo $modeStatus['black']; ?> value="black" /><?php _e('Blacklist', 'user-domain-whitelist'); ?>
        <p><label for="enable"><?php _e('Domain whitelist', 'user-domain-whitelist'); ?> (<em><?php _e('one per line', 'user-domain-whitelist'); ?></em>): </label><br />
          <textarea class="domain-list" name="domain_whitelist" id="domain_whitelist"><?php echo $pluginOptions['domain_whitelist']; ?></textarea></p>
        <p><label for="enable"><?php _e('Domains Blacklist', 'user-domain-whitelist'); ?> (<em><?php _e('one per line', 'user-domain-whitelist'); ?></em>): </label><br />
          <textarea class="domain-list" name="domain_blacklist" id="domain_blacklist"><?php echo $pluginOptions['domain_blacklist']; ?></textarea></p>
        <p><label for="enable"><?php _e('Disallowed email domain error message', 'user-domain-whitelist'); ?>: </label><br />
          <textarea name="bad_domain_message" id="bad_domain_message"><?php echo $pluginOptions['bad_domain_message']; ?></textarea></p>
        <div class="submit">
          <input name="HMUserDomainWhitelist_update_setting" type="hidden" value="<?php echo wp_create_nonce('update_settings'); ?>" />
          <input type="submit" name="update_HMUserDomainWhitelist" value="<?php _e('Update Settings', 'HMUserDomainWhitelist', 'user-domain-whitelist') ?>" />
        </div>
      </form>
      </div>

      <?php
    }
    function validateEmailAddress( $login, $email, $errors ){
      // check registrant address against domain whitelist
      $pluginOptions = $this->getAdminOptions();
      $validDomains = explode( "\r\n", $pluginOptions['domain_whitelist'] );
      $invalidDomains = explode( "\r\n", $pluginOptions['domain_blacklist'] );
      if( $pluginOptions['mode'] == 'black' ){
        // use backlist
        $isValidEmailDomain = true;
        foreach( $invalidDomains as $badDomain ){
          if( !empty( $badDomain ) ){
            $domainLength = strlen( $badDomain );
            $emailDomain = strtolower( substr( $email, -($domainLength), $domainLength ) );
            if( $emailDomain == strtolower( $badDomain ) ){
              $isValidEmailDomain = false;
              break;
            }
          }
        }
      }else{
        // use whitelist (default)
        $isValidEmailDomain = false;
        foreach( $validDomains as $domain ){
          if( !empty( $domain ) ){
            $domainLength = strlen( $domain );
            $emailDomain = strtolower( substr( $email, -($domainLength), $domainLength ) );
            if( $emailDomain == strtolower( $domain ) ){
              $isValidEmailDomain = true;
              break;
            }
          }
        }
      }
      // if invalid, return error
      if( $isValidEmailDomain === false ){
        $errors->add('domain_whitelist_error',__( '<strong>' . __('ERROR', 'user-domain-whitelist') . '</strong>: ' . $pluginOptions['bad_domain_message'] ));
      }
    }

  }

} // End HMUserDomainWhitelist class

if ( class_exists( 'HMUserDomainWhitelist' ) ) {
  $hmUserDomainWhitelist = new HMUserDomainWhitelist();
}

if( !function_exists( 'HMUserDomainWhitelist_op' ) ){
  function HMUserDomainWhitelist_op(){
    global $hmUserDomainWhitelist;
    if( !isset( $hmUserDomainWhitelist ) ){
      return;
    }
    if( function_exists( 'add_options_page' ) ){
      add_options_page( 'User Domain Whitelist/Blacklist', 'User Domain Whitelist/Blacklist', 'manage_options', basename( __FILE__ ), array( &$hmUserDomainWhitelist, 'displayAdminPage' ) );
    }
  }
}

function register_hmUDWsettings(){
  register_setting( 'domain_whitelist', 'mode' );
  register_setting( 'domain_whitelist', 'domain_whitelist' );
  register_setting( 'domain_whitelist', 'domain_blacklist' );
  register_setting( 'domain_whitelist', 'bad_domain_message' );
}

function admin_menu_hmUDW(){
  if( is_admin() ){
    if ( current_user_can('level_8') ) {
      add_action('admin_menu', 'HMUserDomainWhitelist_op');
    }
    add_action( 'admin_init', 'register_hmUDWsettings' );
  }
}

// Actions & Filters
if( isset( $hmUserDomainWhitelist ) ){
  // Actions
  add_action('user-domain-whitelist/user-domain-whitelist.php',  array(&$hmUserDomainWhitelist, 'init'));
  add_action('register_post', array( &$hmUserDomainWhitelist, 'validateEmailAddress' ),10,3 );
  add_action('plugins_loaded', 'admin_menu_hmUDW' );
  // Filters

}


?>
