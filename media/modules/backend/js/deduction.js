"use strict";

var Deduction = {
    blankOptions: [],
    ready: function () {
        Deduction.blankOptions = $("input#blank-options").val().split(",");
        $("form#form-deduction select").change(Deduction.setValueOptions);
    },
    
    setValueOptions: function () {
        var selectedOption = $(this).val();
        var targetInput = $(this).parents("tr:first").find("input[type=text]");
        if (Deduction.blankOptions.includes(selectedOption)) {
            targetInput.prop("required", false).val("");
        } else {
            targetInput.prop("required", true);
        }
        
        return false;
    }
    
};

$(document).ready(function () {
        Deduction.ready();
    }
);
