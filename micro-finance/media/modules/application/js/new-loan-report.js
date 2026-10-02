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

        $("a.view-loans").click(LoanSummary.summary);
    },
    
    summary: function () {
        var params = $(this).data("params");
        var formData = { id : params };
        var formAction = $("div#modal-loan input#summary-url").val();
            $.post(formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    var result = reply.summary;
                    $("div#modal-loan td#summary-fmc").text(result.fmc);
                    $("div#modal-loan td#summary-month").text(result.month);
                    $("div#modal-loan td#summary-source").text(result.source);
                    $("div#modal-loan td#summary-type").text(result.type);
                    $("div#modal-loan td#summary-loans").text(result.loans);
                    $("div#modal-loan td#summary-amount").text(result.amount);
                    
                    $("div#modal-loan tbody#loans-block").html(reply.html);
                    $("div#modal-loan").modal("show");
                } else if (reply.redirect) {
                    ApplicationCommon.reloadPage();
                } else if (reply.message) {
                    alert(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );
        
        return false;
    }
};

$(document).ready(function () {
        LoanSummary.ready();
    }
);
