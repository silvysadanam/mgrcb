"use strict";

var Beneficiary = {
    ready: function () {
        $("input#date_of_birth").daterangepicker({
            timePicker: false,
            singleDatePicker: true,
            autoUpdateInput: false,
            showDropdowns: true,
            minDate: $("input#start-date").val(),
            maxDate: $("input#end-date").val(),
            locale: {
                format: BackendCommon.dateFormat,
                cancelLabel: "Clear"
            }
        });
        $("select.select2").select2({ width: "100%" });
        Beneficiary.entryBlock = $("tbody#entry-block");
        $("a#add-entry-row").click(Beneficiary.addRow);
        Beneficiary.entryBlock.on("click", "a.delete-entry-row", Beneficiary.deleteRow);
        Beneficiary.entryBlock.on("blur", "input[type=number]", Beneficiary.reCalculate);
        $("form#form-member").submit(Beneficiary.validateForm);
        
        $("input#date_of_birth").on('apply.daterangepicker', function(ev, picker) {
              $(this).val(picker.startDate.format(picker.locale.format));
        });

        $("input#date_of_birth").on('cancel.daterangepicker', function(ev, picker) {
            $(this).val("");
        });
        
        $("a#clear-date-of-birth").click(function () {
                $("input#date_of_birth").val("");
            }
        );

        $("input#approved_date").daterangepicker({
            timePicker: false,
            singleDatePicker: true,
            autoUpdateInput: false,
            showDropdowns: true,
            minDate: $("input#start-date").val(),
            maxDate: $("input#end-date").val(),
            locale: {
                format: BackendCommon.dateFormat,
                cancelLabel: "Clear"
            }
        });
        
        $("input#approved_date").on('apply.daterangepicker', function(ev, picker) {
              $(this).val(picker.startDate.format(picker.locale.format));
        });

        $("input#approved_date").on('cancel.daterangepicker', function(ev, picker) {
            $(this).val("");
        });
        
        $("a#clear-approved_date").click(function () {
                $("input#approved_date").val("");
            }
        );

        $("input#examination_date").daterangepicker({
            timePicker: false,
            singleDatePicker: true,
            autoUpdateInput: false,
            showDropdowns: true,
            minDate: $("input#start-date").val(),
            maxDate: $("input#end-date").val(),
            locale: {
                format: BackendCommon.dateFormat,
                cancelLabel: "Clear"
            }
        });
        
        $("input#examination_date").on('apply.daterangepicker', function(ev, picker) {
              $(this).val(picker.startDate.format(picker.locale.format));
        });

        $("input#examination_date").on('cancel.daterangepicker', function(ev, picker) {
            $(this).val("");
        });
        
        $("a#clear-examination_date").click(function () {
                $("input#examination_date").val("");
            }
        );

$("input#proposed_date").daterangepicker({
            timePicker: false,
            singleDatePicker: true,
            autoUpdateInput: false,
            showDropdowns: true,
            minDate: $("input#start-date").val(),
            maxDate: $("input#end-date").val(),
            locale: {
                format: BackendCommon.dateFormat,
                cancelLabel: "Clear"
            }
        });
        
        $("input#proposed_date").on('apply.daterangepicker', function(ev, picker) {
              $(this).val(picker.startDate.format(picker.locale.format));
        });

        $("input#proposed_date").on('cancel.daterangepicker', function(ev, picker) {
            $(this).val("");
        });
        
        $("a#clear-proposed_date").click(function () {
                $("input#proposed_date").val("");
            }
        );
    },
    
     addRow: function () {
        var entryCount = Beneficiary.entryBlock.find("tr").length;
        ++entryCount;
        var row = '<tr><td class="entry-number">' + entryCount + '</td>'
                + '<td><input type="text" name="family_name[]" class="form-control family_name" placeholder="പേര്" step="1" required="required"></td>'
                + '<td><input type="text" name="family_relation[]" class="form-control family_relation"  placeholder="ബന്ധം" step="1" required="required"></td>'
                + '<td><input type="text" name="family_category[]" class="form-control family_category" placeholder="വിഭാഗം" step="1" required="required"></td>'
                + '<td><input type="text" name="family_age[]" class="form-control family_age" placeholder="വയസ്സ്" step="1" required="required"></td>'
                + '<td><input type="text" name="family_education[]" class="form-control family_education" placeholder="വിദ്യാഭ്യാസം" step="1"></td>'
                + '<td><input type="text" name="family_disability[]" class="form-control family_disability" placeholder="വികലാംഗത" step="1"></td>'
                + '<td><input type="text" name="family_occupation[]" class="form-control family_occupation" placeholder="തൊഴിൽ" step="1"></td>'
                + '<td><input type="text" name="family_income[]" class="form-control family_income" placeholder="മാസവരുമാനം" step="1"></td>'
                + '<td><a href="javascript:;" class="btn btn-sm btn-outline-danger delete-entry-row" title="Delete"><i class="ft-x-square"></i> Delete</a>'
                + '</td></tr>';
        Beneficiary.entryBlock.append(row);
    },
    
    deleteRow: function () {
        if (1 === Beneficiary.entryBlock.find("tr").length) {
            Beneficiary.showMessage("At least one entry is required");
            return false;
        }
        
        $(this).parents("tr:first").remove();
        Beneficiary.reNumber();
        Beneficiary.reCalculate();
    },
    
    reNumber: function () {
        if (0 === Beneficiary.entryBlock.find("tr").length) {
            return;
        }
        
        var counter = 0;
        Beneficiary.entryBlock.find("tr").each(function () {
                ++counter;
                $(this).find("td:first").text(counter);
            }
        );
    },
    
    reCalculate: function () {
        var amount = 0;
        var quantity = 0;
        if (Beneficiary.entryBlock.find("tr").length > 0) {
            Beneficiary.entryBlock.find("input.entry-amount").each(function () {
                    var itemAmount = $(this).val();
                    if ("" !== itemAmount) {
                        amount += parseInt(itemAmount);
                    }
                }
            );
    
            Beneficiary.entryBlock.find("input.entry-quantity").each(function () {
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
        Beneficiary.clearMessage();
        if (Beneficiary.notSelected($("select#donor_id").val())) {
            Beneficiary.showMessage("Donor not selected");
            return false;
        }
        
        if (Beneficiary.notSelected($("select#member_id").val())) {
            Beneficiary.showMessage("Member not selected");
            return false;
        }
        
        var advance = $("input#advance").val();
        if ("" !== advance) {
            if (isNaN(advance)) {
                Beneficiary.showMessage("Advance is invalid");
                return false;
            }
            
            if ("0" !== advance) {
                if (! Beneficiary.isAmount(advance)) {
                    Beneficiary.showMessage("Advance is invalid");
                    return false;
                }
            }
        }
        
        if (0 === Beneficiary.entryBlock.find("tr").length) {
            Beneficiary.showMessage("No entry added");
            return false;
        }
        
        var valid = true;
        var entryNumber = 0;
        Beneficiary.entryBlock.find("tr").each(function () {
                var row = $(this);
                ++entryNumber;
                if (Beneficiary.notSelected(row.find("select:first").val())) {
                    Beneficiary.showMessage("Entry " + entryNumber + ": Medicine not selected");
                    valid = false;
                    return false;
                }
                
                if (! Beneficiary.isAmount(row.find("input.entry-amount").val())) {
                    Beneficiary.showMessage("Entry " + entryNumber + ": Invalid Amount");
                    valid = false;
                    return false;
                }
                
                if (! Beneficiary.isQuantity(row.find("input.entry-quantity").val())) {
                    Beneficiary.showMessage("Entry " + entryNumber + ": Invalid weight");
                    valid = false;
                    return false;
                }
            }
        );
        
        if (! valid) {
            return false;
        }
        
        if (advance > parseInt($("input#total-amount").val())) {
             Beneficiary.showMessage("Advance is more than total amount");
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
        
        return ! Beneficiary.isAmount(value);        
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
        Beneficiary.ready();
    }
);
