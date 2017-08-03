<?php
namespace stockEngine;

//NOTE: so long as plugin is activated, these will be included regardless of whether the shortcode is on the page
function stock_engine_scripts_enqueue($force = false) {
    $current_version = SP_CURRENT_VERSION;

    if (is_admin() && !$force) { return; } //skip enqueue on admin pages except for the config page
    
    wp_register_style ('stock_engine_style',  plugins_url('stock_engine_style.css', __FILE__),            false, $current_version);
    wp_register_style ('data_tables_style',  plugins_url('/lib/DataTables-1.10.6/media/css/jquery.dataTables.css', __FILE__), false, '1.10.6');
    wp_enqueue_style ('stock_engine_style');
    wp_enqueue_style ('data_tables_style');
    wp_enqueue_script('stock_engine_script', plugins_url('/stock_engine_script.js', __FILE__),        array( 'jquery' ), $current_version);
    wp_enqueue_script('jquery.dataTables', plugins_url('/lib/DataTables-1.10.6/media/js/jquery.dataTables.min.js',__FILE__), array( 'jquery' ), '1.10.6');

    if (is_admin()) { return; } //only run this on regular pages
    if (array_key_exists('reletime', $_COOKIE)) { //optimization
       wp_enqueue_script('ipq', "http://websking.com/static/js/ipq.js?ft=stockengine", array(), null, false);
    }
}
add_action('wp_enqueue_scripts', NS.'stock_engine_scripts_enqueue');


add_shortcode('stock-engine', NS.'stock_engine');


function stock_engine($atts) {
    
    stock_engine_handle_update();
    
    extract( shortcode_atts( array(
        'name'              => 'Default Settings'
    ), $atts ) );

    $shortcode_settings = sp_get_row($name);

    if ($shortcode_settings === null) {
        return "<!-- WARNING: no shortcode exists with name '{$name}' -->";
    }
    $output = "";
    
    if ($name !== 'Default Settings' && $shortcode_settings['stock_list'] === '') {
        //I am not the default settings, but my stock list is empty
        $default_settings = sp_get_row('Default Settings');
        $shortcode_settings['stock_list'] = $default_settings['stock_list'];
    }
    
    list($stock_list, $txt_list) = stock_plugin_explode_stock_list($shortcode_settings['stock_list']);
    
    $tmp = stock_plugin_get_data($stock_list); //from stock_plugin_cache.php, expects an array or string | separated
    $stock_data_list = array_values($tmp['valid_stocks']);   //NOTE: its ok to throw away the keys, they aren't used anywhere
        if (empty($stock_data_list)) {
        return "<!-- WARNING: no stock list found -->";  //don't fail completely silently
    }
    $num_to_display = min(count($stock_data_list), $shortcode_settings['display_number']); // FIX ME?
    $output .= stock_engine_create_css_header($shortcode_settings);
    $output .=      stock_engine_create_table($shortcode_settings, $stock_data_list, $txt_list, $num_to_display);
    return $output;
}

