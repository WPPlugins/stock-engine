<?php
namespace stockEngine;
define(__NAMESPACE__ . '\NS', __NAMESPACE__ . '\\');


global $wpdb;

global $list_table; //needs to be created inside stock_engine_add_screen_options, but utilized within stock_engine_list_page

global $relevad_plugins;

if (!is_array($relevad_plugins)) {
    $relevad_plugins = array();
}
$relevad_plugins[] = array(
'url'  => admin_url('admin.php?page=stock_engine_list'),
'name' => 'Stock Engine'
);

//NOTE: These will automatically be within the namespace
define(NS.'SP_TABLE_NAME', $wpdb->prefix . 'stock_engines');
define(NS.'SP_CHARSET',    $wpdb->get_charset_collate()); //requires WP v3.5

define(NS.'SP_CURRENT_VERSION', 1);   //this should increment by 1 for every database change
define(NS.'SP_TYPE', 'engine');
define(NS.'DATA_ITEM_COUNT', 11);
define(NS.'SP_VALIDATION_PARAMS', <<< DEFINE
{
"max_display":   [1,   200],
"width":         [100, 5000],
"height":        [50, 10000],
"font_size":     [5,   36]
}
DEFINE
);  //access with (array)json_decode(SP_VALIDATION_PARAMS);


include plugin_dir_path(__FILE__) . 'stock_plugin_utils.php'; //used to contain validation functions
include plugin_dir_path(__FILE__) . 'relevad_plugin_utils.php';
include plugin_dir_path(__FILE__) . 'stock_plugin_cache.php';
include plugin_dir_path(__FILE__) . 'stock_engine_display.php';

function dbug_print($var) {
    echo "<pre>";
    print_r($var);
    echo "</pre>\n";
}

function stock_engine_create_db_table() {  //NOTE: for brevity into a function
    $table_name = SP_TABLE_NAME;
    $charset    = SP_CHARSET;
    static $run_once = true; //on first run = true
    if ($run_once === false) return;
    
    //NOTE: later may want: 'default_market'    => 'DOW',   'display_options_strings' 
    $sql = "CREATE TABLE {$table_name} (
    id                      mediumint(9)                        NOT NULL AUTO_INCREMENT,
    name                    varchar(50)  DEFAULT ''             NOT NULL,
    layout                  tinyint(1)   DEFAULT 2              NOT NULL,
    width                   smallint(4)  DEFAULT 400            NOT NULL,
    height                  smallint(4)  DEFAULT 200            NOT NULL,
    display_number          smallint(3)  DEFAULT 5              NOT NULL,
    font_size               tinyint(3)   DEFAULT 12             NOT NULL,
    font_family             varchar(20)  DEFAULT 'Times'        NOT NULL,
    text_color              varchar(7)   DEFAULT '#000000'      NOT NULL,
    auto_text_color         tinyint(1)   DEFAULT 1              NOT NULL,
    bg_color1               varchar(7)   DEFAULT '#FFFFFF'      NOT NULL,
    bg_color2               varchar(7)   DEFAULT '#F4F4F4'      NOT NULL,
    auto_background_color   tinyint(1)   DEFAULT 0              NOT NULL,
    bg_color3               varchar(7)   DEFAULT '#EEEEEE'      NOT NULL,
    feature_list            smallint(3)  DEFAULT 26             NOT NULL,
    active_data_items       text                                NOT NULL,
    advanced_style          text                                NOT NULL,
    stock_page_url          text                                NOT NULL,
    stock_list              text                                NOT NULL,
    UNIQUE KEY name (name),
    PRIMARY KEY (id)
    ) {$charset};";
    //NOTE: Extra spaces for readability screw up dbDelta, so we remove those
    $sql = preg_replace('/ +/', ' ', $sql);
    //NOTE: WE NEED 2 spaces exactly between PRIMARY KEY and its definition.
    $sql = str_replace('PRIMARY KEY', 'PRIMARY KEY ', $sql);
    
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql ); //this will return an array saying what was done, if we want to output it
    $run_once = false;
}

function stock_engine_activate() {
    $current_version = SP_CURRENT_VERSION;
    
    if (!get_option('stock_engine_version')) {
        //if there is no entry for version, assume initial install
        stock_engine_create_db_table();
        $values = array( //NOTE: the rest should all be the defaults
                        'id'             => 1, //explicitly set this or else mysql configs where the default is not 1 will be broken
                        'name'           => 'Default Settings',
                        'active_data_items' => '[["stock_name","1"],["stock_symbol",0],["last_value",0]]',
                        'advanced_style' => 'margin: auto;',
                        'stock_page_url' => 'https://www.google.com/finance?q=__STOCK__',
                        'stock_list'     => "^IXIC\n^GSPC\n^NYA"
                        );
        sp_add_row($values);
        add_option('stock_engine_version',                         $current_version);
        add_option('stock_engine_version_text', "Initial install v{$current_version}");
    }
}
register_activation_hook($main_plugin_file, NS.'stock_engine_activate' ); //does this happen imediately or not?
error_Log(__FILE__);

//NOTE: just installing a plugin, does not make any of its code run, it needs to be activated first.



//*********cleanup and conversion functions for updating versions *********
function stock_engine_handle_update() {
    $current_version = SP_CURRENT_VERSION;
    
    $db_version = get_option('stock_engine_version', '0'); // get version number, if that fails, default of 0, aka no version?

    //NOTE: Don't forget to add each and every version number as a case
    switch($db_version) {
        // case '1.0':
            // stock_engine_create_db_table(); // this needs to be added back in if database entry changes format
        // case '1.1': // not needed until we roll out a new version
            //*****************************************************
            //this will always be right above current_version case
            //keep these 2 updates paired
            // update_option('stock_engine_version',      $current_version);
            // update_option('stock_engine_version_text', " updated from v{$db_version} to");
            //NOTE: takes care of add_option() as well
        case $current_version:
            break;
        default: //this shouldn't be needed
            //future version? downgrading?
            update_option('stock_engine_version_text', " found v{$db_version} current version");
            break;
    }
}

