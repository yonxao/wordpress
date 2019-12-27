<?php
/*
 * Plugin Name: QAPress
 * Plugin URI: https://www.wpcom.cn/plugins/qapress.html
 * Description: WordPress问答功能插件
 * Version: 3.2.1
 * Author: WPCOM
 * Author URI: https://www.wpcom.cn
*/

define( 'QAPress_VERSION', '3.2.1' );
define( 'QAPress_DIR', plugin_dir_path( __FILE__ ) );
define( 'QAPress_URI', plugins_url( '/', __FILE__ ) );

if( !defined('WPCOM_ADMIN_PATH') ) {
    define( 'WPCOM_ADMIN_PATH', is_dir($framework_path = plugin_dir_path( __FILE__ ) . '/admin/') ? $framework_path : plugin_dir_path( __DIR__ ) . '/Themer/admin/' );
    define( 'WPCOM_ADMIN_URI', is_dir($framework_path) ? plugins_url( '/admin/', __FILE__ ) : plugins_url( '/Themer/admin/', __DIR__ ) );
}

$QAPress_info = array(
    'slug' => 'QAPress',
    'name' => 'QAPress',
    'ver' => QAPress_VERSION,
    'title' => '问答',
    'icon' => 'dashicons-editor-help',
    'position' => 30,
    'key' => 'qa_options',
    'plugin_id' => '46b3ade48ebb2b3e',
    'basename' => plugin_basename( __FILE__ )
);

require_once WPCOM_ADMIN_PATH . 'load.php';
$GLOBALS['QAPress'] = new WPCOM_Plugin_Panel($QAPress_info);

require_once QAPress_DIR . 'includes/sql.php';
require_once QAPress_DIR . 'includes/html.php';
require_once QAPress_DIR . 'includes/rewrite.php';
require_once QAPress_DIR . 'includes/ajax.php';
require_once QAPress_DIR . 'includes/functions.php';
require_once QAPress_DIR . 'includes/widgets.php';