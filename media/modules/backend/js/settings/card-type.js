"use strict";

var CardType = {
    formModal: null,
    formAction: null,
    ready: function() {
        CardType.formModal = $("div#modal-card-type");
        CardType.formAction = $("input#manage-url").val();
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
        $("a#add-card-type").click(CardType.add);
        $("table#list-table-2").on("click", "a.edit-card-type", CardType.edit);
        $("table#list-table-2").on("click", "a.remove-card-type", CardType.remove);
        $("form#form-card-type-modal").submit(CardType.save);
    },
    
    add: function () {
        CardType.formModal.find("h3#myModalLabelCardType").text("Add Card Type");
        CardType.formModal.find("input[type=text]").val("");
        CardType.formModal.find("input#card-type-id").val(0);
        CardType.formModal.modal("show");
        CardType.clearMessage();
    },
    
    save: function () {
        var formData = {
            action : 1,
            card_type : CardType.formModal.find("input#card_type").val(),
            id :  CardType.formModal.find("input#card-type-id").val()
        };
        
        $.post(CardType.formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    BackendCommon.reloadPage();
                } else if (reply.redirect) {
                    BackendCommon.reloadPage();
                } else if (reply.message) {
                    CardType.showMessage(reply.message);
                } else {
                    alert("Unable to complete the request.");
                }
            }, "json"
        );

        return false;
    },
    
    edit: function () {
        CardType.formModal.find("h3#myModalLabelCardType").text("Edit Card Type");
        CardType.formModal.find("input#card_type").val($(this).data("title"));
        CardType.formModal.find("input#card-type-id").val($(this).data("id"));
        CardType.formModal.modal("show");
        CardType.clearMessage();
    },
    
    remove: function () {
        if (confirm("Are you sure you to delete this Card Type?")) {
            var cardTypeId = $(this).data("id");
            var formData = {
                action : 2,
                id : cardTypeId
            };

            $.post(CardType.formAction, formData, function(reply) {
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
        CardType.formModal.find("div.alert").html(message).show();
    },
    
    clearMessage: function () {
        CardType.formModal.find("div.alert").hide();
    }
    
};

$(document).ready(function() {
        CardType.ready();
    }
);
