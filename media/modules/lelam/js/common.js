"use strict";

var LelamCommon = {
    dateFormat: "DD/MM/YYYY",
    dateTimeFormat: "DD/MM/YYYY hh:mm A",
    timeFormat: "hh:mm A",
    
    ready: function() {
        LelamCommon.confirmRemove();
        LelamCommon.cancelButton();
        LelamCommon.numberDecimal();
        LelamCommon.numberInteger();
        LelamCommon.singleDatepicker();
        LelamCommon.removeItem();
        $.ajaxSetup({
            headers: { "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content") }
        }); 
    },
    
    confirmRemove: function() {
        $("a.confirm-remove").click(function() {
                return confirm("Are you sure you want to delete this " + $(this).data("confirm") + "?");
            }
        );
    },
    
    cancelButton: function() {
        $("button.form-button-cancel").click(function() {
                window.location = $(this).data("cancel");
            }
        );
    },
    
    reloadPage: function() {
        window.location.reload(true);
    }, 
    
    numberDecimal: function() {
        $("input[type='text'].number-decimal").on("keypress", function(e) {
            if (e.ctrlKey) {
                return;
            }
            if (e.key.length > 1) {
                return;
            }
            var code = (e.which) ? e.which : e.keyCode;
            if (code === 46 || (code > 47 && code < 58)) {
                var currentValue = $(this).val();
                if (code === 46) {
                    if ("" === currentValue) {
                        e.preventDefault();
                        return false;
                    }
                    if (-1 !== currentValue.indexOf(".")) {
                        e.preventDefault();
                        return false;
                    }
                } else if (code === 48) {
                    if ("0" === currentValue) {
                        e.preventDefault();
                        return false;
                    }
                }
            } else {
                e.preventDefault();
                return false;
            }
            return true;
        });
    },
    
    numberInteger: function() {
        $("input[type='text'].number-integer").on("keypress", function(e) {
            if (e.ctrlKey) {
                return;
            }
            if (e.key.length > 1) {
                return;
            }
            var code = (e.which) ? e.which : e.keyCode;
            var currentValue = $(this).val();
            if (code > 47 && code < 58) {
                if (code === 48) {
                    if ("0" === currentValue) {
                        e.preventDefault();
                        return false;
                    }
                }
            } else {
                e.preventDefault();
                return false;
            }
            return true;
        });
        
        $("input[type='number'].number-integer").on("keypress", function(e) {
            if (e.ctrlKey) {
                return;
            }
            if (e.key.length > 1) {
                return;
            }
            var maxLength = $(this).attr("maxlength");
            var currentValue = $(this).val();
            if (maxLength) {
                if (currentValue.length >= maxLength) {
                    e.preventDefault();
                    return false;
                }
            }
            return true;
        });
    },
    
    setDateFormat: function (format) {
        LelamCommon.dateFormat = format;
    },
    
    getDatepickerTimestamp: function (dateValue) {
        var dateParts,dateTime;
        switch (LelamCommon.dateFormat) {
            case "DD/MM/YYYY":
                dateParts = dateValue.split("/");
                dateTime = new Date(dateParts[2] + "-" + dateParts[1] + "-" + dateParts[0]);
                break;
            
            case "MM/DD/YYYY":
                dateParts = dateValue.split("/");
                dateTime = new Date(dateParts[2] + "-" + dateParts[0] + "-" + dateParts[1]);
                break;
                
            case "DD-MM-YYYY":
                dateParts = dateValue.split("-");
                dateTime = new Date(dateParts[2] + "-" + dateParts[1] + "-" + dateParts[0]);
                break;
                
            default:
                dateTime = new Date(dateValue);
                break;
        }
        
        return dateTime.getTime();
    },
    
    singleDatepicker: function () {        
        $("input.single-date-clearable").daterangepicker({
            timePicker: false,
            singleDatePicker: true,
            autoUpdateInput: false,
            showDropdowns: true,
            maxDate: moment(),
            locale: {
                format: LelamCommon.dateFormat,
                cancelLabel: "Clear"
            }
        });
        
        $("input.single-date-clearable").on('apply.daterangepicker', function(ev, picker) {
              $(this).val(picker.startDate.format(picker.locale.format));
        });

        $("input.single-date-clearable").on('cancel.daterangepicker', function(ev, picker) {
            $(this).val("");
        });
        
        $("input.single-date-clearable-future").daterangepicker({
            timePicker: false,
            singleDatePicker: true,
            autoUpdateInput: false,
            showDropdowns: true,
            locale: {
                format: LelamCommon.dateFormat,
                cancelLabel: "Clear"
            }
        });
        
        $("input.single-date-clearable-future").on('apply.daterangepicker', function(ev, picker) {
              $(this).val(picker.startDate.format(picker.locale.format));
        });

        $("input.single-date-clearable-future").on('cancel.daterangepicker', function(ev, picker) {
            $(this).val("");
        });
        
        $("input.single-date").daterangepicker({
            timePicker: false,
            singleDatePicker: true,
            showDropdowns: true,
            locale: {
                format: LelamCommon.dateFormat
            }
        });
        
        $("input.single-date-max-today").daterangepicker({
            timePicker: false,
            singleDatePicker: true,
            maxDate: moment(),
            showDropdowns: true,
            locale: {
                format: LelamCommon.dateFormat
            }
        });
        
        $("a.clear-date").click(function () {
            var targetInput = $(this).data("target");
            $("input#" + targetInput).val("");
        });
        
        $("input.single-datetime").daterangepicker({
            timePicker: true,
            singleDatePicker: true,
            showDropdowns: true,
            timePickerIncrement: 1,
            maxDate: moment(),
            locale: {
                format: LelamCommon.dateTimeFormat
            }
        });
    },
    
    removeItem: function () {
        $("a.remove-item").click(function () {
                var itemId = $(this).data("item-id");
                if (confirm("Are you sure you want to delete this " + $("input#remove-item-title").val() + "?")) {
                    var formAction = $("input#remove-item-url").val();
                    $.post(formAction, { id : itemId }, function(reply) {
                            if (reply.success && reply.valid) {
                                LelamCommon.reloadPage();
                            } else if (reply.redirect) {
                                LelamCommon.reloadPage();
                            } else if (reply.message) {
                                alert(reply.message);
                            }
                        }, "json"
                    );
                }
            }
        );
    }
    
};

$(document).ready(function() {
        LelamCommon.ready();
    }
);
