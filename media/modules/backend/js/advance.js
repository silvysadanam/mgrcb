"use strict";

var Advance = {
    ready: function () {
        $("table#list-table-9").DataTable( {
                "aaSorting" : [],
                "lengthMenu": [[20, 50, 100, 200], [20, 50, 100, 200]],
                columnDefs: [ {
                        targets: [ 9 ],
                        searchable: false
                    }
                ]
            }
        );

        $("select#fmc").change(Advance.loadfmcMembers);
    },
    
    loadfmcMembers: function () {
        var fmcId = $(this).val();
        if (fmcId > 0) {
            var formAction = $("input#get-member-url").val();
            var formData = { fmc : fmcId };

            $.post(formAction, formData, function(reply) {
                    if (reply.success && reply.valid) {
                        $("select#member").html(reply.result);
                    } else if (reply.redirect) {
                        BranchCommon.reloadPage();
                    } else if (reply.message) {
                        alert(reply.message);
                    } else {
                        alert("Unable to complete the request.");
                    }
                }, "json"
            );
        } else {
            $("select#member").html('<option value="0">--all--</option>');
        }
    }
    
};

$(document).ready(function () {
        Advance.ready();
    }
);
