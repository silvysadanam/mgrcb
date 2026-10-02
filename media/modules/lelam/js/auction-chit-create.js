"use strict";

var AuctionChit = {
    entryBlock: null,
    medicineOptions: null,
    ready: function () {
        AuctionChit.entryBlock = $("tbody#entry-block");
        AuctionChit.medicineOptions = $("select#medicine-options").html();
        $("a#add-entry-row").click(AuctionChit.addRow);
        AuctionChit.entryBlock.on("click", "a.delete-entry-row", AuctionChit.deleteRow);
        AuctionChit.entryBlock.on("blur", "input[type=number]", AuctionChit.reCalculate);
        $("form#form-auction").submit(AuctionChit.validateForm);
        $("input#advance").blur(AuctionChit.calculateBalance);
    },
    
    addRow: function () {
        var entryCount = AuctionChit.entryBlock.find("tr").length;
        ++entryCount;
        var row = '<tr><td class="entry-number">' + entryCount + '</td>'
                + '<td><select name="medicine[]" class="form-control" title="Medicine">'
                + AuctionChit.medicineOptions + '</select></td>'
                + '<td><input type="number" name="amount[]" class="form-control entry-amount" placeholder="Amount" min="1" max="999999" step="1" required="required"></td>'
                + '<td><input type="number" name="quantity[]" class="form-control entry-quantity" placeholder="Weight" max="9999" step="1"></td>'
                + '<td><a href="javascript:;" class="btn btn-sm btn-outline-danger delete-entry-row" title="Delete"><i class="ft-x-square"></i> Delete</a>'
                + '</td></tr>';
        AuctionChit.entryBlock.append(row);
    },
    
    deleteRow: function () {
        if (1 === AuctionChit.entryBlock.find("tr").length) {
            AuctionChit.showMessage("At least one entry is required");
            return false;
        }
        
        $(this).parents("tr:first").remove();
        AuctionChit.reNumber();
        AuctionChit.reCalculate();
    },
    
    reNumber: function () {
        if (0 === AuctionChit.entryBlock.find("tr").length) {
            return;
        }
        
        var counter = 0;
        AuctionChit.entryBlock.find("tr").each(function () {
                ++counter;
                $(this).find("td:first").text(counter);
            }
        );
    },
    
    reCalculate: function () {
        var amount = 0;
        var quantity = 0;
        if (AuctionChit.entryBlock.find("tr").length > 0) {
            AuctionChit.entryBlock.find("input.entry-amount").each(function () {
                    var itemAmount = $(this).val();
                    if ("" !== itemAmount) {
                        amount += parseInt(itemAmount);
                    }
                }
            );
    
            AuctionChit.entryBlock.find("input.entry-quantity").each(function () {
                    var itemQuantity = $(this).val();
                    if ("" !== itemQuantity) {
                        quantity += parseInt(itemQuantity);
                    }
                }
            );
        }
        
        $("input#total-amount").val(amount);
        $("input#total-quantity").val(quantity);
        var advance = parseInt($("input#advance").val());
        var balance = amount;
        if (amount > 0 && advance > 0) {
            if (advance <= amount) {
                balance = amount - advance;
            }
        }
        
        $("input#balance").val(balance);
        
        return false;
    },
    
    validateForm: function () {
        AuctionChit.clearMessage();
        if (AuctionChit.notSelected($("select#member_id").val())) {
            AuctionChit.showMessage("Member not selected");
            return false;
        }
        
        var advance = $("input#advance").val();
        if ("" !== advance) {
            if (isNaN(advance)) {
                AuctionChit.showMessage("Advance is invalid");
                return false;
            }
            
            if ("0" !== advance) {
                if (! AuctionChit.isAmount(advance)) {
                    AuctionChit.showMessage("Advance is invalid");
                    return false;
                }
            }
        }
        
        if (0 === AuctionChit.entryBlock.find("tr").length) {
            AuctionChit.showMessage("No entry added");
            return false;
        }
        
        var valid = true;
        var entryNumber = 0;
        AuctionChit.entryBlock.find("tr").each(function () {
                var row = $(this);
                ++entryNumber;
                if (AuctionChit.notSelected(row.find("select:first").val())) {
                    AuctionChit.showMessage("Entry " + entryNumber + ": Medicine not selected");
                    valid = false;
                    return false;
                }
                
                if (! AuctionChit.isAmount(row.find("input.entry-amount").val())) {
                    AuctionChit.showMessage("Entry " + entryNumber + ": Invalid Amount");
                    valid = false;
                    return false;
                }
                
                if (! AuctionChit.isQuantity(row.find("input.entry-quantity").val())) {
                    AuctionChit.showMessage("Entry " + entryNumber + ": Invalid weight");
                    valid = false;
                    return false;
                }
            }
        );
        
        if (! valid) {
            return false;
        }
        
        if (advance > parseInt($("input#total-amount").val())) {
             AuctionChit.showMessage("Advance is more than total amount");
             return false;
        }
        
        return true;
    },
    
    calculateBalance: function () {
        var amount = parseInt($("input#total-amount").val());
        var advance = parseInt($("input#advance").val());
        var balance = amount;
        if (amount > 0 && advance > 0) {
            if (advance <= amount) {
                balance = amount - advance;
            }
        }
        
        $("input#balance").val(balance);
        
        return false;
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
        
        return ! AuctionChit.isAmount(value);        
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
        AuctionChit.ready();
    }
);
