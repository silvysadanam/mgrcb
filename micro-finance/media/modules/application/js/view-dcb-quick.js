"use strict";

var ViewDcb = {
    ready: function () {
        $("form#form-quick-find").submit(ViewDcb.loadResult);
    },
    
    loadResult: function () {
        ViewDcb.clearMessage();
        $("section#search-result-block").hide();
        var search_item = $("select#search_item").val();
        var search_value = $("input#search_value").val();
        if (2 == search_item) {
            if (! /^[1-9]\d*$/.test(search_value)) {
                ViewDcb.showMessage("Invalid DCB ID");
                return false;
            }
        } else if (/\s/.test(search_value)) {
            ViewDcb.showMessage("Invalid Search input");
            return false;
        }
        
        var formData = { id : search_item, search : search_value };
        var formAction = $("input#get-search-url").val();
        $.post(formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    if ($("select#search_action").val() > 0) {
                         $("div#search-message").text(reply.message);
                        $("section#search-result-block").find("tbody:first").html(reply.html);
                        $("section#search-result-block").show();
                    } else {
                       window.location = $("input#view-dcb-url").val() + reply.dcb_id;
                    }
                } else if (reply.redirect) {
                    ApplicationCommon.reloadPage();
                } else if (reply.message) {
                    ViewDcb.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );
        
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
    }
};

$(document).ready(function () {
        ViewDcb.ready();
    }
);
