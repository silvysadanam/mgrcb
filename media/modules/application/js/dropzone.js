"use strict";

Dropzone.autoDiscover = false;

var ApplicationDropzone = {
    ready: function() {
        Dropzone.options.dropzoneUploadSingle = {
            paramName: "document",
            maxFiles: 1,
            maxFilesize: 10,
            clickable : true,
            createImageThumbnails : false,
            addRemoveLinks : false,
            acceptedFiles : ".jpg,.pdf,.doc,.docx",
            init: function() {
                this.on("maxfilesexceeded", function(file) {
                    this.removeAllFiles();
                    this.addFile(file);
                });
                this.on("complete", function(file) {
                    this.removeAllFiles();
                });
            }
        };

        var dropzoneModalUpload = new Dropzone("div#dropzone-upload-single", {
                url: $("input#dropzone-upload-url").val(),
                headers: { "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content") },
                success : function (file, reply) {
                    ApplicationDropzone.uploaded(reply);
                },
                error : function (message) {
                    alert("Upload failed, please try again");
                }
            }
        );

        $("body").on("click", "a.drop-upload-file", function () {
                var fileOptions = {
                    field : $(this).data("field"),
                    title : $(this).data("title")
                };
                ApplicationDropzone.openDropzone(fileOptions);
            }
        );
        
    },

    uploaded: function (reply) {
        if (reply.valid && reply.success) {
            $("div#modal-dropzone").modal("hide");
            var fileId = $("input#target-file-id").val();
            $("input#hidden_" + fileId).val(reply.hidden_file);
            $("div#" + fileId + "_document").html(reply.document_link);
            $("div#" + fileId + "_message").show();
        } else {
            alert(reply.message);
        }
    },
    
    openDropzone: function (options) {
        var modalBlock = $("div#modal-dropzone");
        modalBlock.find("input#target-file-id").val(options.field);
        if (options.title) {
            modalBlock.find("h3#myModalDropzone").text(options.title);
        }
        modalBlock.modal("show");
    }
};

$(document).ready(function() {
        ApplicationDropzone.ready();
    }
);
