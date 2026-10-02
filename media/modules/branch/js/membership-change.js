"use strict";

var MembersipChange = {
    formModal : null,
    dummyData : { id : 0 },
    actions : {
        get_member : 1, save_member : 2, get_card : 3, save_card : 4,
        remove_card : 5, get_boat : 6, save_boat : 7, remove_boat : 8,
        get_document : 9, save_document_with_file : 10, 
        save_document_without_file : 11, remove_document : 12, 
        save_photo : 13, get_account : 14, save_account : 15, 
        remove_account : 16, set_password : 17
    },
    requiredSymbol : ' <span class="red">*</span>',
    ready: function () {
        $("a#edit-member").click(MembersipChange.editMember);
        $("form#form-member-modal").submit(MembersipChange.saveMember);
        
        $("a#add-card").click(MembersipChange.addCard);
        $("a.edit-card").click(MembersipChange.editCard);
        $("form#form-card-modal").submit(MembersipChange.saveCard);
        $("a.delete-card").click(MembersipChange.deleteCard);
        
        $("a#add-boat").click(MembersipChange.addBoat);
        $("a.edit-boat").click(MembersipChange.editBoat);
        $("form#form-boat-modal").submit(MembersipChange.saveBoat);
        $("a.delete-boat").click(MembersipChange.deleteBoat);
        
        $("a#add-document").click(MembersipChange.addDocument);
        $("a.edit-document").click(MembersipChange.editDocument);
        $("form#form-document-modal").submit(MembersipChange.saveDocument);
        $("a.delete-document").click(MembersipChange.deleteDocument);
        
        $("a#upload-photo").click(MembersipChange.photoForm);
        $("form#form-photo-modal").submit(MembersipChange.uploadPhoto);
        
         $("input#date_of_birth").daterangepicker({
            timePicker: false,
            singleDatePicker: true,
            autoUpdateInput: false,
            showDropdowns: true,
            minDate: $("input#start-date").val(),
            maxDate: $("input#end-date").val(),
            locale: {
                format: BranchCommon.dateFormat,
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

        $("a#add-account").click(MembersipChange.addAccount);
        $("a.edit-account").click(MembersipChange.editAccount);
        $("form#form-account-modal").submit(MembersipChange.saveAccount);
        $("a.delete-account").click(MembersipChange.deleteAccount);
        
        $("a#edit-password").click(MembersipChange.editPassword);
        $("form#form-password-modal").submit(MembersipChange.savePassword);
    },
    
    editMember: function () {
        $.post(MembersipChange.getUrl(MembersipChange.actions.get_member), MembersipChange.dummyData, function(reply) {
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
                    BranchCommon.reloadPage();
                } else if (reply.message) {
                    BranchCommon.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );

        return false;
    },
    
    saveMember: function () {
        MembersipChange.clearMessage();
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
        
        $.post(MembersipChange.getUrl(MembersipChange.actions.save_member), formData, function(reply) {
                if (reply.success && reply.valid) {
                    BranchCommon.reloadPage();
                } else if (reply.redirect) {
                    BranchCommon.reloadPage();
                } else if (reply.message) {
                    MembersipChange.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );

        return false;
    },
    
    addCard: function () {
        MembersipChange.setModal("modal-card");
        MembersipChange.formModal.find("h3#myModalLabel2").text("Add Card Number");
        MembersipChange.formModal.find("input[type=text]").val("");
        MembersipChange.formModal.find("select#card_type_id").val(0);
        MembersipChange.formModal.find("input#card-id").val(0);
        
        return false;
    },
    
    editCard: function () {
        var cardId = $(this).data("id");
        var formData = { id : cardId };
        $.post(MembersipChange.getUrl(MembersipChange.actions.get_card), formData, function(reply) {
                if (reply.success && reply.valid) {
                    MembersipChange.setModal("modal-card");
                    MembersipChange.formModal.find("h3#myModalLabel2").text("Edit Card Number");
                    MembersipChange.formModal.find("input#card_number").val(reply.result.card_number);
                    MembersipChange.formModal.find("input#issue_date").val(reply.result.issue_date);
                    MembersipChange.formModal.find("input#expiry_date").val(reply.result.expiry_date);
                    MembersipChange.formModal.find("select#card_type_id").val(reply.result.card_type_id);
                    MembersipChange.formModal.find("input#card-id").val(cardId);
                } else if (reply.redirect) {
                    BranchCommon.reloadPage();
                } else if (reply.message) {
                    BranchCommon.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );

        return false;
    },
    
    saveCard: function () {
        MembersipChange.clearMessage();
        if ("0" === MembersipChange.formModal.find("select#card_type_id").val()) {
            MembersipChange.showMessage("Type not selected");
            return false;
        }
        
        var formData = {
            id : MembersipChange.formModal.find("input#card-id").val(),
            card_type_id : MembersipChange.formModal.find("select#card_type_id").val(),
            card_number : MembersipChange.formModal.find("input#card_number").val(),
            issue_date : MembersipChange.formModal.find("input#issue_date").val(),
            expiry_date : MembersipChange.formModal.find("input#expiry_date").val()
        };
        
        $.post(MembersipChange.getUrl(MembersipChange.actions.save_card), formData, function(reply) {
                if (reply.success && reply.valid) {
                    BranchCommon.reloadPage();
                } else if (reply.redirect) {
                    BranchCommon.reloadPage();
                } else if (reply.message) {
                    MembersipChange.showMessage(reply.message);
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
            $.post(MembersipChange.getUrl(MembersipChange.actions.remove_card), { id : cardId }, function(reply) {
                    if (reply.success && reply.valid) {
                        BranchCommon.reloadPage();
                    } else if (reply.redirect) {
                        BranchCommon.reloadPage();
                    } else if (reply.message) {
                        MembersipChange.showMessage(reply.message);
                    } else {
                        alert("Unable to complete the request.");
                    }
                }, "json"
            );
        }
        
        return false;
    },
    
    
    
    addDocument : function () {
        MembersipChange.setModal("modal-document");
        MembersipChange.formModal.find("h3#myModalLabel4").text("Add Document");
        MembersipChange.formModal.find("input#description").val("");
        MembersipChange.formModal.find("select").val(0);
        MembersipChange.formModal.find("input#document-id").val(0);
        MembersipChange.formModal.find("input#document_file").prop("required", true).val("");
        $("div#document-field").find("label:first").html("Upload file" + MembersipChange.requiredSymbol);
    },
    
    editDocument: function () {
        var documentId = $(this).data("id");
        var formData = { id : documentId };
        $.post(MembersipChange.getUrl(MembersipChange.actions.get_document), formData, function(reply) {
                if (reply.success && reply.valid) {
                    MembersipChange.setModal("modal-document");
                    MembersipChange.formModal.find("h3#myModalLabel4").text("Edit Document");
                    MembersipChange.formModal.find("input#description").val(reply.result.description);
                    MembersipChange.formModal.find("select#document_type_id").val(reply.result.document_type_id);
                    MembersipChange.formModal.find("input#document-id").val(documentId);
                    MembersipChange.formModal.find("input#document_file").prop("required", false).val("");
                    $("div#document-field").find("label:first").html("Replace file (optional)");
                } else if (reply.redirect) {
                    BranchCommon.reloadPage();
                } else if (reply.message) {
                    BranchCommon.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );

        return false;
    },
    
    saveDocument: function () {
        MembersipChange.clearMessage();
        if ("0" === MembersipChange.formModal.find("select#document_type_id").val()) {
            MembersipChange.showMessage("Docuemnt Type not selected");
            return false;
        }
        
        var documentId = MembersipChange.formModal.find("input#document-id").val();
        var fileField = $("input#document_file");
        var fileTitle = "Document";
        if (fileField.get(0).files.length > 0) {
            if (! DocumentValidator.validate(fileField)) {
                MembersipChange.showMessage(DocumentValidator.getMessage(fileTitle));
                fileField.val("");
                return false;
            }
        }
        
        var fileData = new FormData();
        if (fileField.get(0).files.length > 0) {
            fileData.append("document", fileField.get(0).files[0]);
            fileData.append("document_id", documentId);
            fileData.append("document_type_id", MembersipChange.formModal.find("select#document_type_id").val());
            fileData.append("description", MembersipChange.formModal.find("input#description").val());
            $.ajax({
                    url : MembersipChange.getUrl(MembersipChange.actions.save_document_with_file),
                    method: "POST",
                    data: fileData,
                    contentType: false,
                    cache: false,
                    processData: false,
                    success: function(reply) {
                        if (reply.valid) {
                            BranchCommon.reloadPage();
                        } else {
                            MembersipChange.showMessage(reply.message);
                        }
                    }
                }
            );
        } else {
            var formData = {
                id : documentId,
                document_type_id : MembersipChange.formModal.find("select#document_type_id").val(),
                description : MembersipChange.formModal.find("input#description").val()
            };

            $.post(MembersipChange.getUrl(MembersipChange.actions.save_document_without_file), formData, function(reply) {
                    if (reply.success && reply.valid) {
                        BranchCommon.reloadPage();
                    } else if (reply.redirect) {
                        BranchCommon.reloadPage();
                    } else if (reply.message) {
                        MembersipChange.showMessage(reply.message);
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
            $.post(MembersipChange.getUrl(MembersipChange.actions.remove_document), { id : documentId }, function(reply) {
                    if (reply.success && reply.valid) {
                        BranchCommon.reloadPage();
                    } else if (reply.redirect) {
                        BranchCommon.reloadPage();
                    } else if (reply.message) {
                        MembersipChange.showMessage(reply.message);
                    } else {
                        alert("Unable to complete the request.");
                    }
                }, "json"
            );
        }
        
        return false;
    },
    
    photoForm: function () {
        MembersipChange.setModal("modal-photo");
        MembersipChange.formModal.find("input[type=file]").val("");
        
        return false;
    },
    
    uploadPhoto: function (e) {
        e.preventDefault();
        MembersipChange.clearMessage();
        var fileField = $("input#photo_file");
        var fileTitle = "Photo";
        if (fileField.get(0).files.length > 0) {
            if (! PhotoValidator.validate(fileField)) {
                MembersipChange.showMessage(PhotoValidator.getMessage(fileTitle));
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
                    url : MembersipChange.getUrl(MembersipChange.actions.save_photo),
                    method: "POST",
                    data: fileData,
                    contentType: false,
                    cache: false,
                    processData: false,
                    success: function(reply) {
                        if (reply.valid) {
                            BranchCommon.reloadPage();
                        } else {
                            MembersipChange.showMessage(reply.message);
                        }
                    }
                }
            );
        }
        
        fileField.val("");
        return false;
    },
    
    addAccount: function () {
        MembersipChange.setModal("modal-account");
        MembersipChange.formModal.find("h3#myModalLabel6").text("Add Bank Account");
        MembersipChange.formModal.find("input[type=text]").val("");
        MembersipChange.formModal.find("select").val(0);
        MembersipChange.formModal.find("input#account-id").val(0);
        
        return false;
    },
    
    editAccount: function () {
        var accountId = $(this).data("id");
        var formData = { id : accountId };
        $.post(MembersipChange.getUrl(MembersipChange.actions.get_account), formData, function(reply) {
                if (reply.success && reply.valid) {
                    MembersipChange.setModal("modal-account");
                    MembersipChange.formModal.find("h3#myModalLabel6").text("Edit Bank Account");
                    MembersipChange.formModal.find("input#account_number").val(reply.result.account_number);
                    MembersipChange.formModal.find("select#bank_id").val(reply.result.bank_id);
                    MembersipChange.formModal.find("select#account_type_id").val(reply.result.account_type_id);
                    MembersipChange.formModal.find("input#description").val(reply.result.description);
                    MembersipChange.formModal.find("input#account-id").val(accountId);
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
    
    saveAccount: function () {
        MembersipChange.clearMessage();
        if ("0" === MembersipChange.formModal.find("select#bank_id").val()) {
            MembersipChange.showMessage("Bank not selected");
            return false;
        }
        
        if ("0" === MembersipChange.formModal.find("select#account_type_id").val()) {
            MembersipChange.showMessage("Account type not selected");
            return false;
        }
        
        var formData = {
            id : MembersipChange.formModal.find("input#account-id").val(),
            bank_id : MembersipChange.formModal.find("select#bank_id").val(),
            account_number : MembersipChange.formModal.find("input#account_number").val(),
            account_type_id : MembersipChange.formModal.find("select#account_type_id").val(),
            description : MembersipChange.formModal.find("input#description").val()
        };
        
        $.post(MembersipChange.getUrl(MembersipChange.actions.save_account), formData, function(reply) {
                if (reply.success && reply.valid) {
                    BackendCommon.reloadPage();
                } else if (reply.redirect) {
                    BackendCommon.reloadPage();
                } else if (reply.message) {
                    MembersipChange.showMessage(reply.message);
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
            $.post(MembersipChange.getUrl(MembersipChange.actions.remove_account), { id : accountId }, function(reply) {
                    if (reply.success && reply.valid) {
                        BackendCommon.reloadPage();
                    } else if (reply.redirect) {
                        BackendCommon.reloadPage();
                    } else if (reply.message) {
                        MembersipChange.showMessage(reply.message);
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
        MembersipChange.setModal("modal-password");
        MembersipChange.formModal.find("h3#myModalLabel7").text(title);
        MembersipChange.formModal.find("input[type=password]").val("");
        
        return false;
    },
    
    savePassword: function () {
        MembersipChange.clearMessage();
        var passwordFirst = MembersipChange.formModal.find("input#password1").val();
        var passwordRepeat = MembersipChange.formModal.find("input#password2").val();
        if ("" === passwordFirst) {
            MembersipChange.showMessage("Password not entered");
            return false;
        }
        
        if ("" === passwordRepeat) {
            MembersipChange.showMessage("Repeat Password not entered");
            return false;
        }
        
        if (passwordRepeat !== passwordFirst) {
            MembersipChange.showMessage("Password and Repeat password are not the same");
            return false;
        }
        
        var passwordLength = passwordFirst.length;
        
        if (passwordLength < 6 || passwordLength > 20) {
            MembersipChange.showMessage("Password should contain 6 - 20 characters");
            return false;
        }
        
        if (/\s+/.test(passwordFirst)) {
            MembersipChange.showMessage("Password should not contain space");
            return false;
        }
        
        
        var formData = { password1 : passwordFirst, password2 : passwordRepeat };
        
        $.post(MembersipChange.getUrl(MembersipChange.actions.set_password), formData, function(reply) {
                if (reply.success && reply.valid) {
                    BackendCommon.reloadPage();
                } else if (reply.redirect) {
                    BackendCommon.reloadPage();
                } else if (reply.message) {
                    MembersipChange.showMessage(reply.message);
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
        MembersipChange.formModal = $("div#" + name);
        MembersipChange.formModal.modal("show");
        MembersipChange.formModal.find("div.alert").hide();
    },
    
    closeModal: function () {
        MembersipChange.formModal.modal("hide");
    },
    
    showMessage: function (message) {
        MembersipChange.formModal.find("div.alert").html(message).show();
    },
    
    clearMessage: function () {
        MembersipChange.formModal.find("div.alert").hide();
    }
    
};

$(document).ready(function () {
        MembersipChange.ready();
    }
);
