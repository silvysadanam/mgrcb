"use strict";

var LoanHistory = {
    summaryModal : null,
    ready: function () {
        LoanHistory.summaryModal = $("div#modal-loan-summary");
        $("table#list-table-4").DataTable( {
                "lengthMenu": [[20, 50, 100, 200], [20, 50, 100, 200]],
                columnDefs: [ {
                        targets: [ 4 ],
                        searchable: false
                    }
                ]
            }
        );

        $("a.member-summary").click(LoanHistory.summary);
    },
    
    summary: function () {
        var memberId = $(this).data("id");
        var formData = { id : memberId };
        var formAction = $("input#view-summary-url").val();
            $.post(formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    var result = reply.result;
                    var member = reply.member;
                    LoanHistory.summaryModal.find("td#result-fmc").text(reply.fmc);
                    LoanHistory.summaryModal.find("td#result-member-name").text(member.name);
                    LoanHistory.summaryModal.find("td#result-member-code").text(member.code);
                    LoanHistory.summaryModal.find("td#result-total").text(result.total);
                    LoanHistory.summaryModal.find("td#result-active").text(result.active);
                    LoanHistory.summaryModal.find("td#result-closed").text(result.closed);
                    LoanHistory.summaryModal.find("td#result-preclosed").text(result.preclosed);
                    LoanHistory.summaryModal.find("td#result-delinquent").text(result.delinquent);
                    LoanHistory.summaryModal.find("td#result-refinanced").text(result.refinanced);
                    LoanHistory.summaryModal.find("td#result-rescheduled").text(result.rescheduled);
                    LoanHistory.summaryModal.find("td#result-written-off").text(result.written_off);
                    LoanHistory.summaryModal.modal("show");
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
        LoanHistory.ready();
    }
);
