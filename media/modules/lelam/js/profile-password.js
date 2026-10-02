"use strict";

var Profile = {
    messageBlock: null,
    ready: function () {
        Profile.messageBlock = $("div#alidation-message");
        $("form#form-password-modal").submit(Profile.validateForm);
    },
    
    validateForm: function () {
        Profile.clearMessage();
        var currentPassword = $("input#profile_password1").val();
        var passwordFirst = $("input#profile_password2").val();
        var passwordRepeat = $("input#profile_password3").val();
        
        if (passwordRepeat !== passwordFirst) {
            Profile.showMessage("Password and Repeat password are not the same");
            return false;
        }
        
        var passwordLength = passwordFirst.length;
        
        if (passwordLength < 6 || passwordLength > 20) {
            Profile.showMessage("Password should contain 6 - 20 characters");
            return false;
        }
        
        if (/\s+/.test(passwordFirst)) {
            Profile.showMessage("Password should not contain space");
            return false;
        }
        
        if (currentPassword === passwordFirst) {
            Profile.showMessage("New password should be different from current password");
            return false;
        }

        return true;
    },
    
    showMessage: function (message) {
        Profile.messageBlock.html(message).show();
    },
    
    clearMessage: function () {
        Profile.messageBlock.hide();
    }
    
};

$(document).ready(function () {
        Profile.ready();
    }
);
