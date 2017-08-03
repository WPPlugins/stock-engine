<?php
/*
    Plugin Name: Stock Engine
    Plugin URI: http://relevad.com/wp-plugins/
    Description: Create customizable stock data tables that can be placed anywhere on a site using shortcodes.
    Author: Relevad
    Version: 1.0
    Author URI: http://relevad.com/

*/

/*  Copyright 2015 Relevad Corporation (email: stock-engine@relevad.com) 
 
    This program is free software; you can redistribute it and/or modify 
    it under the terms of the GNU General Public License as published by 
    the Free Software Foundation; either version 3 of the License, or 
    (at your option) any later version. 
 
    This program is distributed in the hope that it will be useful, 
    but WITHOUT ANY WARRANTY; without even the implied warranty of 
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the 
    GNU General Public License for more details. 
 
    You should have received a copy of the GNU General Public License 
    along with this program; if not, write to the Free Software 
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA 
*/

$php_bad_version = version_compare( PHP_VERSION, '5.3.0', '<' );
if ($php_bad_version) {

    add_action( 'admin_init', 'se_deactivate' );
    add_action( 'admin_notices', 'se_deactivation_notice' );
      function st_deactivate() {
          deactivate_plugins( plugin_basename( __FILE__ ) );
      }
      function st_deactivation_notice() {
           echo '<div class="error"><p>Sorry, but the <strong>Stock Engine</strong> plugin requires PHP version 5.3.0 or greater to use. Your PHP version is '.PHP_VERSION.'.</p></div>';
           if ( isset( $_GET['activate'] ) )
                unset( $_GET['activate'] );
      }
} else {
    $main_plugin_file = __FILE__;
    require(plugin_dir_path(__FILE__) . 'stock_engine_admin.php');
}
