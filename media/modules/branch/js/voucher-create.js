"use strict";

var Voucher = {
    entryBlock: null,
    headOptions: null,
    ready: function () {
        Voucher.entryBlock = $("tbody#entry-block");
        Voucher.headOptions = $("select#head-options").html();
        $("a#add-entry-row").click(Voucher.addRow);
        $("select#payment_type").change(Voucher.enableType);     
        $("select#head").change(Voucher.getMemberCollection);
        Voucher.entryBlock.on("click", "a.delete-entry-row", Voucher.deleteRow);
        Voucher.entryBlock.on("blur", "input[type=number]", Voucher.reCalculate);
        $("form#form-receipt").submit(Voucher.validateForm);
        $("input#advance").blur(Voucher.calculateBalance);
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
    getMemberCollection: function () {
       
        var headID = parseInt($(this).val());
        var receipt_date = $("input#receipt_date").val();
        if (headID > 0) {
            var formAction = $("input#get-collection-url").val();
            $("input#collectionID").val("");
            $("input#amount").prop("readonly", false).prop("required", true).val("");
            $.post(formAction, { id : headID, receiptDate :  receipt_date }, function(reply) {
                    if (reply.success && reply.valid) {
                        $("input#collectionID").val(headID);
                        $("input#amount").prop("required", false).prop("readonly", true).val(reply.result);
                    } else if (reply.redirect) {
                        ApplicationCommon.reloadPage();
                    } else if (reply.message) {
                        if (displayError) {
                            LoanCreate.showMessage(reply.message);
                        }
                    } else {
                        alert("Unable to complete the request.");
                    }
                }, "json"
            );
        } else {
           
            $("input#amount").prop("readonly", false).prop("required", true).val("");
        }
    },
    addRow: function () { 
        var entryCount = Voucher.entryBlock.find("tr").length;
        ++entryCount;
        var row = '<tr><td class="entry-number">' + entryCount + '</td>'
                + '<td><select name="head[]" class="form-control" title="Head">'
                + Voucher.headOptions + '</select></td>'
                + '<td><input type="number" name="amount[]" class="form-control entry-amount" placeholder="Amount" min="1" max="9999999" step="1" required="required"></td>'
                + '<td><a href="javascript:;" class="btn btn-sm btn-outline-danger delete-entry-row" title="Delete"><i class="ft-x-square"></i> Delete</a>'
                + '</td></tr>';
        Voucher.entryBlock.append(row);
    },
    
    deleteRow: function () {
        if (1 === Voucher.entryBlock.find("tr").length) {
            Voucher.showMessage("At least one entry is required");
            return false;
        }
        
        $(this).parents("tr:first").remove();
        Voucher.reNumber();
        Voucher.reCalculate();
    },
    
    reNumber: function () {
        if (0 === Voucher.entryBlock.find("tr").length) {
            return;
        }
        
        var counter = 0;
        Voucher.entryBlock.find("tr").each(function () {
                ++counter;
                $(this).find("td:first").text(counter);
            }
        );
    },
    reCalculate: function () {
        var amount = 0;
        if (Voucher.entryBlock.find("tr").length > 0) {
            Voucher.entryBlock.find("input.entry-amount").each(function () {
                    var itemAmount = $(this).val();
                    if ("" !== itemAmount) {
                        amount += parseInt(itemAmount);
                    }
                }
            );    
        }
        $("input#total-amount").val(amount);
        return false;
    },
    
    
    validateForm: function () {
        Voucher.clearMessage();
        if (Voucher.notSelected($("select#donor_id").val()) && Voucher.notSelected($("select#member_id").val()) && Voucher.notSelected($("input#non_member").val())) {
            Voucher.showMessage("Donor/Member/Non Member not selected");
            return false;
        }
        
      /*  if (Voucher.notSelected($("select#member_id").val())) {
            Voucher.showMessage("Member not selected");
            return false;
        }*/
       var paymentType = $("select#payment_type").val();
       if(paymentType == 'Bank'){         
            if (Voucher.notSelected($("select#bank_id").val())) {
                 Voucher.showMessage("Bank details not selected");
                 return false;
            }
        }
        
        
        if (0 === Voucher.entryBlock.find("tr").length) {
            Voucher.showMessage("No entry added");
            return false;
        }
        
        var valid = true;
        var entryNumber = 0;
        Voucher.entryBlock.find("tr").each(function () {
                var row = $(this);
                ++entryNumber;
                if (Voucher.notSelected(row.find("select:first").val())) {
                    Voucher.showMessage("Entry " + entryNumber + ": Head not selected");
                    valid = false;
                    return false;
                }
                
                if (! Voucher.isAmount(row.find("input.entry-amount").val())) {
                    Voucher.showMessage("Entry " + entryNumber + ": Invalid Amount");
                    valid = false;
                    return false;
                }
            }
        );
        
        if (! valid) {
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
    
    notNumber: function (value) {
        if ("" === value) {
            return true;
        }
        
        if (isNaN(value)) {
            return true;
        }
        
        if ("0" === value) {
            return true;
        }
        
        return ! Voucher.isAmount(value);        
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
    },
    
    isQuantity: function (value) {
        if ("" === value) {
            return true;
        }
        
        if (isNaN(value)) {
            return false;
        }
        
        if ("0" === value) {
            return true;
        }
        
        return /^[1-9]\d*$/.test(value);   
    }
    
};

$(document).ready(function () {
        Voucher.ready();
    }
);
