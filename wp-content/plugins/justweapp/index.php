<?php
/*
 * Plugin Name: WPCOM小程序控制台
 * Plugin URI: https://www.wpcom.cn
 * Description: 针对WPCOM微信小程序开发的控制台
 * Version: 2.0.1
 * Author: WPCOM
 * Author URI: https://www.wpcom.cn
*/

define( 'WWA_VERSION', '2.0.1' );
define( 'WWA_DIR', plugin_dir_path( __FILE__ ) );
define( 'WWA_URI', plugins_url( '/', __FILE__ ) );

if( !defined('WPCOM_ADMIN_PATH') ) {
    define( 'WPCOM_ADMIN_PATH', is_dir($framework_path = plugin_dir_path( __FILE__ ) . '/admin/') ? $framework_path : plugin_dir_path( __DIR__ ) . '/Themer/admin/' );
    define( 'WPCOM_ADMIN_URI', is_dir($framework_path) ? plugins_url( '/admin/', __FILE__ ) : plugins_url( '/Themer/admin/', __DIR__ ) );
}

$WWA_info = array(
    'slug' => 'justweapp',
    'name' => '小程序控制台',
    'ver' => WWA_VERSION,
    'title' => '小程序',
    'icon' => 'dashicons-wpcom-xcx',
    'position' => 99,
    'key' => 'wwa_options',
    'plugin_id' => '845657f2fb83ddff',
    'basename' => plugin_basename( __FILE__ )
);

require_once WPCOM_ADMIN_PATH . 'load.php';
$GLOBALS['WWA'] = new WPCOM_PLUGIN_PANEL($WWA_info);

require_once WWA_DIR . 'includes/functions.php';
require_once WWA_DIR . 'includes/admin.php';