function stock_engine_admin_enqueue($hook) {
    $current_version = SP_CURRENT_VERSION;

    //echo "<!-- testing {$hook} '".strpos($hook, 'stock_engine')."'-->";
    //example: relevad-plugins_page_stock_engine_admin
    if (strpos($hook, 'stock_engine') === false) {return;} //do not run on other admin pages

    wp_register_style ('stock_plugin_admin_style',  plugins_url('stock_plugin_admin_style.css', __FILE__), false,             $current_version);
    wp_register_script('stock_plugin_admin_script', plugins_url('stock_plugin_admin_script.js', __FILE__), array( 'jquery' ), $current_version, false);
    wp_register_script('stock_engine_admin_script', plugins_url('stock_engine_admin_script.js', __FILE__), array( 'jquery', 'stock_plugin_admin_script' ), $current_version, false);

    wp_enqueue_style ('stock_plugin_admin_style');
    wp_enqueue_script('stock_plugin_admin_script');
    wp_enqueue_script('stock_engine_admin_script');
    wp_enqueue_script('jquery-ui-sortable');
    
    stock_engine_scripts_enqueue(true); //we also need these scripts
}
add_action('admin_enqueue_scripts', NS.'stock_engine_admin_enqueue');

function stock_engine_admin_actions() {
    
    relevad_plugin_add_menu_section(); //imported from relevad_plugin_utils.php
    
           //add_submenu_page( 'options-general.php', $page_title, $menu_title,         $capability,     $menu_slug,           $function ); // do not use __FILE__ for menu_slug
    $hook1 = add_submenu_page('relevad_plugins', 'StockEngine',   'StockEngines',      'manage_options', 'stock_engine_list',   NS.'stock_engine_list_page'); 
    $hook2 = add_submenu_page('relevad_plugins', 'New Table',     '&rarr; New Table',  'manage_options', 'stock_engine_addnew', NS.'stock_engine_addnew'); 
    
    add_action( "load-{$hook1}", NS.'stock_engine_add_screen_options' ); 
    //this adds the screen options dropdown along the top
}
add_action('admin_menu', NS.'stock_engine_admin_actions');

function stock_engine_add_screen_options() {
    global $list_table;
    
    $option = 'per_page';
    $args = array(
         'label' => 'Shortcodes',
         'default' => 10,
         'option' => 'shortcodes_per_page'
    );
    add_screen_option( $option, $args );
    
    //placed in this function so that list_table can get the show/hide columns checkboxes automagically
    $list_table = new stock_engine_List_Table(); //uses relative namespace automatically
}

function stock_engine_set_screen_option($status, $option, $value) {
    //https://www.joedolson.com/2013/01/custom-wordpress-screen-options/
    //standard screen options are not filtered in this way
    //if ( 'shortcodes_per_page' == $option ) return $value;
    
    //return $status;
    
    return $value;
}
add_filter('set-screen-option', NS.'stock_engine_set_screen_option', 10, 3);


//ON default settings, should restore to defaults
//ON other shortcodes, should just reload the page
function stock_engine_reset_options() {
    $stock_engine_default_settings = Array(
        //'name'                  => 'Default Settings', //redundant
        'layout'                => 2,
        'width'                 => 400,
        'height'                => 200,
        'display_number'        => 5,
        'font_size'             => 12,
        'font_family'           => 'Times',
        'text_color'            => '#000000', 
        'auto_text_color'       => 1,
        'bg_color1'             => '#FFFFFF',
        'bg_color2'             => '#F4F4F4',
        'bg_color3'             => '#EEEEEE',
        'auto_background_color' => 0,
        'active_data_items'     => '[["stock_name","1"],["stock_symbol",0],["last_value",0]]',
        'feature_list'          => array(1,1,0,1,0),
        //feature list - Table Header, Sortable, Cell Borders, Row Borders, Hover Highlight
        'advanced_style'        => 'margin: auto;',
        'stock_page_url'        => 'https://www.google.com/finance?q=__STOCK__',
        'stock_list'            => "^IXIC\n^GSPC\n^NYA"
    );
    
    sp_update_row($stock_engine_default_settings, array('name' => 'Default Settings'));
    
    stock_plugin_notice_helper("Reset 'Default Settings' to initial install values.");
}


function stock_engine_addnew() { //default name is the untitled_id#
    
    stock_engine_handle_update();
    
    //Add row to DB with defaults
    $values = array( //NOTE: the rest should all be the defaults
                        //'name'            //name auto created by sp_add_row
                        'active_data_items' => '[["stock_name","1"],["stock_symbol",0],["last_value",0]]',
                        'advanced_style' => 'margin: auto;',
                        'stock_page_url' => 'https://www.google.com/finance?q=__STOCK__'
                        );
    $new_id = sp_add_row($values);
    
    if ($new_id !== false) {
        stock_plugin_notice_helper("Added New Table");
        stock_engine_admin_page($new_id);
    }
    else {
        stock_plugin_notice_helper("ERROR: Unable to create new table. <a href='javascript:history.go(-1)'>Go back</a>", 'error');
    }
    
    return;
}