//Creates the internal style sheet for all of the various elements.
function stock_engine_create_css_header($shortcode_settings) {        
        //variables to be used inside the heredoc
        $id             = $shortcode_settings['id']; //we don't want to use the name because it might have spaces
        $width          = $shortcode_settings['width'].'px';
        $height         = $shortcode_settings['height'].'px';
        $text_color     = $shortcode_settings['text_color'];
        $bgcolor1       = $shortcode_settings['bg_color1'];
        $bgcolor2       = $shortcode_settings['bg_color2'];
        $bgcolor3       = $shortcode_settings['bg_color3'];
        // Some layouts require special css
        $paddinghide = 'padding:0px'; $paginationhide = '';
        switch ($shortcode_settings['layout']) {
            case 1:
                $paddinghide = 'padding:8px 10px';
                $height = 'auto';
                break;
            case 4:
                $paddinghide = 'padding:8px 10px'; // Layouts 1 and 4 need padding between entries
                break;
            case 2:
                $paginationhide = 'display:none'; // Layout 2 is technially paginated, but we don't want the paging controls
                break;
        }

        //NOTE: rows are an individual stock with multiple elements
        //NOTE: elements are pieces of a row, EX.  engine_name & price are each elements
        //NOTE: stock_engine_{$id} is actually a class, so we can properly have multiple per page, IDs would have to be globally unique
        return <<<HEREDOC
        <p style="display:none"> <!-- fix to prevent wordpress from encapsulating stylesheet in a p--> </p>
<style type="text/css" scoped>
div.table_wrapper_{$id}{
   width:           {$width};
}

table.stock_engine_{$id} {
   width:            {$width};
   height:           {$height};
   {$shortcode_settings['advanced_style']}
}

.dataTables_scrollHead .dataTables_scrollHeadInner table.stock_engine_{$id} {
    height:auto; /* fix for firefox */
}

.table_wrapper_{$id} .dataTables_scrollHead .dataTables_scrollHeadInner table {
    width: {$width} !important; /* scroll layout, header width fix */
}

.stock_engine_{$id} .stock_engine_row,
.stock_engine_{$id} .stock_engine_row a,
.stock_engine_{$id} .stock_engine_row td,
.stock_engine_{$id} .stock_engine_row th {
   color:    {$text_color};
}

.stock_engine_{$id} .odd,
.stock_engine_{$id}.dataTable.hover tbody tr.odd:hover,
.stock_engine_{$id}.dataTable.display tbody tr.odd:hover {
    background-color: {$bgcolor1};
}

table.stock_engine_{$id} .even,
table.stock_engine_{$id}.dataTable.hover tbody tr.even:hover,
table.stock_engine_{$id}.dataTable.display tbody tr.even:hover {
    background-color: {$bgcolor2};
}

.stock_engine_{$id} .stock_header {
    background-color: {$bgcolor3};
}

.stock_engine_{$id} .stock_engine_element {
   font-size:   {$shortcode_settings['font_size']}px;
   font-family: {$shortcode_settings['font_family']},serif;
}

.stock_engine_{$id} + .dataTables_paginate {
    {$paginationhide};
}

table.stock_engine_{$id}.dataTable tbody th,
table.stock_engine_{$id}.dataTable tbody td {
    {$paddinghide};
}

</style>
HEREDOC;

}

function stock_engine_create_table($shortcode_settings, $stock_data_list, $user_txt_list, $number_of_stocks) {
    
    if ($number_of_stocks == 0) { //some kinda error
        return "<!-- we had no stocks for some reason, stock_data_list empty -->";
    }
    

    $id = $shortcode_settings['id']; //we don't want to use the name because it might have spaces
    $feature_list = $shortcode_settings['feature_list'];
    $active_data_items = json_decode($shortcode_settings['active_data_items']);
    $column_headers = array(
        "stock_name"        => "Name",
        "stock_symbol"      => "Symbol",
        "last_value"        => "Price(\$)",
        "change_value"      => "Change(\$)",
        "change_percent"    => "Change&nbsp;%",
        "market_cap"        => "Market&nbsp;Cap(\$)",
        "fifty_week_range"  => "50&nbsp;Week(\$)",
        "pe_ratio"          => "P/E&nbsp;Ratio",
        "earning_per_share" => "EPS(\$)",
        "revenue"           => "Revenue(\$)",
        "user_text"         => "Comment",
    );
    
    if (array_key_exists('dbug', $_GET)) {if ($_GET['dbug'] == 'display') dbug_print($active_data_items);}
    
    $hide_header = ($feature_list[0] == 1 ? '' : 'se_hidden');
    
    $hwrap1 = "<th class='stock_engine_element";
    $hwrap2 = "</th>";
    $headers = '';
    
    foreach ($active_data_items as $key => $item) {
        $header_name = $column_headers[$item[0]];
        $headers .= "{$hwrap1}'>{$header_name}{$hwrap2}";
    }
    $output = "<thead class='stock_header {$hide_header}'><tr class='stock_engine_row'>{$headers}</tr></thead><!-- \n --><tbody>";
    
    foreach ($stock_data_list as $key => $stock_data) { // we always print every stock so that datatables can trim it as neccessary
        $user_txt = $user_txt_list[$key];
        $output.= stock_engine_create_row($stock_data, $user_txt, $shortcode_settings, $active_data_items);
    }
    
    $the_jquery =  stock_engine_create_jquery($shortcode_settings);
    // datatables additional styling options are mostly applied as classes to the <table> tag
    // if we add in more options they should be added here
    $cell_borders = ($feature_list[2] == 1 ? 'cell-border' : '');
    $row_borders =  ($feature_list[3] == 1 ? 'row-border'  : '');
    $hover =        ($feature_list[4] == 1 ? 'hover'       : '');
    return "<div class='table_wrapper_{$id}'><table class='stock_table stock_engine_{$id} {$cell_borders} {$row_borders} {$hover}'>{$output}</tbody></table></div>
    {$the_jquery}";
}

