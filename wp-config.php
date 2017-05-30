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
define('DB_NAME', 'newspape_wp704');

/** MySQL database username */
define('DB_USER', 'newspape_wp704');

/** MySQL database password */
define('DB_PASSWORD', '267piGSp)@');

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
define('AUTH_KEY',         'rqir9qxcxsfaomqmf4bzs9a1eq1klj9bwtc54g9dtfbyzsnhesgvxb14snoj6tkc');
define('SECURE_AUTH_KEY',  '1ooftnb9lp5qmc82hvbjkzx4vvjujo4ie4sdc6gvyefseynmum02emhv01fgmixq');
define('LOGGED_IN_KEY',    't4zgvg4sw4ykbieov5hoyj90vvra8q8deeinbsf8ysbgo59xrjk8vjfnc9rgupqy');
define('NONCE_KEY',        'cbb1ftwh6sympcuvzvbdlytq4vgbsjhz5f8dep8keadml9gbvfynq6gorf0lg7s1');
define('AUTH_SALT',        'z3iflkjmqokugoflb2gqlx2jhqdvhav9sdj1lie572xz5yotkozekczdyexpekmj');
define('SECURE_AUTH_SALT', 'mrsn8kvx157hed4wfak5svb78eyuwlbojkyh8z1yboxjtmljuslebfobgvn5ej5k');
define('LOGGED_IN_SALT',   '4mt7sznr6hgbpxiaqhh7t10i3kmuqj08qhvx3jghikcenwpn8tmgaulrw2u0zugu');
define('NONCE_SALT',       'jpq1brcwq6tppfgjpjrgxsvwu6ajazogckwfs3gzvud7oe6ckj5o3wdsctueqthq');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wpo8_';

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
