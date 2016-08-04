var mg;
jQuery(document).ready(function ($) {

    $ = jQuery;

    var $col_zone = $('.active-drop[data-areaid="directory_table-columns"]'),
        self = {
            active_columns: [],
            directoryCol_zone: $col_zone,
            sort_box_name: 'sort_field_key'
        };

    mg = self;

    self.init = function () {
        self.getColumns(self.directoryCol_zone);
        self.updateSortBoxes();
        self.bindEvents();
    };

    self.bindEvents = function () {

        var sort_area = $('#directory-fields').find(".active-drop-field").data('uiSortable'),
            widget = sort_area.widget("ui-sortable");

        //adding new fields
        $('.gv-add-field.button-secondary[data-areaid="directory_table-columns"]').on("click", function () {
            $('.gv-fields').on("click", function (e) {
                $(document).one('ajaxSuccess', function (event, xhr, settings) {
                    if ('ajaxSuccess' == event.type) {
                        self.addColumn(e.currentTarget);
                        self.updateSortBoxes();
                    }
                });
            });
        });

        //removing fields
        $('.gv-field-controls a[href="#remove"]').on("click", function (e) {
            self.removeColumn(e);
            self.updateSortBoxes();
        });

        //reording fields
        widget.on("sortupdate", function (event, ui) {

            var selector = "[name='" + self.sort_box_name + "']",
                $boxes = $(selector);

            $.each($boxes, function (box_index, box_value) {
                $(this).empty();
            });

            var $col_zone = $('.active-drop[data-areaid="directory_table-columns"]');

            self.active_columns = [];

            self.getColumns($col_zone);
            self.updateSortBoxes();
            self.bindEvents();

        });
    };

    self.getColumns = function (column_zone) {
        var $fields = $(column_zone).find(".gv-fields");

        $.each($fields, function () {
            self.theColumns(this);
        });

        return self.active_columns;
    };

    self.addColumn = function (e) {

        var field_id = e.getAttribute('data-fieldid');
        var $all_fields_with_ID = $("[data-areaid='directory_table-columns'] *[data-fieldid='" + field_id + "']");

        for (var i = 0, count = $all_fields_with_ID.length; i < count; i++) {
            if (i === count - 1) {
                var uuid = $all_fields_with_ID[i];
                uuid = $(uuid).find('.field-key').prop("name");
                uuid = uuid.replace("fields[directory_table-columns][", "");
                uuid = uuid.replace("][id]", "");
            }
        }

        var label = document.getElementById("fieldsdirectory_table-columns" + uuid + "custom_label");

        if ("undefined" != typeof label.value) {
            if (label.value && "" !== label.value) {
                label = label.value;
            } else {
                label = $('.gv-fields[data-fieldid="' + field_id + '"]').find(".field-label").val();
            }

        } else {
            label = $('.gv-fields[data-fieldid="' + field_id + '"]').find(".field-label").val();
        }

        if ('custom' === field_id) {
            //Does the DT View allow custom content to be indexed?

            if ( "undefined" !== typeof gvDTIndex.index_custom_content && 0 == gvDTIndex.index_custom_content ){
                return self.active_columns;
            } else {
                var j = 0;
                var new_field_id = field_id + "_" + j;
                for (var key in self.active_columns) {
                    if (new_field_id === self.active_columns[key]['id']) {
                        j++;
                        new_field_id = field_id + "_" + j;
                    }
                }
                if (Object.keys(self.active_columns).length === 0) {
                    field_id = field_id + "_0";
                } else {
                    field_id = new_field_id;
                }
            }
        }

        self.active_columns[uuid] = {
            id: field_id,
            label: label
        };

        return uuid;

    };

    self.theColumns = function (e) {

        var field_id = e.getAttribute('data-fieldid'),
            $uuid = $(e).find(".field-key").attr("name"),
            $uuid = $uuid.replace("fields[directory_table-columns][", ""),
            $uuid = $uuid.replace("][id]", ""),
            label = document.getElementById("fieldsdirectory_table-columns" + $uuid + "custom_label"),
            label = typeof label.value == "undefined" || "" == label.value ? $(e).find(".field-label").val() : label.value;


        if ('custom' === field_id) {
            //Does the DT View allow custom content to be indexed?
            if ( "undefined" !== typeof gvDTIndex.index_custom_content && 0 == gvDTIndex.index_custom_content ){
                console.log(gvDTIndex.index_custom_content);
                return self.active_columns;
            } else {
                var i = 0;
                var new_field_id = field_id + "_" + i;
                for (var key in self.active_columns) {
                    if (new_field_id === self.active_columns[key]['id']) {
                        i++;
                        new_field_id = field_id + "_" + i;
                    }
                }
                if (Object.keys(self.active_columns).length === 0) {
                    field_id = field_id + "_0";
                } else {
                    field_id = new_field_id;
                }
            }
        }

        self.active_columns[$uuid] = {
            id: field_id,
            label: label
        };

        return self.active_columns;

    };

    self.removeColumn = function (e) {

        var $parent = $(e.target).parents('.gv-fields');

        var uuid = $parent.find(".field-key").prop("name"),
            uuid = uuid.replace("fields[directory_table-columns][", ""),
            uuid = uuid.replace("][id]", "");

        delete self.active_columns[uuid];

        return uuid;
    };

    self.updateSortBoxes = function () {
        var selector = "[name='" + self.sort_box_name + "']",
            $boxes = $(selector);

        window.dynamicFieldMap = "undefined" !== typeof dynamicFieldMap ? dynamicFieldMap : {};

        $.each($boxes, function (box_index, box_value) {
            $(box_value).empty();
            for (var col_hash in self.active_columns) {
                if ("undefined" !== typeof window.dynamicFieldMap['data'] && (window.dynamicFieldMap['data']).length > 0) {
                    var selected =
                        $.grep(window.dynamicFieldMap['data'], function (el, index) {
                            return el['key'] === self.active_columns[col_hash]['id'] && box_index == index;
                        });
                    if (0 < selected.length) {
                        $(box_value).append("<option value='" + self.active_columns[col_hash]['id'] + "' selected>" + self.active_columns[col_hash].label + "</option>");
                    } else {
                        $(box_value).append("<option value='" + self.active_columns[col_hash]['id'] + "'>" + self.active_columns[col_hash].label + "</option>");
                    }
                }
            }
        });

    };

    self.init();

});