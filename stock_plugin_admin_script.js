jQuery(document).ready(function() {
    var admin_options_toggle = jQuery('.section_toggle');
    var option_display = jQuery('.section-options-display');
    var toggle_option = function(target) {
        if(target.text() == "+"){
            target.text('-');
        }else{
            target.text('+');
        }
        target.next('.section-options-display').toggle(200);
    }
    admin_options_toggle.click(function(event) {
        toggle_option(jQuery(this));
        toggleSection(event.target.id);
    });
    
    function ui_sortable_update_hidden_input() {
        var data = jQuery('#sortable-data-active').sortable('toArray'); // get serialized array
        jQuery('#input-active-data-items').val(data); // write out to hidden html input for POST
    }

    
    // initialize jQuery ui-sortable (http://jqueryui.com/sortable/)
    jQuery(function() {
        jQuery( "#sortable-data-active,#sortable-data-inactive").sortable({
            connectWith: ".sortable-data-items-container", // all items with this class are connected sorting containers
            items: ".sortable-data-item", // all items with this class are valid sortable items
            stop: function (event, ui) { // we need to do some stuff every time the sort operation is completed
                ui_sortable_update_hidden_input();
                ui_sortable_stop_callback(); // redefined in plugin-specific admin script.js
            },
            create: function(event, ui) { // we need to do some stuff on initialization
                ui_sortable_update_hidden_input();
            }
        });
    });
    
    var dependant_input_list = [
        ['bg_color_change',  false] // list of inputs w/ dependencies (used to be much larger)
    ];
    
    for (var i = 0; i < dependant_input_list.length; ++ i) {
        var itmp = jQuery("#input_"+dependant_input_list[i]);
        var dtmp = jQuery(".disabled_"+dependant_input_list[i]);
        
        toggle_suboption(itmp, dtmp, dependant_input_list[i][1]); // run the function once on pageload
        itmp.change(itmp, dtmp, function(event) {    // then register it to the .change event handler
            toggle_suboption(this,event.data[1],event.data[2])
        });
        
    }
});

var namepairs = {
    stock_name:'Stock Name',
    stock_symbol:'Stock Symbol',
    last_value:'Last Value',
    change_value:'Change Value',
    change_percent:'Change Percent',
    market_cap:'Market Cap',
    fifty_week_range:'Fifty Week Range',
    pe_ratio:'P/E Ratio',
    earning_per_share:'Earning Per Share',
    revenue:'Revenue',
    user_text:'Comment'
};

var ui_sortable_stop_callback = function() {
        // this function intentionally left blank, it is redefined on a per-plugin basis in the plugin-specific admin script.js
}

function input_color_enhance(target) {
        target = jQuery(target);
        if (target.type != 'text') {
            // jQuery("label[for="+target+"]").next('sup').remove(); // is this better?
            target.parent().parent().find('sup').remove();
        }
}

function readCookie(name) {
        var nameEQ = name + "=";
        var ca = document.cookie.split(';');
        for(var i=0;i < ca.length;i++) {
                var c = ca[i];
                while (c.charAt(0)==' ') c = c.substring(1,c.length);
                if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
        }
        return null;
}


function toggleSection(sectionToToggle) {
    var sec = readCookie(sectionToToggle);
    if (sec) {                //if cookie has a value
        if (sec == "none") {  //if the section is collapsed when clicked
            document.cookie = sectionToToggle + "=block";  //set the new cookie state to uncollpased
        } else {              //if the section is not collapsed when clicked
            document.cookie = sectionToToggle + "=none";   //set the new cookie state to collpased
        }
    } else {                //if cookie doesn't have a value
        document.cookie = sectionToToggle + "=block";      //user clicked a section for the first time, it is now uncollapsed
    }
}

function fadeNotification() {
    jQuery('.updated').delay(5000).fadeTo(1000,0) // fades notification out
}


function swap_layout(new_layout) { // when the user clicks a radio button for a new layout, disable inputs that arent valid for that input
    var itmp,l_tmp0,l_tmp1;
    switch(new_layout) {
        case 1: itmp = [true,true]; break; // true = disable input
        case 2: itmp = [false,false]; break; // false = enable input
        case 3: itmp = [false,false]; break;
        case 4: itmp = [false,true]; break;
    }
    jQuery('#input_height').prop('disabled',itmp[0]);
    jQuery('#input_max_display').prop('disabled',itmp[1]);
    l_tmp0 = (itmp[0] ? 0.2 : 1);
    l_tmp1 = (itmp[1] ? 0.2 : 1);
    jQuery('label[for="input_height"]').css({opacity:l_tmp0});
    jQuery('label[for="input_max_display"]').css({opacity:l_tmp1});
}

function toggle_suboption(button,target,invert) { // options page dependency function, button is the checkbox, target is a css class containing all lables & inputs for dependants
    var target_input = jQuery(target).filter('input'); // seperate inputs from labels
    var target_label = jQuery(target).filter('label'); 
    button = jQuery(button);  // This has to be a jQuery object
    var status = (!invert) ? button.prop('checked') : !button.prop('checked'); // If invert is true, status is opposite of the button state
    target_input.prop('disabled', status); // Set disabled to equal status
    if (status) {
        target_label.addClass("label_disabled");
    } else {
        target_label.removeClass("label_disabled");
    }
}