// Default Admin page.
// PAGE for displaying all previously saved engines.
function stock_engine_list_page() {
    global $list_table;
    
    stock_engine_handle_update();

    //This page is referenced from all 3 options: copy, edit, delete and will transfer control to the appropriate function
    $action = (isset($_GET['action'])    ? $_GET['action']    : '');
    $ids    = (isset($_GET['shortcode']) ? $_GET['shortcode'] : false); //form action post does not clear url params

    //action = -1 is from the search query
    if (!empty($action) && $action !== '-1' && !is_array($ids) && !is_numeric($ids)) {
        stock_plugin_notice_helper("ERROR: No shortcode ID for action: {$action}.", 'error');
        $action = ''; //clear the action so we skip to default switch action
    }
    
    switch ($action) {
        case 'copy':
            if (is_array($ids)) $ids = $ids[0];
            $old_id = $ids;
            $ids = sp_clone_row((int)$ids);
            if ($ids === false) {
                stock_plugin_notice_helper("ERROR: Unable to clone shortcode {$old_id}. <a href='javascript:history.go(-1)'>Go back</a>", 'error');
                return;
            }
            stock_plugin_notice_helper("Cloned {$old_id} to {$ids}");
        case 'edit':
            if (is_array($ids)) $ids = $ids[0];
            stock_engine_admin_page((int)$ids);
            break;

        case 'delete': //fall through to display the list as normal
            if (! isset($_GET['shortcode'])) {
                stock_plugin_notice_helper("ERROR: No shortcodes selected for deletion.", 'error');
            }
            else {
                $ids = $_GET['shortcode'];
                if (!is_array($ids)) {
                    $ids = (array)$ids; //make it an array
                }
                sp_delete_rows($ids); //NOTE: no error checking needed, handled inside
            }
        default:
            $current_version = SP_CURRENT_VERSION;
            
            $version_txt = get_option('stock_engine_version_text', '') . " v{$current_version}";
            update_option('stock_engine_version_text', ''); //clear the option after we display it once
        
            $list_table->prepare_items();
            
            //$thescreen = get_current_screen();
            
            echo <<<HEREDOC
            <div id="sp-options-page">
                <h1>Stock Engine</h1><sub>{$version_txt}</sub>
                <p>The Stock Engine plugin allows you to create and run your own custom stock tables.</p>
                <p>To configure a table, click the edit button below that table's name. Or add a new table using the link below.</p>
                <p>To place a table onto your site, copy a shortcode from the list below, or use the default shortcode of <code>[stock-engine]</code>, and paste it into a post, page, or <a href="https://wordpress.org/plugins/shortcode-widget/" ref="external nofollow" target="_blank">Shortcode Widget</a>.<br />
                Alternatively, you can use <code>&lt;?php echo do_shortcode('[stock-engine]'); ?&gt;</code> inside your theme files or a <a href="https://wordpress.org/plugins/php-code-widget/" ref="external nofollow" target="_blank">PHP Code Widget</a>.</p>
            </div>
                <div id='sp-list-table-page' class='wrap'>
HEREDOC;
            echo "<h2>Available Stock Tables <a href='" . esc_url( menu_page_url( 'stock_engine_addnew', false ) ) . "' class='add-new-h2'>" . esc_html( 'Add New' ) . "</a>";

            if ( ! empty( $_REQUEST['s'] ) ) {
                echo sprintf( '<span class="subtitle">Search results for &#8220;%s&#8221;</span>', esc_html( $_REQUEST['s'] ) );
            }
            echo "</h2>";
          
            echo "<form method='get' action=''>"; //this arrangement of display within the form, is copied from contactform7
                echo "<input type='hidden' name='page' value='" . esc_attr( $_REQUEST['page'] ) . "' />";
                $list_table->search_box( 'Search Stock Tables', 'stock-engine' ); 
                $list_table->display();  //this actually renders the table itself
            echo "</form></div>";
            
            break;
    }
}


/** Used for edit engines. & after copy/add **/
function stock_engine_admin_page($id = '') {
    if (array_key_exists('dbug', $_GET)) {
        if ($_GET['dbug'] == 'reset') {
            global $wpdb;
            $wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->prefix . "stock_engines" );
            delete_option('stock_engine_version');
            delete_option('stock_engine_version_text');
            stock_engine_activate();
        }
    }

    if ($id === '') {
        stock_plugin_notice_helper("ERROR: No shortcode ID found", 'error'); return; //This should never happen
    }
    
    $ds_flag = false; //flag used for handling specifics of default settings
    if ($id === 1) {
        $ds_flag = true;
    }

    if (isset($_POST['save_changes'])) {
        stock_engine_update_options($id); //pass in the unchanged settings
        stock_plugin_notice_helper("Changes saved");
    } 
    elseif (isset($_POST['reset_options'])) { //just reload the page if from non Default Settings
        if ($ds_flag)
            stock_engine_reset_options();
        else
            stock_plugin_notice_helper("Reverted all changes");
    }
    
    $shortcode_settings = sp_get_row($id, 'id'); //NOTE: have to retrieve AFTER update
    if ($shortcode_settings === null) {
        stock_plugin_notice_helper("ERROR: No shortcode ID '{$id}' exists. <a href='javascript:history.go(-1)'>Go back</a>", 'error');
        return;
    }
    
    if (array_key_exists('dbug', $_GET)) {
        if ($_GET['dbug'] == 'data') dbug_print($shortcode_settings);
    }

    $the_action = '';
    if (!isset($_GET['action']) || $_GET['action'] != 'edit') {
        $the_action = '?page=stock_engine_list&action=edit&shortcode=' . $id; //for turning copy -> edit
    }
    
    $reset_btn    = "Revert Changes";
    $reset_notice = "<a class='submitdelete' href='?page=stock_engine_list&action=delete&shortcode={$id}' onclick='return showNotice.warn()'>Delete Permanently</a>";
    if ($ds_flag) {
        $reset_btn    = "Reset to Defaults";
        $reset_notice = "<sup>*</sup><br /><sup>* NOTE: 'Reset to Defaults' also clears all default stock lists.</sup>";
    }
    
    echo <<<HEREDOC
