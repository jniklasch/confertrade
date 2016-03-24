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
define('DB_NAME', 'confertrade');

/** MySQL database username */
define('DB_USER', 'root');

/** MySQL database password */
define('DB_PASSWORD', 'root');

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
define('AUTH_KEY',         'KtZ/-.+,jzCMU~bj+^xg(N3#Z`ly/[Pv0yK;YKt5RE|-XjH 6KY9L*~3|C?*rP-c');
define('SECURE_AUTH_KEY',  'e{XbgUV&1YjA:PBu7O2y`r?B69+EqqW Rjd|%6:t}IU*.cT_OWMZ?!biYMDQ9!|^');
define('LOGGED_IN_KEY',    'J8CiIl&7!~j_w&A4~`BKL-4igCtI*:Pm0N| 7oS-!^US&+|-T|-(|RSAY!u8+}$,');
define('NONCE_KEY',        '~pI2L2m?H||}_q*_0Gd!{ga&8@6=z+>LAVx_p*6uGA:w=*D#66|(-Y|0#dbAkw~$');
define('AUTH_SALT',        'DRYuhD.|t@]8<q{}eV(n={KNG[HH(sVPxb$DQ&}G|?4tT#k5x}j/B7-fRs44M/Z,');
define('SECURE_AUTH_SALT', '@#aAc|%}U$md&-xa+9=Vg8h0>:-f]l~mW 864d)YK7<^IMD-n)1>P2|E-dEmk+6{');
define('LOGGED_IN_SALT',   'uqQDu[Ngs*%{,>9,s?HjE7f K]wX|D9:KR08ov%lB+Bc]J*)$kFU<WPY SoxBV--');
define('NONCE_SALT',       '(:qG`@{}->cyx?s6G,&1B;.qP Cd3Z%`g^f8i5MGw59 dsJA,m5|yRvvoZSLo9Z>');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'ct_';

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
