"use strict";

var Pqr = {
    resultBox: null,
    ready: function () {
        $("select.select2").select2();
        $("form#form-pqr").submit(function () {
                if ($("select#report_option").val() > 0) {
                    $(this).attr("target", "_self");
                    $(this).attr("action", "");
                    $(this).attr("method", "post");
                } else {
                    $(this).attr("target", "_blank");
                    $(this).attr("action", $("input#view-pqr-url").val());
                    $(this).attr("method", "get");
                }

                return true;
            }
        );

        $("a#check-loan-filter").click(Pqr.checkLoan);
        $("a#check-member-filter").click(Pqr.checkMember);
        Pqr.resultBox = $("div#check-result");
        $("a.scroll-to").on("click", function (e) {
            e.preventDefault();
            var targetBlock = $("section#" + $(this).data("target"));
            $("html, body").stop().animate({
                    "scrollTop": targetBlock.offset().top
                }, 800, "swing", function () {}
            );
    
            return false;
        });
    },
    
    checkLoan: function () {
        var formData = {
            fmc_id : $("select#fmc_id").val(),
            reporting_date : $("select#reporting_date").val(),
            loan_source_id : $("select#loan_source_id").val(),
            loan_type_id : $("select#loan_type_id").val(),
            amount : $("select#amount").val()
        };
        
        var formAction = $("input#check-pqr-url").val();
        $.post(formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    Pqr.resultBox.html(reply.result);
                    Pqr.resultBox.removeClass("alert-success");
                    Pqr.resultBox.removeClass("alert-danger");
                    Pqr.resultBox.addClass(reply.alert_class).show();
                } else if (reply.redirect) {
                    ApplicationCommon.reloadPage();
                } else if (reply.message) {
                    Pqr.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );
    },
    
    checkMember: function () {
        var formData = {
            fmc_id : $("select#fmc_id").val(),
            reporting_date : $("select#reporting_date").val(),
            amount : $("select#amount").val()
        };
        
        var formAction = $("input#check-pqr-url").val();
        $.post(formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    Pqr.resultBox.html(reply.result);
                    Pqr.resultBox.removeClass("alert-success");
                    Pqr.resultBox.removeClass("alert-danger");
                    Pqr.resultBox.addClass(reply.alert_class).show();
                } else if (reply.redirect) {
                    ApplicationCommon.reloadPage();
                } else if (reply.message) {
                    Pqr.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );
    }
};

$(document).ready(function () {
        Pqr.ready();
    }
);
