"use strict";

var LoanSummary = {
    
    ready: function () {
        $("input#start_date,input#end_date").daterangepicker({
                timePicker: false,
                singleDatePicker: true,
                maxDate: moment(),
                minDate: $("input#min-date").val(),
                showDropdowns: true,
                locale: {
                    format: ApplicationCommon.dateFormat
                }
            }
        );
    }
};

$(document).ready(function () {
        LoanSummary.ready();
    }
);
