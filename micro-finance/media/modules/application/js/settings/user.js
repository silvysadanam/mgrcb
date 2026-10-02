"use strict";

var ManageUser = {
    formModal: null,
    formAction: null,
    userTitle: null,
    userType: 0,
    ready: function() {
        ManageUser.formModal = $("div#modal-user");
        ManageUser.formAction = $("input#manage-url").val();
        ManageUser.userTitle = $("input#user-title").val();
        ManageUser.userType = $("input#user-type").val();
        $("table#list-table-2").DataTable( {
                "aaSorting" : [],
                "lengthMenu": [[20, 50, 100], [20, 50, 100]],
                columnDefs: [ {
                        targets: [ 2 ],
                        searchable: false
                    }
                ]
            }
        );

        $("a#add-user").click(ManageUser.add);
        $("table#list-table-2").on("click", "a.edit-user", ManageUser.edit);
        $("table#list-table-2").on("click", "a.remove-user", ManageUser.remove);
        $("form#form-user-modal").submit(ManageUser.save);
    },
    
    add: function () {
        ManageUser.formModal.find("h3#myModalLabelUser").text("Add " + ManageUser.userTitle);
        ManageUser.formModal.find("input[type=text],input[type=password]").val("");
        ManageUser.formModal.find("input[type=password]").prop("required", true);
        ManageUser.formModal.find("input#user-id").val(0);
        ManageUser.formModal.modal("show");
        ManageUser.clearMessage();
        ManageUser.formModal.find("p#edit-user").hide();
    },
    
    save: function () {
        var formData = {
            action : 2,
            name : ManageUser.formModal.find("input#user-name").val(),
            username : ManageUser.formModal.find("input#user-username").val(),
            password : ManageUser.formModal.find("input#user-password").val(),
            id :  ManageUser.formModal.find("input#user-id").val(),
            type : ManageUser.userType
        };
        
        $.post(ManageUser.formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    ApplicationCommon.reloadPage();
                } else if (reply.redirect) {
                    ApplicationCommon.reloadPage();
                } else if (reply.message) {
                    ManageUser.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );

        return false;
    },
    
    edit: function () {
        var userId = $(this).data("id");
        var formData = {
            action : 1,
            id :  userId,
            type : ManageUser.userType
        };
        
        $.post(ManageUser.formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    ManageUser.formModal.find("h3#myModalLabelCEO").text("Edit " + ManageUser.userTitle);
                    ManageUser.formModal.find("input#user-name").val(reply.user.name);
                    ManageUser.formModal.find("input#user-username").val(reply.user.username);
                    ManageUser.formModal.find("input[type=password]").prop("required", false).val("");
                    ManageUser.formModal.find("input#user-id").val(userId);
                    ManageUser.formModal.modal("show");
                    ManageUser.clearMessage();
                    ManageUser.formModal.find("p#edit-user").show();
                } else if (reply.redirect) {
                    ApplicationCommon.reloadPage();
                } else if (reply.message) {
                    ManageUser.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );
        
    },
    
    remove: function () {
        if (confirm("Are you sure you to remove this " + ManageUser.userTitle + "?")) {
            var userId = $(this).data("id");
            var formData = {
                action : 3,
                id : userId,
                type : ManageUser.userType
            };

            $.post(ManageUser.formAction, formData, function(reply) {
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
        $("div#js-validation-error").hide();
    }
    
};

$(document).ready(function() {
        ManageUser.ready();
    }
);
