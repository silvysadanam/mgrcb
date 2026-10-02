"use strict";

var MembershipChange = {
    formModal : null,
    dummyData : { id : 0 },
    actions : {
        get_member : 1, save_member : 2, get_card : 3, save_card : 4,
        remove_card : 5, get_boat : 6, save_boat : 7, remove_boat : 8,
        get_document : 9, save_document_with_file : 10, 
        save_document_without_file : 11, remove_document : 12, 
        save_photo : 13, get_account : 14, save_account : 15, 
        remove_account : 16, set_password : 17,
        disable : 18, activate : 19
    },
    requiredSymbol : ' <span class="red">*</span>',
    ready: function () {
        $("a#edit-member").click(MembershipChange.editMember);
        $("form#form-member-modal").submit(MembershipChange.saveMember);
        
        $("a#add-card").click(MembershipChange.addCard);
        $("a.edit-card").click(MembershipChange.editCard);
        $("form#form-card-modal").submit(MembershipChange.saveCard);
        $("a.delete-card").click(MembershipChange.deleteCard);
        
        $("a#add-boat").click(MembershipChange.addBoat);
        $("a.edit-boat").click(MembershipChange.editBoat);
        $("form#form-boat-modal").submit(MembershipChange.saveBoat);
        $("a.delete-boat").click(MembershipChange.deleteBoat);
        
        $("a#add-document").click(MembershipChange.addDocument);
        $("a.edit-document").click(MembershipChange.editDocument);
        $("form#form-document-modal").submit(MembershipChange.saveDocument);
        $("a.delete-document").click(MembershipChange.deleteDocument);
        
        $("a#upload-photo").click(MembershipChange.photoForm);
        $("form#form-photo-modal").submit(MembershipChange.uploadPhoto);
        
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
       
        $("a#add-account").click(MembershipChange.addAccount);
        $("a.edit-account").click(MembershipChange.editAccount);
        $("form#form-account-modal").submit(MembershipChange.saveAccount);
        $("a.delete-account").click(MembershipChange.deleteAccount);
        
        $("a#edit-password").click(MembershipChange.editPassword);
        $("form#form-password-modal").submit(MembershipChange.savePassword);
        
        $("a#disable-membership").click(MembershipChange.showDisable);
        $("a#enable-membership").click(MembershipChange.enableMembership);
         $("form#form-disable-modal").submit(MembershipChange.disableMembership);
        
    },
    
    editMember: function () {
        $.post(MembershipChange.getUrl(MembershipChange.actions.get_member), MembershipChange.dummyData, function(reply) {
                if (reply.success && reply.valid) {
                    MembershipChange.setModal("modal-member");
                    MembershipChange.formModal.find("input#name").val(reply.result.name);
                    MembershipChange.formModal.find("input#code").val(reply.result.code);
                    MembershipChange.formModal.find("input#email").val(reply.result.email);
                    MembershipChange.formModal.find("input#mobile").val(reply.result.mobile);
                    MembershipChange.formModal.find("input#aadhar_number").val(reply.result.aadhar_number);
                    MembershipChange.formModal.find("input#pan_number").val(reply.result.pan_number);
                    MembershipChange.formModal.find("input#parish").val(reply.result.parish);
                    MembershipChange.formModal.find("input#landline").val(reply.result.landline);
                    MembershipChange.formModal.find("input#whatsapp").val(reply.result.whatsapp);
                    MembershipChange.formModal.find("input#location").val(reply.result.location);
                    MembershipChange.formModal.find("input#membership_fee").val(reply.result.membership_fee);
                    MembershipChange.formModal.find("textarea#address").val(reply.result.address);
                    MembershipChange.formModal.find("input#approved_date").val(reply.result.approved_date);
                    MembershipChange.formModal.find("select#member_type_id").val(reply.result.member_type_id);
                    MembershipChange.formModal.find("select#membership_type_id").val(reply.result.membership_type_id);
                    MembershipChange.formModal.find("input#date_of_birth").val(reply.result.date_of_birth);
                } else if (reply.redirect) {
                    BackendCommon.reloadPage();
                } else if (reply.message) {
                    BackendCommon.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );

        return false;
    },
    
    saveMember: function () {
        MembershipChange.clearMessage();
        var formData = {
            name : MembershipChange.formModal.find("input#name").val(),
            code : MembershipChange.formModal.find("input#code").val(),
            mobile : MembershipChange.formModal.find("input#mobile").val(),
            aadhar_number : MembershipChange.formModal.find("input#aadhar_number").val(),
            pan_number : MembershipChange.formModal.find("input#pan_number").val(),
            parish : MembershipChange.formModal.find("input#parish").val(),
            landline : MembershipChange.formModal.find("input#landline").val(),
            email : MembershipChange.formModal.find("input#email").val(),
            whatsapp : MembershipChange.formModal.find("input#whatsapp").val(),
            address : MembershipChange.formModal.find("textarea#address").val(),
            membership_fee : MembershipChange.formModal.find("select#membership_fee").val(),
            member_type_id : MembershipChange.formModal.find("select#member_type_id").val(),
            membership_type_id : MembershipChange.formModal.find("select#membership_type_id").val(),
            date_of_birth : MembershipChange.formModal.find("input#date_of_birth").val(),
            approved_date : MembershipChange.formModal.find("input#approved_date").val(),
            location : MembershipChange.formModal.find("input#location").val()
        };
        
        $.post(MembershipChange.getUrl(MembershipChange.actions.save_member), formData, function(reply) {
                if (reply.success && reply.valid) {
                    BackendCommon.reloadPage();
                } else if (reply.redirect) {
                    BackendCommon.reloadPage();
                } else if (reply.message) {
                    MembershipChange.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );

        return false;
    },
    
    addCard: function () {
        MembershipChange.setModal("modal-card");
        MembershipChange.formModal.find("h3#myModalLabel2").text("Add Card Number");
        MembershipChange.formModal.find("input[type=text]").val("");
        MembershipChange.formModal.find("select#card_type_id").val(0);
        MembershipChange.formModal.find("input#card-id").val(0);
        
        return false;
    },
    
    editCard: function () {
        var cardId = $(this).data("id");
        var formData = { id : cardId };
        $.post(MembershipChange.getUrl(MembershipChange.actions.get_card), formData, function(reply) {
                if (reply.success && reply.valid) {
                    MembershipChange.setModal("modal-card");
                    MembershipChange.formModal.find("h3#myModalLabel2").text("Edit Card Number");
                    MembershipChange.formModal.find("input#card_number").val(reply.result.card_number);
                    MembershipChange.formModal.find("input#issue_date").val(reply.result.issue_date);
                    MembershipChange.formModal.find("input#expiry_date").val(reply.result.expiry_date);
                    MembershipChange.formModal.find("select#card_type_id").val(reply.result.card_type_id);
                    MembershipChange.formModal.find("input#card-id").val(cardId);
                } else if (reply.redirect) {
                    BackendCommon.reloadPage();
                } else if (reply.message) {
                    BackendCommon.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );

        return false;
    },
    
    saveCard: function () {
        MembershipChange.clearMessage();
        if ("0" === MembershipChange.formModal.find("select#card_type_id").val()) {
            MembershipChange.showMessage("Type not selected");
            return false;
        }
        
        var formData = {
            id : MembershipChange.formModal.find("input#card-id").val(),
            card_type_id : MembershipChange.formModal.find("select#card_type_id").val(),
            card_number : MembershipChange.formModal.find("input#card_number").val(),
            issue_date : MembershipChange.formModal.find("input#issue_date").val(),
            expiry_date : MembershipChange.formModal.find("input#expiry_date").val()
        };
        
        $.post(MembershipChange.getUrl(MembershipChange.actions.save_card), formData, function(reply) {
                if (reply.success && reply.valid) {
                    BackendCommon.reloadPage();
                } else if (reply.redirect) {
                    BackendCommon.reloadPage();
                } else if (reply.message) {
                    MembershipChange.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );

        return false;
    },
    
    deleteCard: function () {
        if (confirm("Are you sure you to delete this Card Number?")) {
            var cardId = $(this).data("id");
            $.post(MembershipChange.getUrl(MembershipChange.actions.remove_card), { id : cardId }, function(reply) {
                    if (reply.success && reply.valid) {
                        BackendCommon.reloadPage();
                    } else if (reply.redirect) {
                        BackendCommon.reloadPage();
                    } else if (reply.message) {
                        MembershipChange.showMessage(reply.message);
                    } else {
                        alert("Unable to complete the request.");
                    }
                }, "json"
            );
        }
        
        return false;
    },
    
    
    
    addDocument : function () {
        MembershipChange.setModal("modal-document");
        MembershipChange.formModal.find("h3#myModalLabel4").text("Add Document");
        MembershipChange.formModal.find("input#description").val("");
        MembershipChange.formModal.find("select").val(0);
        MembershipChange.formModal.find("input#document-id").val(0);
        MembershipChange.formModal.find("input#document_file").prop("required", true).val("");
        $("div#document-field").find("label:first").html("Upload file" + MembershipChange.requiredSymbol);
    },
    
    editDocument: function () {
        var documentId = $(this).data("id");
        var formData = { id : documentId };
        $.post(MembershipChange.getUrl(MembershipChange.actions.get_document), formData, function(reply) {
                if (reply.success && reply.valid) {
                    MembershipChange.setModal("modal-document");
                    MembershipChange.formModal.find("h3#myModalLabel4").text("Edit Document");
                    MembershipChange.formModal.find("input#description").val(reply.result.description);
                    MembershipChange.formModal.find("select#document_type_id").val(reply.result.document_type_id);
                    MembershipChange.formModal.find("input#document-id").val(documentId);
                    MembershipChange.formModal.find("input#document_file").prop("required", false).val("");
                    $("div#document-field").find("label:first").html("Replace file (optional)");
                } else if (reply.redirect) {
                    BackendCommon.reloadPage();
                } else if (reply.message) {
                    MembershipChange.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );

        return false;
    },
    
    saveDocument: function () {
        MembershipChange.clearMessage();
        if ("0" === MembershipChange.formModal.find("select#document_type_id").val()) {
            MembershipChange.showMessage("Docuemnt Type not selected");
            return false;
        }
        
        var documentId = MembershipChange.formModal.find("input#document-id").val();
        var fileField = $("input#document_file");
        var fileTitle = "Document";
        if (fileField.get(0).files.length > 0) {
            if (! DocumentValidator.validate(fileField)) {
                MembershipChange.showMessage(DocumentValidator.getMessage(fileTitle));
                fileField.val("");
                return false;
            }
        }
        
        var fileData = new FormData();
        if (fileField.get(0).files.length > 0) {
            fileData.append("document", fileField.get(0).files[0]);
            fileData.append("document_id", documentId);
            fileData.append("document_type_id", MembershipChange.formModal.find("select#document_type_id").val());
            fileData.append("description", MembershipChange.formModal.find("input#description").val());
            $.ajax({
                    url : MembershipChange.getUrl(MembershipChange.actions.save_document_with_file),
                    method: "POST",
                    data: fileData,
                    contentType: false,
                    cache: false,
                    processData: false,
                    success: function(reply) {
                        if (reply.valid) {
                            BackendCommon.reloadPage();
                        } else {
                            MembershipChange.showMessage(reply.message);
                        }
                    }
                }
            );
        } else {
            var formData = {
                id : documentId,
                document_type_id : MembershipChange.formModal.find("select#document_type_id").val(),
                description : MembershipChange.formModal.find("input#description").val()
            };

            $.post(MembershipChange.getUrl(MembershipChange.actions.save_document_without_file), formData, function(reply) {
                    if (reply.success && reply.valid) {
                        BackendCommon.reloadPage();
                    } else if (reply.redirect) {
                        BackendCommon.reloadPage();
                    } else if (reply.message) {
                        MembershipChange.showMessage(reply.message);
                    } else {
                        alert("Unable to complete the request.");
                    }
                }, "json"
            );
        }
        
        fileField.val("");
        
        return false;
    },
    
    deleteDocument: function () {
        if (confirm("Are you sure you to delete this Document?")) {
            var documentId = $(this).data("id");
            $.post(MembershipChange.getUrl(MembershipChange.actions.remove_document), { id : documentId }, function(reply) {
                    if (reply.success && reply.valid) {
                        BackendCommon.reloadPage();
                    } else if (reply.redirect) {
                        BackendCommon.reloadPage();
                    } else if (reply.message) {
                        MembershipChange.showMessage(reply.message);
                    } else {
                        alert("Unable to complete the request.");
                    }
                }, "json"
            );
        }
        
        return false;
    },
    
    photoForm: function () {
        MembershipChange.setModal("modal-photo");
        MembershipChange.formModal.find("input[type=file]").val("");
        
        return false;
    },
    
    uploadPhoto: function () {
        MembershipChange.clearMessage();
        var fileField = $("input#photo_file");
        var fileTitle = "Photo";
        if (fileField.get(0).files.length > 0) {
            if (! PhotoValidator.validate(fileField)) {
                MembershipChange.showMessage(PhotoValidator.getMessage(fileTitle));
                fileField.val("");
                return false;
            }
        } else {
            return false;
        }
        
        var fileData = new FormData();
        if (fileField.get(0).files.length > 0) {
            fileData.append("photo", fileField.get(0).files[0]);
            $.ajax({
                    url : MembershipChange.getUrl(MembershipChange.actions.save_photo),
                    method: "POST",
                    data: fileData,
                    contentType: false,
                    cache: false,
                    processData: false,
                    success: function(reply) {
                        if (reply.valid) {
                            BackendCommon.reloadPage();
                        } else {
                            MembershipChange.showMessage(reply.message);
                        }
                    }
                }
            );
        }
        
        fileField.val("");
        return false;
    },
    
    addAccount: function () {
        MembershipChange.setModal("modal-account");
        MembershipChange.formModal.find("h3#myModalLabel6").text("Add Bank Account");
        MembershipChange.formModal.find("input[type=text]").val("");
        MembershipChange.formModal.find("select").val(0);
        MembershipChange.formModal.find("input#account-id").val(0);
        
        return false;
    },
    
    editAccount: function () {
        var accountId = $(this).data("id");
        var formData = { id : accountId };
        $.post(MembershipChange.getUrl(MembershipChange.actions.get_account), formData, function(reply) {
                if (reply.success && reply.valid) {
                    MembershipChange.setModal("modal-account");
                    MembershipChange.formModal.find("h3#myModalLabel6").text("Edit Bank Account");
                    MembershipChange.formModal.find("input#account_number").val(reply.result.account_number);
                    MembershipChange.formModal.find("select#bank_id").val(reply.result.bank_id);
                    MembershipChange.formModal.find("select#account_type_id").val(reply.result.account_type_id);
                    MembershipChange.formModal.find("input#description2").val(reply.result.description);
                    MembershipChange.formModal.find("input#account-id").val(accountId);
                } else if (reply.redirect) {
                    BackendCommon.reloadPage();
                } else if (reply.message) {
                    MembershipChange.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );

        return false;
    },
    
    saveAccount: function () {
        MembershipChange.clearMessage();
        if ("0" === MembershipChange.formModal.find("select#bank_id").val()) {
            MembershipChange.showMessage("Bank not selected");
            return false;
        }
        
        if ("0" === MembershipChange.formModal.find("select#account_type_id").val()) {
            MembershipChange.showMessage("Account type not selected");
            return false;
        }
        
        var formData = {
            id : MembershipChange.formModal.find("input#account-id").val(),
            bank_id : MembershipChange.formModal.find("select#bank_id").val(),
            account_number : MembershipChange.formModal.find("input#account_number").val(),
            account_type_id : MembershipChange.formModal.find("select#account_type_id").val(),
            description : MembershipChange.formModal.find("input#description2").val()
        };
        
        $.post(MembershipChange.getUrl(MembershipChange.actions.save_account), formData, function(reply) {
                if (reply.success && reply.valid) {
                    BackendCommon.reloadPage();
                } else if (reply.redirect) {
                    BackendCommon.reloadPage();
                } else if (reply.message) {
                    MembershipChange.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );

        return false;
    },
    
    deleteAccount: function () {
        if (confirm("Are you sure you to delete this Bank Account?")) {
            var accountId = $(this).data("id");
            $.post(MembershipChange.getUrl(MembershipChange.actions.remove_account), { id : accountId }, function(reply) {
                    if (reply.success && reply.valid) {
                        BackendCommon.reloadPage();
                    } else if (reply.redirect) {
                        BackendCommon.reloadPage();
                    } else if (reply.message) {
                        MembershipChange.showMessage(reply.message);
                    } else {
                        alert("Unable to complete the request.");
                    }
                }, "json"
            );
        }
        
        return false;
    },
    
    editPassword: function () {
        var title = $(this).attr("title");
        MembershipChange.setModal("modal-password");
        MembershipChange.formModal.find("h3#myModalLabel7").text(title);
        MembershipChange.formModal.find("input[type=password]").val("");
        
        return false;
    },
    
    savePassword: function () {
        MembershipChange.clearMessage();
        var passwordFirst = MembershipChange.formModal.find("input#password1").val();
        var passwordRepeat = MembershipChange.formModal.find("input#password2").val();
        if ("" === passwordFirst) {
            MembershipChange.showMessage("Password not entered");
            return false;
        }
        
        if ("" === passwordRepeat) {
            MembershipChange.showMessage("Repeat Password not entered");
            return false;
        }
        
        if (passwordRepeat !== passwordFirst) {
            MembershipChange.showMessage("Password and Repeat password are not the same");
            return false;
        }
        
        var passwordLength = passwordFirst.length;
        
        if (passwordLength < 6 || passwordLength > 20) {
            MembershipChange.showMessage("Password should contain 6 - 20 characters");
            return false;
        }
        
        if (/\s+/.test(passwordFirst)) {
            MembershipChange.showMessage("Password should not contain space");
            return false;
        }
        
        
        var formData = { password1 : passwordFirst, password2 : passwordRepeat };
        
        $.post(MembershipChange.getUrl(MembershipChange.actions.set_password), formData, function(reply) {
                if (reply.success && reply.valid) {
                    BackendCommon.reloadPage();
                } else if (reply.redirect) {
                    BackendCommon.reloadPage();
                } else if (reply.message) {
                    MembershipChange.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );

        return false;
    },
    
    
    getUrl: function (action) {
        return $("input#manage-url").val() + "/" + action;
    },
    
    setModal: function (name) {
        MembershipChange.formModal = $("div#" + name);
        MembershipChange.formModal.modal("show");
        MembershipChange.formModal.find("div.alert").hide();
    },
    
    closeModal: function () {
        MembershipChange.formModal.modal("hide");
    },
    
    showMessage: function (message) {
        MembershipChange.formModal.find("div.alert").html(message).show();
    },
    
    clearMessage: function () {
        MembershipChange.formModal.find("div.alert").hide();
    },
    
    enableMembership: function () {
        if (confirm("Are you sure you to activate this member?")) {
            var formData = { id : 0 };
            $.post(MembershipChange.getUrl(MembershipChange.actions.activate), formData, function(reply) {
                    if (reply.success && reply.valid) {
                        BackendCommon.reloadPage();
                    } else if (reply.redirect) {
                        BackendCommon.reloadPage();
                    } else if (reply.message) {
                        MembershipChange.showMessage(reply.message);
                    } else {
                        alert("Unable to complete the request.");
                    }
                }, "json"
            );
        }
        
        return false;
    },
    
    showDisable: function () {
        if (confirm("Are you sure you to disable this member?")) {
            MembershipChange.setModal("modal-disable");
            MembershipChange.formModal.find("input#inactive_note").val("");
        }
        
        return false;
    },
    
    disableMembership: function () {
        var formData = {
            id : MembershipChange.formModal.find("input#inactive_date").val(),
            note : MembershipChange.formModal.find("input#inactive_note").val()
        };
        
        $.post(MembershipChange.getUrl(MembershipChange.actions.disable), formData, function(reply) {
                if (reply.success && reply.valid) {
                    //BackendCommon.reloadPage();
                } else if (reply.redirect) {
                    BackendCommon.reloadPage();
                } else if (reply.message) {
                    MembershipChange.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );

        return false;
    }
    
};

$(document).ready(function () {
        MembershipChange.ready();
    }
);
