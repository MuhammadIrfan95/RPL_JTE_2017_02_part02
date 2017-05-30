<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'newspape_wp746');

/** MySQL database username */
define('DB_USER', 'newspape_wp746');

/** MySQL database password */
define('DB_PASSWORD', '98pHb1)4@S');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'yt0jh6hkbhq59hjbxma022dos4qmjkyrbdk4t9m7sxkgkh2uxhncc0jrbq9azs49');
define('SECURE_AUTH_KEY',  'gwb11hodssuew0jnmjc2ehao9bm6xzokqmuoyr60eedpugwbxhb2wykcramvz24r');
define('LOGGED_IN_KEY',    'uqwmqw2cabalzo3tezqib6ugw6gm0kqe0fw2sgcwwney81jst4tbcmxiblavzsgq');
define('NONCE_KEY',        '3mhe7l5oefi325bdju18gz3wtdel9pih2emrlaaxljxqiui5acpy6st5igprbsh5');
define('AUTH_SALT',        'gy2bdjtoizyrzh7gxqcv4kyrr614ahriy48xvomlnypctsnplwdsp3rgz2wsuwj4');
define('SECURE_AUTH_SALT', '1nvymkxge7uxsgbfpe7lrldroycmogbvstb9g8rircskixxaltxfx5nzsghmweav');
define('LOGGED_IN_SALT',   'mlvuj204cilollyaeyexuvzve2vbo8zwq5xwzjmgglw1ooj2ngerdvnyoglohdpl');
define('NONCE_SALT',       'vwg3bvu84yjvyd9pwrlrrsstdnbejsudsjbq3utrfooibomsi6nkl8gporrhcdsp');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp4o_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