<div id="sp-options-page">
    <h1>Edit Stock Engine</h1>
    <p>Choose your stocks and display settings below.</p>
    <form action='{$the_action}' method='POST'>
HEREDOC;
    
    echo "<div id='sp-form-div' class='postbox-container sp-options'>
            <div id='normal-sortables' class='meta-box-sortables'>
                <div id='referrers' class='postbox'>";
    if (!$ds_flag) {
        echo      "<div class='inside'>";
        stock_engine_create_name_field($shortcode_settings);
    }
    else {
        echo "     <h3>Default Shortcode Settings</h3>
                    <div class='inside'>";
    }
                        stock_engine_create_template_field();
                        
    echo "              <div class='sp-options-subsection'>
                            <h4>Layout & Size</h4>";
                                stock_plugin_cookie_helper(1);
                                stock_engine_create_engine_layout_section($shortcode_settings);
    echo "                  </div>
                        </div>
                        <div class='sp-options-subsection'>
                            <h4>Color & Style</h4>";
                                stock_plugin_cookie_helper(2);
                                stock_engine_create_color_config($shortcode_settings);
    echo "                  </div>
                        </div>
                        <div class='sp-options-subsection'>
                            <h4>Data & Display</h4>";
                                stock_plugin_cookie_helper(3);
                                stock_engine_create_display_options($shortcode_settings);
    echo "                  </div>
                        </div>
                       <div class='sp-options-subsection'>
                            <h4>Advanced Styling</h4>";
                                stock_plugin_cookie_helper(4);
                                stock_engine_create_style_field($shortcode_settings);
    echo "                  </div>
                        </div>
                       <div class='sp-options-subsection'>
                            <h4>URL Link</h4>";
                                stock_plugin_cookie_helper(5);
                                stock_engine_create_url_field($shortcode_settings);
    echo "                  </div>
                </div>
            </div>
                </div><!--end referrers -->
            </div>
            <div id='publishing-actions'>
                <input type='submit' name='save_changes'  value='Save Changes' class='button-primary' />
                <input type='submit' name='reset_options' value='{$reset_btn}' class='button-primary' />
                {$reset_notice}
            </div>
        </div>
    
        <div id='sp-cat-stocks-div' class='postbox-container sp-options'>
                <div id='normal-sortables' class='meta-box-sortables'>
                    <div id='referrers' class='postbox'>
                        <h3><span>Stocks</span></h3>
                        <div class='inside'>
                            <p>Enter each stock symbol on its own line.<br />
                            If a comment is desired, enter it on the same line as the stock symbol.</p>
                            <p></p>
                            <p>For Nasdaq Composite Index, use <code>^IXIC</code>. For S&amp;P500 Index, use <code>^GSPC</code>. Unfortunately, DOW is currently not available.</p>";
                            stock_plugin_create_stock_list_section($shortcode_settings);
    echo "              </div>
                    </div>
                </div>
        </div>";

    $the_name = '';
    if (!$ds_flag) $the_name = " name='{$shortcode_settings['name']}'";
    echo <<<HEREDOC
        </form>
        <div id="sp-preview" class="postbox-container sp-options">
            <div id="normal-sortables" class="meta-box-sortables">
                <div class="postbox">
                    <h3><span>Preview</span></h3>
                    <div class="inside">
                       <p>Based on the last saved settings, this is what the shortcode <code>[stock-engine{$the_name}]</code> will generate:</p>
HEREDOC;

    echo do_shortcode("[stock-engine{$the_name}]");
    echo <<<HEREDOC
                           <p>To preview your latest changes you must first save changes.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="clear"></div>
</div><!-- end options page -->
HEREDOC;
}





    
//NOTE: not moved to the top as a define, because if we have to call json_decode anyways whats the point
function stock_engine_templates() { //helper function to avoid global variables
    return array(
        'Default' => array(
            'name'                  => 'Default (black on white)', 
            'font_family'           => 'Times', 
            'text_color'            => '#000000', 
            'bg_color1'             => '#FFFFFF',
            'bg_color2'             => '#F4F4F4',
            'bg_color3'             => '#EEEEEE',
            'auto_text_color'       => 1,
            'auto_background_color' => 0),
        'Classic' => array(
            'name'                  => 'Classic (white on black)', 
            'font_family'           => 'Times', 
            'text_color'            => '#FFFFFF', 
            'bg_color1'             => '#000000',
            'bg_color2'             => '#111111',
            'bg_color3'             => '#333333',
            'auto_text_color'       => 0,
            'auto_background_color' => 0),
        'Ocean' => array(
            'name'                  => 'Ocean (white on blue)', 
            'font_family'           => 'Arial', 
            'text_color'            => '#FFFFFF', 
            'bg_color1'             => '#000030',
            'bg_color2'             => '#000030',
            'bg_color3'             => '#101050',
            'auto_text_color'       => 0,
            'auto_background_color' => 0),
        'Sky' => array(
            'name'                  => 'Sky (dark blue on light blue)', 
            'font_family'           => 'Arial', 
            'text_color'            => '#000060', 
            'bg_color1'             => '#DDDDFF',
            'bg_color2'             => '#DDDDFF',
            'bg_color3'             => '#FFFFFF',
            'auto_text_color'       => 0,
            'auto_background_color' => 0),
        'Mint' => array(
            'name'                  => 'Mint (Black on green)', 
            'font_family'           => 'cursive', 
            'text_color'            => '#000000', 
            'bg_color1'             => '#EEFFEE',
            'bg_color2'             => '#CCFFCC',
            'bg_color3'             => '#EEFFEE',
            'auto_text_color'       => 0,
            'auto_background_color' => 0),
        'Dynamic' => array(
            'name'                  => 'Dynamic Coloring', 
            'text_color'            => '#000000', 
            'auto_text_color'       => 1,
            'auto_background_color' => 1)
    );
}

