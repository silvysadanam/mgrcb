"use strict";

var Manager = {
    formModal: null,
    formAction: null,
    ready: function() {
        Manager.formModal = $("div#modal-manager");
        Manager.formAction = $("input#manage-url").val();
        $("table#list-table-3").DataTable( {
                "aaSorting" : [],
                "lengthMenu": [[20, 50, 100], [20, 50, 100]],
                columnDefs: [ {
                        targets: [ 3 ],
                        searchable: false
                    }
                ]
            }
        );

        $("a#add-manager").click(Manager.add);
        $("table#list-table-3").on("click", "a.edit-manager", Manager.edit);
        $("table#list-table-3").on("click", "a.remove-manager", Manager.remove);
        $("form#form-manager-modal").submit(Manager.save);
    },
    
    add: function () {
        Manager.formModal.find("h3#myModalLabelManager").text("Add Manager");
        Manager.formModal.find("input[type=text],input[type=password]").val("");
        Manager.formModal.find("select").val(0).prop("disabled", false);
        Manager.formModal.find("input[type=password]").prop("required", true);
        Manager.formModal.find("input#manager-id").val(0);
        Manager.formModal.modal("show");
        Manager.clearMessage();
        Manager.formModal.find("p#edit-manager").hide();
    },
    
    save: function () {
        var formData = {
            action : 2,
            fmc_id : Manager.formModal.find("select#fmc-id").val(),
            name : Manager.formModal.find("input#manager-name").val(),
            username : Manager.formModal.find("input#manager-username").val(),
            password : Manager.formModal.find("input#manager-password").val(),
            id :  Manager.formModal.find("input#manager-id").val()
        };
        
        $.post(Manager.formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    ApplicationCommon.reloadPage();
                } else if (reply.redirect) {
                    ApplicationCommon.reloadPage();
                } else if (reply.message) {
                    Manager.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );

        return false;
    },
    
    edit: function () {
        var managerId = $(this).data("id");
        var formData = {
            action : 1,
            id :  managerId
        };
        
        $.post(Manager.formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    Manager.formModal.find("h3#myModalLabelManager").text("Edit Manager");
                    Manager.formModal.find("input#manager-name").val(reply.manager.name);
                    Manager.formModal.find("input#manager-username").val(reply.manager.username);
                    Manager.formModal.find("select#fmc-id").prop("disabled", true).val(reply.manager.fmc_id);
                    Manager.formModal.find("input[type=password]").prop("required", false).val("");
                    Manager.formModal.find("input#manager-id").val(managerId);
                    Manager.formModal.modal("show");
                    Manager.clearMessage();
                    Manager.formModal.find("p#edit-manager").show();
                } else if (reply.redirect) {
                    ApplicationCommon.reloadPage();
                } else if (reply.message) {
                    Manager.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );
        
    },
    
    remove: function () {
        if (confirm("Are you sure you to remove this Manager?")) {
            var managerId = $(this).data("id");
            var formData = {
                action : 3,
                id : managerId
            };

            $.post(Manager.formAction, formData, function(reply) {
                    if (reply.success && reply.valid) {
                        ApplicationCommon.reloadPage();
                    } else if (reply.redirect) {
                        ApplicationCommon.reloadPage();
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
        $("div#js-validation-error").html(message).show();
    },
    
    clearMessage: function () {
        $("div.alert,div#js-validation-error").hide();
    }
    
};

$(document).ready(function() {
        Manager.ready();
    }
);
