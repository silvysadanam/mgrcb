"use strict";

var Receipt = {
    entryBlock: null,
    headOptions: null,
    ready: function () {
        Receipt.entryBlock = $("tbody#entry-block");
        Receipt.headOptions = $("select#head-options").html();
        $("a#add-entry-row").click(Receipt.addRow);
        $("select#payment_type").change(Receipt.enableType);        
        Receipt.entryBlock.on("click", "a.delete-entry-row", Receipt.deleteRow);
        Receipt.entryBlock.on("blur", "input[type=number]", Receipt.reCalculate);
        $("form#form-receipt").submit(Receipt.validateForm);
        $("input#advance").blur(Receipt.calculateBalance);
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
    addRow: function () { 
        var entryCount = Receipt.entryBlock.find("tr").length;
        ++entryCount;
        var row = '<tr><td class="entry-number">' + entryCount + '</td>'
                + '<td><select name="head[]" class="form-control" title="Head">'
                + Receipt.headOptions + '</select></td>'
                + '<td><input type="number" name="amount[]" class="form-control entry-amount" placeholder="Amount" min="1" max="9999999" step="1" required="required"></td>'
                + '<td><a href="javascript:;" class="btn btn-sm btn-outline-danger delete-entry-row" title="Delete"><i class="ft-x-square"></i> Delete</a>'
                + '</td></tr>';
        Receipt.entryBlock.append(row);
    },
    
    deleteRow: function () {
        if (1 === Receipt.entryBlock.find("tr").length) {
            Receipt.showMessage("At least one entry is required");
            return false;
        }
        
        $(this).parents("tr:first").remove();
        Receipt.reNumber();
        Receipt.reCalculate();
    },
    
    reNumber: function () {
        if (0 === Receipt.entryBlock.find("tr").length) {
            return;
        }
        
        var counter = 0;
        Receipt.entryBlock.find("tr").each(function () {
                ++counter;
                $(this).find("td:first").text(counter);
            }
        );
    },
    reCalculate: function () {
        var amount = 0;
        if (Receipt.entryBlock.find("tr").length > 0) {
            Receipt.entryBlock.find("input.entry-amount").each(function () {
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
        Receipt.clearMessage();
        if (Receipt.notSelected($("select#donor_id").val()) && Receipt.notSelected($("select#member_id").val()) && Receipt.notSelected($("input#non_member").val())) {
            Receipt.showMessage("Auctioneer/Member/Non Member not selected");
            return false;
        }
        
      /*  if (Receipt.notSelected($("select#member_id").val())) {
            Receipt.showMessage("Member not selected");
            return false;
        }*/
       var paymentType = $("select#payment_type").val();
       if(paymentType == 'Bank'){         
            if (Receipt.notSelected($("select#bank_id").val())) {
                 Receipt.showMessage("Bank details not selected");
                 return false;
            }
        }
        
        
        if (0 === Receipt.entryBlock.find("tr").length) {
            Receipt.showMessage("No entry added");
            return false;
        }
        
        var valid = true;
        var entryNumber = 0;
        Receipt.entryBlock.find("tr").each(function () {
                var row = $(this);
                ++entryNumber;
                if (Receipt.notSelected(row.find("select:first").val())) {
                    Receipt.showMessage("Entry " + entryNumber + ": Head not selected");
                    valid = false;
                    return false;
                }
                
                if (! Receipt.isAmount(row.find("input.entry-amount").val())) {
                    Receipt.showMessage("Entry " + entryNumber + ": Invalid Amount");
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
        
        return ! Receipt.isAmount(value);        
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
        Receipt.ready();
    }
);