function stock_engine_create_template_field() {

    $all_templates = stock_engine_templates();
  
    echo "<label for='input_template'>Template: </label>
          <select id='input_template' name='template' style='width:250px;'>
             <option selected> ------- </option>";

            foreach($all_templates as $key=>$template){
                echo "<option value='{$key}'>{$template['name']}</option>";
            }

    echo "</select>
        <input type='submit' name='save_changes'  value='Apply' class='button-primary' />
        <br/>
        <sup>* NOTE: Not all options are over-written by template</sup>";
}

function stock_engine_update_options($id) {
    
    $unchanged = sp_get_row($id, 'id');
    $validation_params = (array)json_decode(SP_VALIDATION_PARAMS);
    
    $selected_template = $_POST['template'];  //NOTE: if this doesn't exist it'll be NULL
    $all_templates     = stock_engine_templates();
    
    $template_settings = array(); 
    if(array_key_exists($selected_template, $all_templates)) {
        $template_settings = $all_templates[$selected_template];
        unset($template_settings['name']); //throw out the name or we'll end up overwriting this shortcode's name
    }

    $settings_new = array();
    
   
    $new_feature_list = array(
        (array_key_exists('show_header',    $_POST) ? 1 : 0),
        (array_key_exists('sorting_enabled',$_POST) ? 1 : 0),
        (array_key_exists('cell_borders',   $_POST) ? 1 : 0),
        (array_key_exists('row_borders',    $_POST) ? 1 : 0),
        (array_key_exists('hover_highlight',$_POST) ? 1 : 0)
    );
    
    $settings_new['feature_list']           = $new_feature_list;
    $settings_new['auto_text_color']        = (array_key_exists('auto_text_color',       $_POST) ? 1 : 0);
    $settings_new['auto_background_color']  = (array_key_exists('auto_background_color', $_POST) ? 1 : 0);
    
    $settings_new['layout']       = (array_key_exists('layout',      $_POST) ? $_POST['layout']       : 2);
        
    if (array_key_exists('max_display',$_POST)) {
        $tmp = relevad_plugin_validate_integer($_POST['max_display'],  $validation_params['max_display'][0],  $validation_params['max_display'][1],  false);
        if ($tmp) {$settings_new['display_number'] = $tmp;}
    }
    
    if (array_key_exists('width',$_POST))  $settings_new['width']  = relevad_plugin_validate_integer($_POST['width'],  $validation_params['width'][0],  $validation_params['width'][1],  $unchanged['width']);
    if (array_key_exists('height',$_POST)) $settings_new['height'] = relevad_plugin_validate_integer($_POST['height'], $validation_params['height'][0], $validation_params['height'][1], $unchanged['height']);

    $settings_new['font_size']   = relevad_plugin_validate_integer(    $_POST['font_size'],   $validation_params['font_size'][0],  $validation_params['font_size'][1],  $unchanged['font_size']);
    $settings_new['font_family'] = relevad_plugin_validate_font_family($_POST['font_family'], $unchanged['font_family']);

    if (array_key_exists('text_color',$_POST))$settings_new['text_color'] = relevad_plugin_validate_color($_POST['text_color'],        $unchanged['text_color']);
    if (array_key_exists('bg_color1',$_POST)) $settings_new['bg_color1']  = relevad_plugin_validate_color($_POST['bg_color1'], $unchanged['bg_color1']);
    if (array_key_exists('bg_color2',$_POST)) $settings_new['bg_color2']  = relevad_plugin_validate_color($_POST['bg_color2'], $unchanged['bg_color2']);
    if (array_key_exists('bg_color3',$_POST)) $settings_new['bg_color3']  = relevad_plugin_validate_color($_POST['bg_color3'], $unchanged['bg_color3']);
    
    $settings_new['stock_page_url'] = $_POST['stock_page_url'];
    
    $tmp = trim($_POST['engine_advanced_style']); //strip spaces
    if ($tmp != '' && substr($tmp, -1) != ';') { $tmp .= ';'; } //poormans making of a css rule
    $settings_new['advanced_style'] = $tmp;
    
    // In case the user specifies 'height' value that is not enough to fit 'display_number' number of stocks:
    // The stock engine will expand beyond the 'height' parameter to satisfy the 'display_number' parameter,
    // and we will display a notification to the user that this is what is happening.
        
    if (($settings_new['layout'] == 2 || $settings_new['layout'] == 3) && ($settings_new['font_size'] * $settings_new['display_number'] > $settings_new['height'])) { // This error only applies in layouts 2 and 3
        stock_plugin_notice_helper("<b class='sp-notice'>Notice:</b> Height of {$settings_new['height']}px is not enough to display {$settings_new['display_number']} stocks at font size {$settings_new['font_size']}.<br />Stock table height will be expanded.", 'notice notice-warning');
    }
    
    if (!empty($_POST['active_data_items'])) {
        $active_data_items = explode(',',$_POST['active_data_items']);
        foreach ($active_data_items as $key => $value) {                  // merge default sort into active items array for storage
            $active_data_items[$key] = array($value,'');                  // convert each item in the array into a sub-array for later
            if ($value == $_POST['default_sort']) {                       // if the current item is the one referenced in 'default sort'
                $active_data_items[$key][1] = $_POST['default_sort_dir']; // then set the default sort bit to 1 (asc) or -1 (dsc)
            } else {
                $active_data_items[$key][1] = 0;                          // otherwise set the default sort bit to 0
            }
        }
        $settings_new['active_data_items'] = json_encode($active_data_items);
    } else {
        stock_plugin_notice_helper("<b class='sp-warning'>Warning:</b> No items are set to active. Changes not saved.", 'error');
    }
   
    //handle this shortcode's stock list and name if either exist
    if (isset($_POST['stocks_for_shortcode'])) {
        $stocklist = str_replace('\"', '"',
                     str_replace("\'", "'",
                     str_replace('\\\\', '\\', $_POST['stocks_for_shortcode'])));                     
        $settings_new['stock_list'] = stock_plugin_validate_stock_list($stocklist);
    } else {
        echo "POST DOES NOT HAVE STOCKS";
    }
    
    if (isset($_POST['shortcode_name']) && $_POST['shortcode_name'] !== $unchanged['name']) {
        //check if other than - and _  if the name is alphanumerics
        if (! ctype_alnum(str_replace(array(' ', '-', '_'), '', $_POST['shortcode_name'])) ) {
            stock_plugin_notice_helper("<b class='sp-warning'>Warning:</b> Allowed only alphanumerics and - _ in shortcode name.<br/>Name reverted!", 'error');
        }
        elseif (sp_name_used($_POST['shortcode_name'])) {
            stock_plugin_notice_helper("<b class='sp-warning'>Warning:</b> Name '{$_POST['shortcode_name']}' is already in use by another shortcode<br/>Name reverted!", 'error');
        }
        else {
            $settings_new['name'] = $_POST['shortcode_name'];
        }
        //NOTE: 50 chars limit but this will be auto truncated by mysql, and enforced by html already
    }
    
    if (array_key_exists('dbug', $_GET)) {
        if ($_GET['dbug'] == 'post') {
            echo '-------------- $_POST  --------------'; dbug_print($_POST);
            echo '-------------- $settings_new --------------'; dbug_print($settings_new);
        }
    }
    
    //now merge template settings > post changes > old unchanged settings in that order
    $status = sp_update_row(array_replace($unchanged, $settings_new, $template_settings), array('id' => $id));
}

