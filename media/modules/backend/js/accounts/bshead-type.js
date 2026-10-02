"use strict";

var HeadType = {
    formModal: null,
    formAction: null,
    ready: function() {
        HeadType.formModal = $("div#modal-head-type");
        HeadType.formAction = $("input#manage-url").val();
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
        $("a#add-head-type").click(HeadType.add);
        $("table#list-table-2").on("click", "a.edit-head-type", HeadType.edit);
        $("table#list-table-2").on("click", "a.remove-head-type", HeadType.remove);
        $("form#form-head-type-modal").submit(HeadType.save);
    },
    
    add: function () {
        HeadType.formModal.find("h3#myModalLabelHeadType").text("Add Head Type");
        HeadType.formModal.find("input[type=text]").val("");
        HeadType.formModal.find("input#head-id").val(0);
        HeadType.formModal.find("input#fmc_id").val($(this).data("fmc_id"));
        HeadType.formModal.find("input#head_opb").val(0);
        HeadType.formModal.modal("show");
        HeadType.clearMessage();
    },
    
    save: function () {
        var formData = {
            action : 1,
            head_name : HeadType.formModal.find("input#head_name").val(),
            head_alias : HeadType.formModal.find("input#head_alias").val(),
            transType : HeadType.formModal.find("select#head_type").val(),
            opb : HeadType.formModal.find("input#head_opb").val(),
            code : HeadType.formModal.find("input#head_code").val(),
            id :  HeadType.formModal.find("input#head-id").val(),
            fmc_id : HeadType.formModal.find("input#fmc_id").val(),
            status : HeadType.formModal.find("select#status").val()
        };
        
        $.post(HeadType.formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    BackendCommon.reloadPage();
                } else if (reply.redirect) {
                    BackendCommon.reloadPage();
                } else if (reply.message) {
                    HeadType.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );

        return false;
    },
    
    edit: function () {
        HeadType.formModal.find("h3#myModalLabelHeadType").text("Edit Balance Sheet Head");
        HeadType.formModal.find("input#head_name").val($(this).data("head_name"));
        HeadType.formModal.find("input#head_alias").val($(this).data("alias"));
        HeadType.formModal.find("select#head_type").val($(this).data("type"));
        HeadType.formModal.find("input#head_opb").val($(this).data("opb"));
        HeadType.formModal.find("input#head_code").val($(this).data("code"));
        HeadType.formModal.find("input#head-id").val($(this).data("id"));
        HeadType.formModal.find("input#fmc_id").val($(this).data("fmc_id"));
        HeadType.formModal.find("select#status").val($(this).data("status"));
        HeadType.formModal.modal("show");
        HeadType.clearMessage();
    },
    
    remove: function () {
        if (confirm("Are you sure you to delete this Head Type?")) {
            var HeadTypeId = $(this).data("id");
            var formData = {
                action : 2,
                id : HeadTypeId
            };

            $.post(HeadType.formAction, formData, function(reply) {
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
        HeadType.formModal.find("div.alert").html(message).show();
    },
    
    clearMessage: function () {
        HeadType.formModal.find("div.alert").hide();
    }
    
};

$(document).ready(function() {
        HeadType.ready();
    }
);
