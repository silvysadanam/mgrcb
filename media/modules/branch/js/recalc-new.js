"use strict";

var Advance = {
    ready: function () {
       // $("form#form-advance").submit(Advance.validateForm);
        $("a#check-balance").click(Advance.loadBalance);
        $("select#payment_type").change(Advance.enableType);     
    },
    enableType: function()
    {
        var paymentType = $("select#payment_type").val();
        if(paymentType == 'Bank'){
            $("select#bank_id").removeAttr("disabled");
        } else {
            $("select#bank_id").attr('disabled', 'disabled');
            $("select#bank_id").val(0);
        }
        
    },
    loadBalance: function () {
        Advance.clearMessage();
        var advanceDate = $("input#advance_date").val();
        $("td#check-advance-date").text(advanceDate);
        $("td#check-member").text("");
        $("td#check-deduction-type").text("");
        $("td#check-advance-type").text("");
        $("td#check-deduction-date").text("");
        $("td#check-closing-balance").text("");
        $("td#check-advance-amount").text("");
        $("td#check-overdue-amount").text("");
        $("td#check-modified-closing").text("");
        $("td#check-last").text("");
        $("td#check-allowed").text("");
        
        var memberId = $("select#member_id").val();
        if ("0" === memberId) {
            Advance.showMessage("Member not selected");
            return false;
        }
        
        var deductionTypeId = $("select#deduction_type_id").val();
        if ("0" === deductionTypeId) {
            Advance.showMessage("Deduction Type not selected");
            return false;
        }
        
        if (! Advance.isAmount($("input#advance_amount").val())) {
            Advance.showMessage("Amount is invalid");
            return false;
        }
        
        var formAction = $("input#check-balance-url").val();
        var formData = {
            member_id : memberId,
            deduction_type_id : deductionTypeId,
            advance_amount : $("input#advance_amount").val(),
            advance_date: $("input#advance_date").val()
        };
        
        $.post(formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    if (reply.result.valid) {
                        $("td#check-advance-date").text(reply.result.advance_date);
                        $("td#check-member").text(reply.result.member);
                        $("td#check-deduction-type").text(reply.result.deduction_type);
                        $("td#check-advance-type").text(reply.result.advance_type);
                        $("td#check-deduction-date").text(reply.result.deduction_date);
                        $("td#check-closing-balance").text(reply.result.closing_balance);
                        $("td#check-advance-amount").text(reply.result.advance_amount);
                        $("td#check-overdue-amount").text(reply.result.overdue_amount);
                        $("td#check-modified-closing").text(reply.result.modified_closing);
                        $("td#check-last").text(reply.result.last);
                        $("td#check-allowed").text(reply.result.allowed);
                    } else {
                        Advance.showMessage(reply.result.message);
                    }
                } else if (reply.redirect) {
                    BranchCommon.reloadPage();
                } else if (reply.message) {
                    Advance.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );
    },
    
    validateForm: function () {
        Advance.clearMessage();
        
        if (Advance.notSelected($("select#deduction_type_id").val())) {
            Advance.showMessage("Deduction Type not selected");
            return false;
        }
        
        return true;
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
    },
    
    notSelected: function (value) {
        return 0 === parseInt(value);
    },
    
    isAmount: function (value) {
        if ("" === value) {
            return false;
        }
        
        if (isNaN(value)) {
            return false;
        }
        
        if ("0" === value) {
            return false;
        }
        
        return /^[1-9]\d*$/.test(value);   
    }
    
};

$(document).ready(function () {
        Advance.ready();
    }
);