function stock_engine_create_name_field($shortcode_settings) {
    echo "<label for='input_shortcode_name'>Shortcode Name:</label> <sub>(limit 50 chars) (alphanumeric and - and _ only)</sub><br/>
    <input id='input_shortcode_name' name='shortcode_name' type='text' maxlength='50' value='{$shortcode_settings['name']}' class='shortcode_name'/>";
}

function stock_engine_create_engine_layout_section($shortcode_settings) {
    $layout = array (null,0,0,0,0); // first key is null so that index corresponds with layout number
    $layout_disable = array (null,'','','','');
    $layout[$shortcode_settings['layout']]         ='checked';     // For the selected layout's input control, write the word 'checked'
    $layout_disable[$shortcode_settings['layout']] ='disabled';    // For not applicable inputs on the selected layout, write the word 'disabled'
    $desc_2 = "Static will display the table with a set height and a fixed number of stocks. Any stocks beyond this number will not be displayed.";
    $desc_1 = "Expand will always display the full stock list entered. It's height will expand to fit all stocks on the list.";
    $desc_3 = "Pages will display a table with paging controls. Each page will display the desired number of stocks.";
    $desc_4 = "Scroll will add a scrollbar to the right side of the table. It will always display all stocks on the list.";
    $plugin_dir = plugin_dir_url(__FILE__);
    
echo <<<HEREDOC
        <table id="layout_radio_buttons">
            <tbody>
                <tr>
                    <td title="{$desc_2}">
                        <label for="display_static"><img src="{$plugin_dir}images/layout_static.png"></label>
                    </td>
                    <td title="{$desc_1}">
                        <label for="display_expand"><img src="{$plugin_dir}images/layout_expand.png"></label>
                    </td>
                    <td title="{$desc_3}">
                        <label for="display_pages"><img src="{$plugin_dir}images/layout_pages.png"></label>
                    </td>
                    <td title="{$desc_4}">
                        <label for="display_scroll"><img src="{$plugin_dir}images/layout_scroll.png"></label>
                    </td>
                </tr>
                <tr>
                    <td><label title="{$desc_2}" for="display_static">Static</label><br />
                    <input
                        id="display_static"
                        type="radio"
                        name="layout"
                        value="2"
                        title="{$desc_2}"
                         {$layout[2]}
                        onclick='swap_layout(2)'
                    /></td>
                    <td><label title="{$desc_1}" for="display_expand">Expand</label><br />
                    <input
                        id="display_expand"
                        type="radio"
                        name="layout"
                        value="1"
                        title="{$desc_1}"
                        {$layout[1]}
                        onclick='swap_layout(1)'
                    /></td>
                    <td><label title="{$desc_3}" for="display_pages">Pages</label><br />
                    <input
                        id="display_pages"
                        type="radio"
                        name="layout"
                        value="3"
                        title="{$desc_3}"
                         {$layout[3]}
                        onclick='swap_layout(3)'
                    /></td>
                    <td><label title="{$desc_4}" for="display_scroll">Scroll</label><br />
                    <input
                        id="display_scroll"
                        type="radio"
                        name="layout"
                        value="4"
                        title="{$desc_4}"
                         {$layout[4]}
                        onclick='swap_layout(4)'
                    /></td>
                </tr>
            </tbody>
        </table>
        <br />
        <table>
            <tbody>
                <tr>
                    <td><input
                            id="input_width"
                            name="width"
                            type="number"
                            min="100"
                            max="5000"
                            value="{$shortcode_settings['width']}"
                            class="itxt"
                    /></td>
                    <td><label for="input_width">Table Width</label></td>
                </tr>
                <tr>
                    <td><input
                            id="input_height"
                            name="height"
                            type="number"
                            min="50"
                            max="10000"
                            value="{$shortcode_settings['height']}"
                            class="itxt layout_aff layout_1_disable"
                            {$layout_disable[1]}
                    /></td>
                    <td><label for="input_height" class="label_{$layout_disable[1]}">Table Height</label></td>
                </tr>
                <tr>
                    <td><input
                            id="input_max_display"
                            name="max_display"
                            type="number"
                            step="1"
                            min="1"
                            max="200"
                            value="{$shortcode_settings['display_number']}"
                            class="itxt layout_aff layout_1_disable layout_4_disable"
                            {$layout_disable[1]} {$layout_disable[4]}
                    /></td>
                    <td><label for="input_max_display" class="label_{$layout_disable[1]} label_{$layout_disable[4]}">Number of Stocks</label></td>
                </tr>
            </tbody>
        </table>
HEREDOC;
}