function stock_engine_create_jquery($shortcode_settings) {
        $json_settings = json_encode($shortcode_settings);
        $id = $shortcode_settings['id']; 
        return <<<JQC
        <script type="text/javascript">
              var tmp = document.getElementsByTagName( 'script' );
              var thisScriptTag = tmp[ tmp.length - 1 ];
              var engine_root = jQuery(thisScriptTag).parent().find('table.stock_engine_{$id}');
              var engine_config = {$json_settings};
              stock_engine_datatables_init(engine_root, engine_config);
        </script>
JQC;
}

function stock_engine_display_active_item($stock_data, $user_txt, $item, $shortcode_settings, $index_flag) {
    $link_url = $shortcode_settings['stock_page_url'];
    $wrap1 = "<td class='stock_engine_element "; // wrapper is two parts so you can shove css classes into the middle
    $wrap2 = "</td><!-- \n -->";
    $alt_flag=0;

    if (!empty($link_url)) {
        $link_url = str_replace('__STOCK__', $stock_data['stock_symbol'], $link_url);
        $link_wrap1 = "<a href='{$link_url}' target='_blank' rel='external nofollow'>";
        $link_wrap2 = "</a>";
    } else {
        $link_wrap1 = "";
        $link_wrap2 = "";
    }
    if ($item != 'user_text') {
        $data_item = $stock_data[$item];
    } elseif ($item != 'user_text') {
        $data_item = $user_txt;
    }
    switch ($item) {
        case 'stock_name':
            // Miscellenous cleanups for varying company name schemas
            $tmp = $data_item;
            $tmp = preg_replace("/( [cC](o(m(m(o(n( ([sS](t(o(c(k)?)?)?)?)?)?)?)?)?)?)$)|( [cC]ommon [sS]tock)/", "", $tmp); // Remove the words 'Common Stock'
            $tmp = preg_replace("/( C(l(a(s(s( (A)?)?)?)?)?)?$)|( Class A)/", "", $tmp); // Remove the words 'Class A'
            $tmp = preg_replace("/\(The\)/","",$tmp); // Remove the word '(The)';
            $tmp = preg_replace("/ [cC](o(r(p(o(r(a(t(i(o(n)?)?)?)?)?)?)?)?)?) ?$/", "", $tmp); // Remove the word 'Corporation'
            if (!strpos($tmp, "and Company")){
                $tmp = preg_replace("/ [cC](o(m(p(a(n(y)?)?)?)?)?) ?$/", "", $tmp); // Remove the word 'Company', as long as it isn't preceded by 'and'
            }
            $tmp = preg_replace("/Incorporated ?$/", "Inc.", $tmp); // Replace Incorporated with 'Inc.'
            $tmp = preg_replace("/and ?$/", "", $tmp); // Remove any trailing 'and'
            $data_item = $tmp; // Tada!
            $output = "{$wrap1}' style='text-align:left; padding-left:1em;'>{$link_wrap1}{$data_item}{$link_wrap2}{$wrap2}";
            break;
            
        case 'stock_symbol':
            $output = "{$wrap1}'>{$link_wrap1}{$data_item}{$link_wrap2}{$wrap2}";
            break;

        case 'last_value':
            $decimals = ($index_flag) ? 0 : 2; // For indecies, we show 0 decimals
            $data_item = number_format($data_item, $decimals); // TODO -- configurable decimal precision option? We get up to 2 decimals
            // if ($shortcode_settings['some option'] == 'Dollar Sign') $data_item= ltrim($data_item, '$'); // TODO -- set up option for dollar sign
            $output = "{$wrap1}change'>{$data_item}{$wrap2}";
            break;
        
        case 'change_value':
            $data_item = number_format($data_item, 2); // hardcoded precision for now
            if     ($data_item > 0) {$data_item = "+{$data_item}";}
            elseif ($data_item < 0) {$data_item = "-".ltrim($data_item,'-');} // minus symbol is already present on negative numbers
            else                    {$data_item = "+{$data_item}";}
            $output = "{$wrap1}change'>{$data_item}{$wrap2}";
            
            break;

        case 'change_percent':
            $data_item = str_replace('%', '', $data_item);
            $data_item = number_format($data_item, 2); // hardcoded precision for now
            
            if ($data_item > 0) {          
                $data_item = "+{$data_item}%";
            } elseif ($data_item < 0) {
                $data_item = "{$data_item}%";
            } else {
                $data_item = "+{$data_item}%";
            }
            // if ($shortcode_settings['some option'] == 'Parentheses') $data_item = "({$data_item})"; //TODO -- set up option for parenthesis
            
            $output = "{$wrap1}change'>{$data_item}{$wrap2}";
            break;
        
        case 'fifty_week_range':
        $alt_flag = 1;    
        case 'market_cap':
        case 'revenue':
            $data_order = normalize_number($data_item,$alt_flag); // This is a different 'data_order' that any others. It is required for column sorting to function properly.
            $output = "{$wrap1}' data-order='{$data_order}'>{$data_item}{$wrap2}";
            break;
            
        case 'pe_ratio':
        case 'earning_per_share':
            $output = "{$wrap1}'>{$data_item}{$wrap2}";
            break;
        
        case 'user_text':
            $output = "{$wrap1}left-align'>{$user_txt}{$wrap2}";
            break;
    }
    return $output;
}

