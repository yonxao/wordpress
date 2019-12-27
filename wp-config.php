<?php
/**
 * WordPress基础配置文件。
 *
 * 这个文件被安装程序用于自动生成wp-config.php配置文件，
 * 您可以不使用网站，您需要手动复制这个文件，
 * 并重命名为“wp-config.php”，然后填入相关信息。
 *
 * 本文件包含以下配置选项：
 *
 * * MySQL设置
 * * 密钥
 * * 数据库表名前缀
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/zh-cn:%E7%BC%96%E8%BE%91_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL 设置 - 具体信息来自您正在使用的主机 ** //
/** WordPress数据库的名称 */
define( 'DB_NAME', 'wordpress_test' );

/** MySQL数据库用户名 */
define( 'DB_USER', 'wordpress' );

/** MySQL数据库密码 */
define( 'DB_PASSWORD', 'wordpress' );

/** MySQL主机 */
define( 'DB_HOST', '39.107.120.119' );

/** 创建数据表时默认的文字编码 */
define( 'DB_CHARSET', 'utf8mb4' );

/** 数据库整理类型。如不确定请勿更改 */
define( 'DB_COLLATE', '' );

/**#@+
 * 身份认证密钥与盐。
 *
 * 修改为任意独一无二的字串！
 * 或者直接访问{@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org密钥生成服务}
 * 任何修改都会导致所有cookies失效，所有用户将必须重新登录。
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '631d0d2f8ddd8955d68b7060256de05b' );
define( 'SECURE_AUTH_KEY',  '5c06f14e640a19fe3553aa640f735da8' );
define( 'LOGGED_IN_KEY',    '68e41c22edbaf5e9d7c61f5b24a1bf6a' );
define( 'NONCE_KEY',        '79322bd2caa800164495dfe09894c992' );
define( 'AUTH_SALT',        '1604c96f1cfdc9d689d2f44cce4358f7' );
define( 'SECURE_AUTH_SALT', '815769c5f8b7806ebb073dd73dab8ff7' );
define( 'LOGGED_IN_SALT',   'b5257e097733ff222e22c00a0055172e' );
define( 'NONCE_SALT',       '18afa7a808c40ca4b7365f1c72a8feb9' );

/**#@-*/

/**
 * WordPress数据表前缀。
 *
 * 如果您有在同一数据库内安装多个WordPress的需求，请为每个WordPress设置
 * 不同的数据表前缀。前缀名只能为数字、字母加下划线。
 */
$table_prefix = 'wp_';

/**
 * 开发者专用：WordPress调试模式。
 *
 * 将这个值改为true，WordPress将显示所有用于开发的提示。
 * 强烈建议插件开发者在开发环境中启用WP_DEBUG。
 *
 * 要获取其他能用于调试的信息，请访问Codex。
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);

/* 好了！请不要再继续编辑。请保存本文件。使用愉快！ */

/** WordPress目录的绝对路径。 */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}

/** 设置WordPress变量和包含文件。 */
require_once( ABSPATH . 'wp-settings.php' );

/* 处理ftp登录提示 */
define("FS_METHOD", "direct");
define("FS_CHMOD_DIR", 0777);
define("FS_CHMOD_FILE", 0777);

// chown -R www-data /wordpress/path/...
