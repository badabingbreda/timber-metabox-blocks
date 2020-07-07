<?php
/**
 * Timber Meta Box Blocks
 *
 * @package     Timber Meta Box Blocks
 * @author      Badabingbreda
 * @license     GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: Timber Meta Box Blocks
 * Plugin URI:  https://www.badabing.nl
 * Description: This plugin will search your Timber views directories for a 'blocks' subdirectory, and register each twig as a Meta Box Gutenberg Block.
 * Version:     1.0.0
 * Author:      Badabingbreda
 * Author URI:  https://www.badabing.nl
 * Text Domain: timber-metabox-blocks
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

define( 'TIMBERMETABOXBLOCKS_VERSION' 	, '1.0.0' );
define( 'TIMBERMETABOXBLOCKS_DIR'			, plugin_dir_path( __FILE__ ) );
define( 'TIMBERMETABOXBLOCKS_FILE'		, __FILE__ );
define( 'TIMBERMETABOXBLOCKS_URL' 		, plugins_url( '/', __FILE__ ) );

require_once "src/TimberBlocks.php";