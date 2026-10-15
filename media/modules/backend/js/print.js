"use strict";

var Print = {
    ready: function () {
        $("select.select2").select2();

        $("form#form-summary").submit(function () {

            if ($("select#report_option").val() > 0) {
                // DOWNLOAD / other options
                $(this).attr("target", "_self");
                $(this).attr("action", "");
                $(this).attr("method", "post");
            } else {
                // PRINT VIEW
                $(this).attr("target", "_blank");
                $(this).attr("action", "");
                $(this).attr("method", "post");
            }

            return true;
        });
    }
};

$(document).ready(function () {
    Print.ready();
});