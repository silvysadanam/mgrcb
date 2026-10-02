"use strict";

var AuctionChit = {
    entryBlock: null,
    medicineOptions: null,
    memberOptions: null,
    ready: function () {
        AuctionChit.entryBlock = $("tbody#entry-block");
        AuctionChit.medicineOptions = $("select#medicine-options").html();
        AuctionChit.memberOptions = $("select#member-options").html();
        $("a#add-entry-row").click(AuctionChit.addRow);
        AuctionChit.entryBlock.on("blur", "input.entry-amount,input.entry-advance", AuctionChit.calculateBalance);
        //$("form#form-auction").submit(AuctionChit.validateForm);
        AuctionChit.entryBlock.on("click", "a.delete-entry-row", AuctionChit.deleteRow);
        $("button#confirm-submit").click(AuctionChit.confirmSubmit);
    },
    
    deleteRow: function () {
        if (1 === AuctionChit.entryBlock.find("tr").length) {
            AuctionChit.showMessage("At least one member entry is required");
            return false;
        }
        
        $(this).parents("tr:first").remove();
        AuctionChit.reNumber();
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
    
    calculateBalance: function () {
        var row = $(this).parents("tr:first");
        var amount = parseInt(row.find("input.entry-amount").val());
        var advance = parseInt(row.find("input.entry-advance").val());
        if (advance < amount) {
            row.find("input.entry-balance").val(amount - advance);
        } else {
            row.find("input.entry-balance").val(0);
        }
        
        return false;
    },
    
    addRow: function () {
        var entryCount = AuctionChit.entryBlock.find("tr").length;
        ++entryCount;
        var row = '<tr><td class="entry-number">' + entryCount + '</td>'
                + '<td><select name="member[]" class="form-control entry-member" title="Member">'
                + AuctionChit.memberOptions + '</select></td>'
                + '<td><select name="medicine[]" class="form-control entry-medicine" title="Medicine">'
                + AuctionChit.medicineOptions + '</select></td>'
                + '<td><input type="number" name="amount[]" class="form-control entry-amount" placeholder="Amount" min="1" max="999999" step="1" required="required"></td>'
                + '<td><input type="number" name="quantity[]" class="form-control entry-quantity" value="0" placeholder="Weight" min="1" max="9999" step="1" required="required"></td>'
                + '<td><input type="number" name="advance[]" class="form-control entry-advance" value="0" placeholder="Advance Paid" min="0" max="999999" step="1" required="required"></td>'
                + '<td><input type="number" name="balance[]" class="form-control entry-balance" value="0" placeholder="Balance" readonly="readonly"></td>'
                + '<td><a href="javascript:;" class="btn btn-sm btn-outline-danger delete-entry-row" title="Delete"><i class="ft-x-square"></i> Delete</a></td></tr>';
        
        AuctionChit.entryBlock.append(row);
    },
    
    validateForm: function () {
        AuctionChit.clearMessage();
        if (AuctionChit.notSelected($("select#donor_id").val())) {
            AuctionChit.showMessage("Donor not selected");
            return false;
        }
        
        if (0 === AuctionChit.entryBlock.find("tr").length) {
            AuctionChit.showMessage("No member entry added");
            return false;
        }
        
        var valid = true;
        var entryNumber = 0;
        AuctionChit.entryBlock.find("tr").each(function () {
                var row = $(this);
                ++entryNumber;
                if (AuctionChit.notSelected(row.find("select.entry-member").val())) {
                    AuctionChit.showMessage("Entry " + entryNumber + ": Member not selected");
                    valid = false;
                    return false;
                }
                if (AuctionChit.notSelected(row.find("select.entry-medicine").val())) {
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
                
                if (! AuctionChit.isAdvance(row.find("input.entry-advance").val())) {
                    AuctionChit.showMessage("Entry " + entryNumber + ": Invalid Advance");
                    valid = false;
                    return false;
                }
                
                if (parseInt(row.find("input.entry-advance").val()) > parseInt(row.find("input.entry-amount").val())) {
                    AuctionChit.showMessage("Entry " + entryNumber + ": Advance is more than Auction Amount");
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
    },
    
    isAdvance: function (value) {
        if ("" === value) {
            return false;
        }
        
        if (isNaN(value)) {
            return false;
        }
        
        if ("0" === value) {
            return true;
        }
        
        return /^[1-9]\d*$/.test(value);   
    },
    
    confirmSubmit: function () {
        if (! confirm("Proceed with submitting entered data?")) {
            return false;
        }
        
        if (! AuctionChit.validateForm()) {
            return false;
        }
        
        $("form#form-auction").get(0).submit();
    }
};

$(document).ready(function () {
        AuctionChit.ready();
    }
);
