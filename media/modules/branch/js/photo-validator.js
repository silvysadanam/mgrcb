"use strict";

var PhotoValidator = {
    maxSize: 10485760,
    allowedExtensions: ["jpg"],
    allowedTypes: [
        "image/jpeg"
    ],
    error: 0,
    errorTypes: {
        required : 1,
        type : 2,
        size : 3
    },
    
    validate: function (field, required) {
        PhotoValidator.error = 0;
        if (field.get(0).files.length > 0) {
            var fileObject = field.get(0).files[0];
            if (! /\.jpg$/i.test(fileObject.name)) {
                PhotoValidator.error = PhotoValidator.errorTypes.type;
            } else if (! PhotoValidator.allowedTypes.includes(fileObject.type)){
                PhotoValidator.error = PhotoValidator.errorTypes.type;
            } else if (fileObject.size > PhotoValidator.maxSize) {
                PhotoValidator.error = PhotoValidator.errorTypes.size;
            }
        } else if (required) {
            PhotoValidator.error = PhotoValidator.errorTypes.required;
        }
        
        return 0 === PhotoValidator.error;
    },
    
    getMessage: function (title) {
        if (PhotoValidator.error > 0) {
            if (PhotoValidator.errorTypes.required === PhotoValidator.error) {
                return title + " file is required";
            }
            
            if (PhotoValidator.errorTypes.type === PhotoValidator.error) {
                return "Invalid " + title + " file type";
            }
            
            if (PhotoValidator.errorTypes.size === PhotoValidator.error) {
                return title + " file size exceeds allowed limit";
            }
        }
        
        return "";
    }

};
