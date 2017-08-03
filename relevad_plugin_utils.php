<?php
namespace stockEngine;

//helper function for all min/max integers
function relevad_plugin_validate_integer($new_val, $min_val, $max_val, $default) {
   if (!is_numeric($new_val)) { return $default; }

   return min(max((integer)$new_val, $min_val), $max_val);
}

function relevad_plugin_validate_font_family($new_val, $default) {
   // FOR FUTURE: add in valid font settings: arial, times, etc
   if (empty($new_val))      { return $default; }
   if (is_numeric($new_val)) { return $default; } //throw it out if its a number
   return $new_val; 
}

//for all color settings
function relevad_plugin_validate_color($new_val, $default) {
   $valid_color_strings = explode(' ', 'Transparent Aliceblue Antiquewhite Aqua Aquamarine Azure Beige Bisque Black Blanchedalmond Blue Blueviolet Brown Burlywood Cadetblue Chartreuse Chocolate Coral Cornflowerblue Cornsilk Crimson Cyan Darkblue Darkcyan Darkgoldenrod Darkgray Darkgreen Darkkhaki Darkmagenta Darkolivegreen Darkorange Darkorchid Darkred Darksalmon Darkseagreen Darkslateblue Darkslategray Darkturquoise Darkviolet Deeppink Deepskyblue Dimgray Dodgerblue Firebrick Floralwhite Forestgreen Fuchsia Gainsboro Ghostwhite Gold Goldenrod Gray Green Greenyellow Honeydew Hotpink Indianred Indigo Ivory Khaki Lavender Lavenderblush Lawngreen Lemonchiffon Lightblue Lightcoral Lightcyan Lightgoldenrodyellow Lightgreen Lightgrey Lightpink Lightsalmon Lightseagreen Lightskyblue Lightslategray Lightsteelblue Lightyellow Lime Limegreen Linen Magenta Maroon Mediumauqamarine Mediumblue Mediumorchid Mediumpurple Mediumseagreen Mediumslateblue Mediumspringgreen Mediumturquoise Mediumvioletred Midnightblue Mintcream Mistyrose Moccasin Navajowhite Navy Oldlace Olive Olivedrab Orange Orangered Orchid Palegoldenrod Palegreen Paleturquoise Palevioletred Papayawhip Peachpuff Peru Pink Plum Powderblue Purple Red Rosybrown Royalblue Saddlebrown Salmon Sandybrown Seagreen Seashell Sienna Silver Skyblue Slateblue Slategray Snow Springgreen Steelblue Tan Teal Thistle Tomato Turquoise Violet Wheat White Whitesmoke Yellow YellowGreen');
   // FOR FUTURE: Add in ability to handle rgb(255,0,0)  and rgba(255,0,0,0.3)  hsl(120,100%,50%)  hsla(120,100%,50%,0.3) ??
   if (substr($new_val, 0, 1) == '#') { //if its in hex format
       if (!ctype_xdigit(substr($new_val, 1))) { return $default; }
       $tmp = strlen($new_val);
       if ($tmp < 4 || $tmp > 7 )              { return $default; } //#ff99bb or #f9b are both valid and mean the same thing
       
       return strtoupper($new_val);
   }
    
    $new_val = ucwords($new_val); //make the first letter uppercase before comparison
    if (!in_array($new_val, $valid_color_strings)) {
        return $default;
    }

    return $new_val;
}

function relevad_plugin_validate_opacity($new_val, $default) {
   //expected float value
   if (!is_numeric($new_val)) { return $default; }

   return min(max((float)$new_val, 0), 1);
}



function relevad_plugin_add_menu_section() {
    if (!defined('RELEVAD_PLUGIN_MENU') ) {
        add_object_page(   'Relevad Plugins', 'Relevad Plugins', 'manage_options', 'relevad_plugins', NS.'relevad_plugin_welcome_screen' ); //this function is just a welcome screen thingy
        //add_object_page( $page_title,        $menu_title,          $capability,       $menu_slug,        $function,                      $icon_url )
        
        //Dummy so that we don't get an automatic submenu option of the above
        add_submenu_page('relevad_plugins', 'Relevad Plugins', 'Welcome', 'manage_options', 'relevad_plugins', NS.'relevad_plugin_welcome_screen' );
        
        define('RELEVAD_PLUGIN_MENU', true, false); //flag for whether this menu has already been added
    }
}

