"use strict";

var DesignationType = {
    formModal: null,
    formAction: null,
    ready: function() {
        DesignationType.formModal = $("div#modal-designation-type");
        DesignationType.formAction = $("input#manage-url").val();
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
        $("a#add-designation-type").click(DesignationType.add);
        $("table#list-table-2").on("click", "a.edit-designation-type", DesignationType.edit);
        $("table#list-table-2").on("click", "a.remove-designation-type", DesignationType.remove);
        $("form#form-designation-type-modal").submit(DesignationType.save);
    },
    
    add: function () {
        DesignationType.formModal.find("h3#myModalLabelDesignationType").text("Add Designation Type");
        DesignationType.formModal.find("input[type=text]").val("");
        DesignationType.formModal.find("input#designation-type-id").val(0);
        DesignationType.formModal.modal("show");
        DesignationType.clearMessage();
    },
    
    save: function () {
        var formData = {
            action : 1,
            designation_type : DesignationType.formModal.find("input#designation_type").val(),
            id :  DesignationType.formModal.find("input#designation-type-id").val()
        };
        
        $.post(DesignationType.formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    BackendCommon.reloadPage();
                } else if (reply.redirect) {
                    BackendCommon.reloadPage();
                } else if (reply.message) {
                    DesignationType.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );

        return false;
    },
    
    edit: function () {
        DesignationType.formModal.find("h3#myModalLabelDesignationType").text("Edit Designation Type");
        DesignationType.formModal.find("input#designation_type").val($(this).data("title"));
        DesignationType.formModal.find("input#designation-type-id").val($(this).data("id"));
        DesignationType.formModal.modal("show");
        DesignationType.clearMessage();
    },
    
    remove: function () {
        if (confirm("Are you sure you to delete this Designation Type?")) {
            var cardTypeId = $(this).data("id");
            var formData = {
                action : 2,
                id : cardTypeId
            };

            $.post(DesignationType.formAction, formData, function(reply) {
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
        DesignationType.formModal.find("div.alert").html(message).show();
    },
    
    clearMessage: function () {
        DesignationType.formModal.find("div.alert").hide();
    }
    
};

$(document).ready(function() {
        DesignationType.ready();
    }
);
