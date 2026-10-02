"use strict";

var AuctioneerList = {
    modalBlock: null,
    ready: function () {
        $("table#list-table-4").DataTable( {
                "aaSorting" : [],
                "lengthMenu": [[20, 50, 100, 200], [20, 50, 100, 200]],
                columnDefs: [ {
                        targets: [ 4 ],
                        searchable: false
                    }
                ]
            }
        );

        AuctioneerList.modalBlock = $("div#modal-opening-balance");
        $("table#list-table-4").on("click", "a.set-balance", AuctioneerList.showBalance);
        $("form#form-opening-balance-modal").submit(AuctioneerList.saveBalance);
    },
    
    showBalance: function () {
        var auctioneerId = $(this).data("id");
        var formAction = $("input#get-balance-url").val();
        $.post(formAction, { id : auctioneerId }, function(reply) {
                if (reply.success && reply.valid) {
                    AuctioneerList.modalBlock.find("input#auctioneer-name").val(reply.result.name);
                    AuctioneerList.modalBlock.find("input#auctioneer-code").val(reply.result.code);
                    AuctioneerList.modalBlock.find("input#opening-balance").val(reply.result.opening_balance);
                    AuctioneerList.modalBlock.find("input#opening-date").val(reply.result.opening_date);
                    AuctioneerList.modalBlock.find("input#auctioneer-id").val(auctioneerId);
                    AuctioneerList.modalBlock.modal("show");
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
    
    saveBalance: function () {
        var formData = {
            id : AuctioneerList.modalBlock.find("input#auctioneer-id").val(),
            opening_balance : AuctioneerList.modalBlock.find("input#opening-balance").val(),
            opening_date : AuctioneerList.modalBlock.find("input#opening-date").val()
        };
        
        var formAction = $("input#set-balance-url").val();
        $.post(formAction, formData, function(reply) {
                if (reply.success && reply.valid) {
                    BranchCommon.reloadPage();
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
    }
    
};

$(document).ready(function () {
        AuctioneerList.ready();
    }
);