function relevad_plugin_welcome_screen() {
    global $relevad_plugins;
    if (empty($relevad_plugins)) //depricated code
        $relevad_plugins = array(); 
    
    $welcomepage_js = file_get_contents("http://relevad.com/wp-plugins/welcomepage.js");
    
    $output = "";
    $all_names = array(); //for backwards compatibility
    foreach ($relevad_plugins as $plugin) {
        $url  = $plugin['url'];
        $name = $plugin['name'];
        $output .= "<li><a href='{$url}'>{$name}</a></li>";
        $all_names[] = $name; //used for javascript to show plugins that AREN'T yet installed
    }
    
    //Depricated code, leaving for backwards compat (incase 1 plugin updated but the others are not)
    $active_plugins = get_option('active_plugins');
    if ( !in_array('Custom Stock Ticker', $all_names) && in_array('custom-stock-ticker/stock_ticker_admin.php', $active_plugins)) {
        $output .= "<li><a href='".admin_url("admin.php?page=stock_ticker_list")."'>Custom Stock Ticker</a></li>";
        $all_names[] = 'Custom Stock Ticker';
    }
    if ( !in_array('Custom Stock Widget', $all_names) && in_array('custom-stock-widget/stock_widget_admin.php', $active_plugins)) {
        $output .= "<li><a href='".admin_url("admin.php?page=stock_widget_list")."'>Custom Stock Widget</a></li>";
        $all_names[] = 'Custom Stock Widget';
    }
    if ( !in_array('Fit My Sidebar', $all_names) && in_array('fit-my-sidebar/fit-my-sidebar.php', $active_plugins)) {
        $output .= "<li><a href='".admin_url("admin.php?page=fms_admin_config")."'>Fit My Sidebar</a></li>";
        $all_names[] = 'Fit My Sidebar';
    }
    
    $temp = implode(',', $all_names);
    echo <<<HEREDOC
<div id="sp-options-page">

    <style type="text/css">
        #relevad-plugins-list li {width:25%; min-width:150px; max-width:200px; text-align:center; border:1px solid black; min-height:160px; padding:10px; margin:10px; background-color:white; display:inline-block; vertical-align:top;}
        #relevad-plugins-list li:hover {background-color:#f9f9f9;}
        #relevad-plugins-list li a {text-decoration:none;}
        #relevad-plugins-list li a h3:hover {text-decoration:underline;}
        #relevad-plugins-active-list li, #relevad-plugins-follow li {margin-left:10px;}
        #relevad-plugins-follow a img {margin-top:5px}
    </style>

    <h1>Relevad Plugins</h1>
    
    <h2>Currently Active</h2>
    <ul id="relevad-plugins-active-list">
    {$output}
    </ul>
    
    <script type="text/javascript">
        var skip = "{$temp}";
    </script>
    <div id="other_rele_plugins" style="display:none;">
        <h2>Other Relevad Plugins</h2>
        <ul id="relevad-plugins-list"></ul>
    </div><!-- relevad-plugin-list generated here -->
    <script type="text/javascript">
    {$welcomepage_js}
    </script>
    
    <br>
    <h2>Follow Us</h2>
    <ul id="relevad-plugins-follow">
        <li><a href="https://www.facebook.com/relevad"><img alt="Relevad on Facebook" src="http://relevad.com/img/social/fb20.png"></a> &nbsp; 
            <a href="https://plus.google.com/+Relevad-Corp"><img alt="Relevad on Google Plus" src="http://relevad.com/img/social/gp20.png"></a> &nbsp; 
            <a href="https://twitter.com/relevad"><img alt="Relevad on Twitter" src="http://relevad.com/img/social/t20.png"></a> &nbsp; 
        </li>
        <li><a href="http://relevad.com/wp-plugins/">Relevad Plugins</a></li>
        <li><a href="https://profiles.wordpress.org/Relevad/#content-plugins">Relevad on Wordpress.org</a></li>
    </ul>
    
</div><!-- end options page -->
HEREDOC;
}

?>
