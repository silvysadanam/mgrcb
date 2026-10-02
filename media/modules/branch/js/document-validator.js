"use strict";

var DocumentValidator = {
    maxSize: 10485760,
    allowedExtensions: ["jpg", "pdf"],
    allowedTypes: [
        "application/pdf", "image/jpeg"
    ],
    error: 0,
    errorTypes: {
        required : 1,
        type : 2,
        size : 3
    },
    
    validate: function (field, required) {
        DocumentValidator.error = 0;
        if (field.get(0).files.length > 0) {
            var fileObject = field.get(0).files[0];
            if (! /\.(pdf|jpg)$/i.test(fileObject.name)) {
                DocumentValidator.error = DocumentValidator.errorTypes.type;
            } else if (! DocumentValidator.allowedTypes.includes(fileObject.type)){
                DocumentValidator.error = DocumentValidator.errorTypes.type;
            } else if (fileObject.size > DocumentValidator.maxSize) {
                DocumentValidator.error = DocumentValidator.errorTypes.size;
            }
        } else if (required) {
            DocumentValidator.error = DocumentValidator.errorTypes.required;
        }
        
        return 0 === DocumentValidator.error;
    },
    
    getMessage: function (title) {
        if (DocumentValidator.error > 0) {
            if (DocumentValidator.errorTypes.required === DocumentValidator.error) {
                return title + " file is required";
            }
            
            if (DocumentValidator.errorTypes.type === DocumentValidator.error) {
                return "Invalid " + title + " file type";
            }
            
            if (DocumentValidator.errorTypes.size === DocumentValidator.error) {
                return title + " file size exceeds allowed limit";
            }
        }
        
        return "";
    }

};