function stock_engine_create_color_config($shortcode_settings) {
    $default_fonts  = array("Arial", "cursive", "Gadget", "Georgia", "Impact", "Palatino", "sans-serif", "serif", "Times");  //maybe extract this list into utils
    $auto_text_checked       = checked($shortcode_settings['auto_text_color'], 1, false);
    $auto_background_checked = checked($shortcode_settings['auto_background_color'], 1, false);
echo <<<HEREDOC
    <table>
        <tbody>
            <tr>
                <td><input
                        id="input_font_size"
                        name="font_size"
                        type="number"
                        step="1"
                        min="5"
                        max="36"
                        value="{$shortcode_settings['font_size']}"
                        class="itxt"
                /></td>
                <td><label for="input_font_size">Text Size</label></td>
            </tr>
            <tr>
                <td><input 
                        id="input_font_family"
                        name="font_family"
                        list="font_family"
                        value="{$shortcode_settings['font_family']}"
                        autocomplete="on"
                        style="width:100px;"
                /></td>
                <td><label for="input_font_family">Font Family</label></td>
            </tr>
            <tr>
                <td><input
                        id="input_text_color"
                        name="text_color"
                        type="color"
                        value="{$shortcode_settings['text_color']}"
                        class="itxt color_input"
                        style="width:100px;"
                /></td>
                <td>
                    <label for="input_text_color" class="disable_text">Text Color</label>
                    <sup><a href="http://www.w3schools.com/tags/ref_colorpicker.asp" ref="external nofollow" target="_blank" title="Use hex to pick colors!">[?]</a></sup>
                    <script>input_color_enhance("#input_text_color");</script>
                </td>
            </tr>
            <tr title="Automatic coloring sets the text color to be either red or green depending on how much the price of the stock has changed.">
                <td><input 
                        id='input_text_color_change' 
                        name='auto_text_color' 
                        type='checkbox' 
                        {$auto_text_checked}
                /></td>
                <td><label for="input_text_color_change">Automatic Text Color</label></td>
            </tr>
            <tr>
                <td><input
                        id="input_background_color_odd" 
                        name="bg_color1" 
                        type="color" 
                        value="{$shortcode_settings['bg_color1']}" 
                        class="itxt color_input disable_bg_color_change" 
                        style="width:99px;" 
                /></td>
                <td><label for="input_background_color_odd" class="disable_bg_color_change">Background Color - Odd Rows</label>
                <sup><a href="http://www.w3schools.com/tags/ref_colorpicker.asp" ref="external nofollow" target="_blank" title="Use hex to pick colors!"">[?]</a></sup></td>
                <script>input_color_enhance("#input_background_color_odd");</script>
            </tr>
            <tr>
                <td><input
                        id="input_background_color_even" 
                        name="bg_color2" 
                        type="color" 
                        value="{$shortcode_settings['bg_color2']}" 
                        class="itxt color_input disable_bg_color_change" 
                        style="width:99px;" 
                /></td>
                <td><label for="input_background_color_even" class="disable_bg_color_change">Background Color - Even Rows</label>
                <sup><a href="http://www.w3schools.com/tags/ref_colorpicker.asp" ref="external nofollow" target="_blank" title="Use hex to pick colors!">[?]</a></sup></td>
                <script>input_color_enhance("#input_background_color_even");</script>
            </tr>
            <tr title="Automatic coloring sets the background color to be either red or green depending on how much the price of the stock has changed.">
                <td><input
                        id='input_bg_color_change'
                        name='auto_background_color'
                        type='checkbox'
                        {$auto_background_checked}
                /></td>
                <td><label for="input_bg_color_change">Automatic Background Color</label></td>
            </tr>
            <tr>
                <td><input
                        id="input_header_color" 
                        name="bg_color3" 
                        type="color" 
                        value="{$shortcode_settings['bg_color3']}" 
                        class="itxt color_input disable_header" 
                        style="width:99px;" 
                /></td>
                <td><label for="input_header_color" class="disable_header">Background Color - Header</label>
                <sup><a href="http://www.w3schools.com/tags/ref_colorpicker.asp" ref="external nofollow" target="_blank" title="Use hex to pick colors!">[?]</a></sup></td>
                <script>input_color_enhance("#input_header_color");</script>
            </tr>
        </tbody>
    </table>        
HEREDOC;
    echo "<datalist id='font_family'>";
    foreach($default_fonts as $font){
        echo "<option value='{$font}'></option>";
    }

    echo "</datalist>";

}

