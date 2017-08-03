function stock_engine_datatables_init(engine_root, engine_config) {
    var options = {searching:false,info:false};
    switch(parseInt(engine_config['layout'])) {
        case 1: // Expand - Engine has no height cap, it fills as much space as it needs for the stocks
            options.paging = false;
        break;
        case 2: // Static - Engine has a fixed height, it will truncate extra stocks
            options.paging = true;
            options.pagingType = 'simple';
            options.pageLength = parseInt(engine_config['display_number']);
            options.lengthChange = false;
        break;
        case 3: // Paged - Engine has a fixed height, it will generate paging buttons to handle extra stocks
            options.paging = true;
            options.pagingType = 'simple';
            options.pageLength = parseInt(engine_config['display_number']);
            options.info = true;
            options.lengthChange = false;
        break;
        case 4: // Scroll - Engine has a fixed height, it will generate a scrollbar to handle extra stocks
            options.paging = false;
            options.scrollY = engine_config['height'];
            options.scrollCollapse = true;
        break;
    }
    
    var active_data_items = jQuery.parseJSON(engine_config['active_data_items']);
    for (i = 0, len = active_data_items.length; i < len; i++) { // find the default sort
        if (active_data_items[i][1] != 0) {
            var default_sort_col = i; // datatables uses numeric index from the left
            if (active_data_items[i][1] == 1) {
                var default_sort_dir = 'asc';
            } else {
                var default_sort_dir = 'desc';
            }
        }
    }
    options.order = [[default_sort_col,default_sort_dir]]; // options.order must always be array of arrays, ex. [[1, 'asc']]
    
    options.columnDefs = [];

    if (!parseInt(engine_config['feature_list'][1])) { // feature 1 is sorting
        options.columnDefs.push({'targets': '_all','orderable': false});
    };

    engine_root.dataTable(options);
};
