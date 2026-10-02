"use strict";

var ManageBank = {
    formModal: null,
    formAction: null,
    ready: function() {
        ManageBank.formModal = $("div#modal-bank");
        ManageBank.formAction = $("input#manage-url").val();
        $("table#list-table-4").DataTable( {
                "aaSorting" : [],
                "lengthMenu": [[20, 50, 100], [20, 50, 100]],
                columnDefs: [ {
                        targets: [ 4 ],
                        searchable: false
                    }
                ]
            }
        );
        $("a#add-bank").click(ManageBank.add);
        $("table#list-table-4").on("click", "a.edit-bank", ManageBank.edit);
        $("table#list-table-4").on("click", "a.remove-bank", ManageBank.remove);
        $("form#form-bank-modal").submit(ManageBank.save);
    },
    
    add: function () {
        ManageBank.formModal.find("h3#myModalLabelBank").text("Add Bank");
        ManageBank.formModal.find("input[type=text]").val("");
        ManageBank.formModal.find("input#bank-id").val(0);
        ManageBank.formModal.modal("show");
        ManageBank.clearMessage();
    },
    
    save: function () {
        var formData = {
            action : 1,
            bank : ManageBank.formModal.find("input#bank").val(),
            branch : ManageBank.formModal.find("input#branch").val(),
            ifsc : ManageBank.formModal.find("input#ifsc").val(),
            id :  ManageBank.formModal.find("input#bank-id").val()
        };
        
        $.post(ManageBank.formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    BackendCommon.reloadPage();
                } else if (reply.redirect) {
                    BackendCommon.reloadPage();
                } else if (reply.message) {
                    ManageBank.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );

        return false;
    },
    
    edit: function () {
        ManageBank.formModal.find("h3#myModalLabelBank").text("Edit Bank");
        ManageBank.formModal.find("input#bank").val($(this).data("bank"));
        ManageBank.formModal.find("input#branch").val($(this).data("branch"));
        ManageBank.formModal.find("input#ifsc").val($(this).data("ifsc"));
        ManageBank.formModal.find("input#bank-id").val($(this).data("id"));
        ManageBank.formModal.modal("show");
        ManageBank.clearMessage();
    },
    
    remove: function () {
        if (confirm("Are you sure you to delete this Bank?")) {
            var bankId = $(this).data("id");
            var formData = {
                action : 2,
                id : bankId
            };

            $.post(ManageBank.formAction, formData, function(reply) {
                    if (reply.success && reply.valid) {
                        BackendCommon.reloadPage();
                    } else if (reply.redirect) {
                        BackendCommon.reloadPage();
                    } else if (reply.message) {
                        alert(reply.message);
                    } else {
                        alert("Unable to complete the request.");
                    }
                }, "json"
            );
        }

        return false;
    },
            
    showMessage: function (message) {
        ManageBank.formModal.find("div.alert").html(message).show();
    },
    
    clearMessage: function () {
        ManageBank.formModal.find("div.alert").hide();
    }
    
};

$(document).ready(function() {
        ManageBank.ready();
    }
);