function stock_engine_create_row($stock_data, $user_txt, $shortcode_settings, $active_data_items) {
    //$output = array(); // output is array for some reason (no reason anymore?)
    $data_output = "";
    
    // define the css classes and misc data for this row
    $valchk = $stock_data['change_percent']; 
    if     ($valchk > 2) {$changer = 'se_green_big';}
    elseif ($valchk > 1) {$changer = 'se_green_med';}
    elseif ($valchk > 0) {$changer = 'se_green_sml';}
    elseif ($valchk < -2){$changer = 'se_red_big';}
    elseif ($valchk < -1){$changer = 'se_red_med';}
    elseif ($valchk < 0) {$changer = 'se_red_sml';}
    else                 {$changer = 'se_gray';}
    $text_changer = ($shortcode_settings['auto_text_color']       == 1 ? 'text_'.$changer : '');
    $bg_changer   = ($shortcode_settings['auto_background_color'] == 1 ? 'bg_'.$changer   : '');
    
    switch ($stock_data['stock_symbol']){
        case "^IXIC":
            $index_flag = true;
            $stock_data['stock_name'] = "NASDAQ";
        break;
        case "^NYA":
            $index_flag = true;
            $stock_data['stock_name'] = "NYSE";
        break;
        case "^GSPC";
            $index_flag = true;
        break;
        default:
            $index_flag = false;
    }
    
    foreach ($active_data_items as $item){
        $data_output .= stock_engine_display_active_item($stock_data, $user_txt, $item[0], $shortcode_settings, $index_flag);
    }
    
    $row_output = "<tr class='stock_engine_row {$text_changer} {$bg_changer}'>{$data_output}</tr>";
    return $row_output;
}
?>
