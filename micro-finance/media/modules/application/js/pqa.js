"use strict";

var Pqa = {
    resultBox: null,
    ready: function () {
        $("input#member_code").blur(Pqa.loadMember);
        $("select#fmc_id").change(Pqa.fmcChange);
        $("form#form-pqa").submit(Pqa.validateForm);
        $("select.select2").select2();
        $("a#check-loan-filter").click(Pqa.checkLoan);
        $("a#check-member-filter").click(Pqa.checkMember);
        Pqa.resultBox = $("div#check-result");
    },
    
    loadMember: function () {
        var memberCode = $(this).val();
        $("input#member_name").val("");
        if (0 === $.trim(memberCode).length) {
            return false;
        }
        
        var fmcId = parseInt($("select#fmc_id").val());
        var formAction = $("input#load-member-url").val();
        $.post(formAction, { id : fmcId, code :  memberCode }, function(reply) {
                if (reply.success && reply.valid) {
                    $("input#member_name").val(reply.result);
                } else if (reply.redirect) {
                    ApplicationCommon.reloadPage();
                } else if (reply.message) {
                    Pqa.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );
        
        return false;
    },
    
    fmcChange: function () {
        var fmcId = $(this).val();
        $("input#member_name").val("");
        var memberCode = $("input#member_code").val();
        if (0 === $.trim(memberCode).length) {
            return false;
        }
        
        var formAction = $("input#load-member-url").val();
        $.post(formAction, { id : fmcId, code :  memberCode }, function(reply) {
                if (reply.success && reply.valid) {
                    $("input#member_name").val(reply.result);
                } else if (reply.redirect) {
                    ApplicationCommon.reloadPage();
                } else if (reply.message) {
                    Pqa.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );
        
        return false;
    },
    
    validateForm: function () {
        if ($("select#report_option").val() > 0) {
            $(this).attr("target", "_self");
            $(this).attr("action", "");
            $(this).attr("method", "post");
        } else {
            $(this).attr("target", "_blank");
            $(this).attr("action", $("input#view-pqa-url").val());
            $(this).attr("method", "get");
        }
        
        return true;
    },
    
    checkLoan: function () {
        var formData = {
            fmc_id : $("select#fmc_id").val(),
            member_code : $("input#member_code").val(),
            reporting_date : $("select#reporting_date").val(),
            loan_source_id : $("select#loan_source_id").val(),
            loan_type_id : $("select#loan_type_id").val(),
            amount : $("select#amount").val(),
            ageing : $("select#ageing").val()
        };
        
        var formAction = $("input#check-pqa-url").val();
        $.post(formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    Pqa.resultBox.html(reply.result);
                    Pqa.resultBox.removeClass("alert-success");
                    Pqa.resultBox.removeClass("alert-danger");
                    Pqa.resultBox.addClass(reply.alert_class).show();
                } else if (reply.redirect) {
                    ApplicationCommon.reloadPage();
                } else if (reply.message) {
                    Pqa.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );
    },
    
    checkMember: function () {
        var formData = {
            fmc_id : $("select#fmc_id").val(),
            member_code : $("input#member_code").val(),
            reporting_date : $("select#reporting_date").val(),
            amount : $("select#amount").val(),
            ageing : $("select#ageing").val()
        };
        
        var formAction = $("input#check-pqa-url").val();
        $.post(formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    Pqa.resultBox.html(reply.result);
                    Pqa.resultBox.removeClass("alert-success");
                    Pqa.resultBox.removeClass("alert-danger");
                    Pqa.resultBox.addClass(reply.alert_class).show();
                } else if (reply.redirect) {
                    ApplicationCommon.reloadPage();
                } else if (reply.message) {
                    Pqa.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );
    },
    
    showMessage: function (message) {
        $("div#validation-message").show();
        $("div#validation-message").html(message);
        $("html,body").animate({
            scrollTop: $("div#validation-message").offset().top - 50
        }, 2000);
    },
    
    clearMessage: function () {
        $("div#validation-message").hide();
    }
    
};

$(document).ready(function () {
        Pqa.ready();
    }
);
