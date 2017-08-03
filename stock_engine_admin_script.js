function ui_sortable_update_default_sort() {
    var dsort = jQuery('#default-sort');
    var data = jQuery('#sortable-data-active').sortable('toArray');
    var current = dsort.find("option:selected").val(); // save the currently selected option for later
    dsort.empty();
    jQuery.each(data, function(index,value) {
        dsort.append(jQuery("<option></option>").attr("value",value).text(namepairs[value])); // rewwrite all <option>s into <select>
    });
    if (jQuery('#default-sort option[value="'+current+'"]').length != 0) { // if the previously selected option is still availble, re-select it
        jQuery('#default-sort option[value="'+current+'"]').attr('selected','selected');
    }
}

var ui_sortable_stop_callback = function() {
    ui_sortable_update_default_sort();
}