function stock_engine_create_display_options($shortcode_settings) {
    $validation_params = (array)json_decode(SP_VALIDATION_PARAMS);
    $feature_list = array();
    $active_data_items = json_decode($shortcode_settings['active_data_items']);
    
    foreach ($shortcode_settings['feature_list'] as $key=>$value) {
        $feature_list[$key] = checked ($value, 1, false);
    }

    $all_data_items = array( // This array will pair the value from $data_order (ex. 'stock_name') with the readable string (ex. 'Stock Name')
        'stock_name'        => 'Stock Name',
        'stock_symbol'      => 'Stock Symbol',
        'last_value'        => 'Last Value',
        'change_value'      => 'Change Value',
        'change_percent'    => 'Change Percent',
        'market_cap'        => 'Market Cap',
        'fifty_week_range'  => 'Fifty Week Range',
        'pe_ratio'          => 'P/E Ratio',
        'earning_per_share' => 'Earning Per Share',
        'revenue'           => 'Revenue',
        'user_text'         => 'Comment'
    );
    $inactive_data_items = $all_data_items; // Start with a full list, we will remove all items in the active set
    
    echo "<div class='clearfix'><ul id='sortable-data-active' class='sortable-data-items-container'><li class='active-items nt-header'>Active Items</li>";
    $ds_list = "";
    
    foreach ($active_data_items as $data) { // Write out active items (order matters)
        $input_name = $data[0];
        $display_name = $all_data_items[$input_name];
        unset($inactive_data_items[$input_name]); // Filter out active items to create inactive item set
        echo "<li id='{$input_name}' class='sortable-data-item'>{$display_name}</li>";
        if ($data[1] != 0) {
            $ds_selected = "selected='selected'";
            $ds_asc = selected( $data[1], "1", false);
            $ds_desc = selected( $data[1], "-1", false);
        } else {
            $ds_selected = "";
        }
        $ds_list .= "<option value={$input_name} {$ds_selected}>{$display_name}</option>";
        
    }

    echo "</ul><ul id='sortable-data-inactive' class='sortable-data-items-container'><li class='inactive-items nt-header'>Inactive Items</li>";
    
    foreach ($inactive_data_items as $input_name => $display_name) { // Write out inactive items (order does not matter)
        echo "<li id='{$input_name}' class='sortable-data-item'>{$display_name}</li>";
    }
    
    echo <<<HEREDOC
    </ul></div>
    <div class="option-default-sort">
        <span>Default Sorting:</span>
        <select id="default-sort" name="default_sort">
            {$ds_list}
        </select>
    
        <select id="default-sort-dir" name="default_sort_dir">
            <option value="1" {$ds_asc}>Asc</option>
            <option value="-1" {$ds_desc}>Desc</option>
        </select>
    </div>
    
    <table>
        <thead class='border-top'>
            <tr>
                <th colspan=2>Feature</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <input
                        id='input_show_header'
                        name='show_header'
                        type='checkbox'
                        {$feature_list[0]}
                    >
                </td>
                <td>
                    <label for='input_show_header'>Table Header</label>
                </td>
            </tr>
            <tr>
                <td>
                    <input
                        title ="Allow the table to be dynamically sorted by clicking on the column headers"
                        id='input_sorting_enabled'
                        name='sorting_enabled'
                        type='checkbox'
                        class='disable_show_header'
                        {$feature_list[1]}
                    >
                </td>
                <td>
                    <label title ="Allow the table to be dynamically sorted by clicking on the column headers" for='input_sorting_enabled' class='disable_show_header'>Columns Sortable</label>
                </td>
            </tr>
            <tr>
                <td>
                    <input
                        id='input_cell_borders'
                        name='cell_borders'
                        type='checkbox'
                        {$feature_list[2]}
                    >
                </td>
                <td>
                    <label for='input_cell_borders'>Cell Borders</label>
                </td>
            </tr>
            <tr>
                <td>
                    <input
                        id='input_row_borders'
                        name='row_borders'
                        type='checkbox'
                        {$feature_list[3]}
                    >
                </td>
                <td>
                    <label for='input_row_borders'>Row Borders</label>
                </td>
            </tr>
            <tr>
                <td>
                    <input
                        title = "A subtle highlight of rows on mouseover"
                        id='input_hover_highlight'
                        name='hover_highlight'
                        type='checkbox'
                        {$feature_list[4]}
                    >
                </td>
                <td>
                    <label title = "A subtle highlight of rows on mouseover" for='input_hover_highlight'>Hover Highlight</label>
                </td>
            </tr>
        </tbody>
    </table>
    <input id="input-active-data-items" type="hidden" name="active_data_items">
HEREDOC;
}



function stock_engine_create_style_field($shortcode_settings) {
    echo "
        <p>
            If you have additional CSS rules you want to apply to the
            entire table (such as alignment or borders) you can add them below.
        </p>
        <p>
            Example: <code>margin:auto; border:1px solid #000000;</code>
        </p>
        <textarea id='input_engine_advanced_style' rows=6 name='engine_advanced_style' class='itxt' style='width:98%; text-align:left;'>{$shortcode_settings['advanced_style']}</textarea>";
}

function stock_engine_create_url_field($shortcode_settings) {
    echo "<p>Url that clicking on a stock will link to.  __STOCK__ will be replaced with the stock symbol.</p>
          <p>Example/Default: https://www.google.com/finance?q=__STOCK__</p>
          <input id='stock_page_url' name='stock_page_url' type='text' value='{$shortcode_settings['stock_page_url']}' class='itxt' style='width:90%; text-align:left;' />";
}

//override the class that is used for all the stock plugins
class stock_engine_List_Table extends \stockEngine\stock_shortcode_List_Table {
    function get_columns() {
        $columns = array( //NOTE: don't need to declare an ID column ID is reserved and expected
            'cb'             => '<input type="checkbox" />',
            'name'           => 'Name',
            'shortcode'      => 'Shortcode',
            'stock_list'     => 'Stock List'
        );
        return $columns;
    }
    
    function column_default( $item, $column_name ) {
      switch( $column_name ) { 
        default:
            return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
      }
    }
    
    function get_sortable_columns() {
      $sortable_columns = array(
        'name'           => array('name',          false)
      );
      return $sortable_columns;
    }
    function column_stock_list($item) {
        $tmp = stock_plugin_explode_stock_list($item['stock_list']);
        $stock_list = implode(',',$tmp[0]);
        return sprintf('<input type="text" readonly="readonly" value="%s" class="stocklist" \>', $stock_list);    
    }
}
?>
