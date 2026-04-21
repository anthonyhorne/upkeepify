/**
 * Upkeepify Utility Functions
 * Shared utility functions for validation, formatting, and helpers
 */

(function($) {
    'use strict';

    window.UpkeepifyUtils = {
        
        /**
         * Validate email format
         * @param {string} email - Email address to validate
         * @return {boolean} True if valid email format
         */
        isValidEmail: function(email) {
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        },

        /**
         * Validate GPS coordinate format and range
         * @param {string|number} coord - Coordinate value
         * @param {string} type - 'lat' for latitude or 'lng' for longitude
         * @return {boolean} True if valid coordinate
         */
        isValidCoordinate: function(coord, type) {
            var value = parseFloat(coord);
            if (isNaN(value)) {
                return false;
            }
            
            if (type === 'lat') {
                return value >= -90 && value <= 90;
            } else if (type === 'lng') {
                return value >= -180 && value <= 180;
            }
            return false;
        },

        /**
         * Validate file type for images
         * @param {File} file - File object to validate
         * @return {boolean} True if valid image type
         */
        isValidImageType: function(file) {
            var validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            return validTypes.indexOf(file.type) !== -1;
        },

        /**
         * Validate file size against max limit
         * @param {File} file - File object to validate
         * @param {number} maxSizeMB - Maximum size in MB (default: 2MB)
         * @return {boolean} True if file size is within limit
         */
        isValidFileSize: function(file, maxSizeMB) {
            maxSizeMB = maxSizeMB || 2;
            var maxSizeBytes = maxSizeMB * 1024 * 1024;
            return file.size <= maxSizeBytes;
        },

        /**
         * Check whether the browser can optimize an image before upload.
         * @param {File} file - File object to inspect
         * @return {boolean} True if client-side optimization is supported
         */
        canOptimizeImage: function(file) {
            return !!(
                file &&
                file.type &&
                file.type.indexOf('image/') === 0 &&
                file.type !== 'image/gif' &&
                typeof Promise !== 'undefined' &&
                typeof FileReader !== 'undefined' &&
                typeof document !== 'undefined' &&
                typeof document.createElement === 'function' &&
                typeof DataTransfer !== 'undefined'
            );
        },

        /**
         * Replace the contents of a file input with a new file list.
         * @param {HTMLInputElement} input - File input element
         * @param {File[]} files - Files to assign
         * @return {boolean} True if the replacement succeeded
         */
        setInputFiles: function(input, files) {
            if (!input || typeof DataTransfer === 'undefined') {
                return false;
            }

            var transfer = new DataTransfer();

            files.forEach(function(file) {
                transfer.items.add(file);
            });

            input.files = transfer.files;
            return true;
        },

        /**
         * Resize/compress an image file to fit within a target size.
         * @param {File} file - Original image file
         * @param {Object} options - Optimization options
         * @return {Promise<File>} Promise resolving to the optimized file
         */
        optimizeImageFile: function(file, options) {
            var self = this;
            var settings = $.extend({
                maxBytes: 2 * 1024 * 1024,
                maxDimension: 1600,
                quality: 0.82,
                minQuality: 0.55,
                qualityStep: 0.07
            }, options || {});

            if (!self.canOptimizeImage(file) || file.size <= settings.maxBytes) {
                return Promise.resolve(file);
            }

            return self.readFileAsDataUrl(file).then(function(dataUrl) {
                return self.loadImageElement(dataUrl).then(function(image) {
                    var dimensions = self.getScaledDimensions(
                        image.naturalWidth || image.width,
                        image.naturalHeight || image.height,
                        settings.maxDimension
                    );

                    var canvas = document.createElement('canvas');
                    var context = canvas.getContext('2d');

                    if (!context) {
                        return file;
                    }

                    canvas.width = dimensions.width;
                    canvas.height = dimensions.height;
                    context.drawImage(image, 0, 0, dimensions.width, dimensions.height);

                    return self.canvasToSizedFile(canvas, file, settings);
                });
            }).catch(function() {
                return file;
            });
        },

        /**
         * Read a file as a data URL.
         * @param {File} file - File to read
         * @return {Promise<string>} Promise resolving to the data URL
         */
        readFileAsDataUrl: function(file) {
            return new Promise(function(resolve, reject) {
                var reader = new FileReader();

                reader.onload = function(event) {
                    resolve(event.target.result);
                };

                reader.onerror = reject;
                reader.readAsDataURL(file);
            });
        },

        /**
         * Load an image element from a URL.
         * @param {string} src - Image source
         * @return {Promise<HTMLImageElement>} Promise resolving to an image
         */
        loadImageElement: function(src) {
            return new Promise(function(resolve, reject) {
                var image = new Image();

                image.onload = function() {
                    resolve(image);
                };

                image.onerror = reject;
                image.src = src;
            });
        },

        /**
         * Scale dimensions to fit within a bounding box.
         * @param {number} width - Original width
         * @param {number} height - Original height
         * @param {number} maxDimension - Maximum width/height
         * @return {Object} Scaled width/height pair
         */
        getScaledDimensions: function(width, height, maxDimension) {
            var longestSide = Math.max(width, height);
            var scale = longestSide > maxDimension ? maxDimension / longestSide : 1;

            return {
                width: Math.max(1, Math.round(width * scale)),
                height: Math.max(1, Math.round(height * scale))
            };
        },

        /**
         * Convert a canvas to a blob.
         * @param {HTMLCanvasElement} canvas - Canvas element
         * @param {string} mimeType - Output mime type
         * @param {number} quality - Output quality
         * @return {Promise<Blob|null>} Promise resolving to the blob
         */
        canvasToBlob: function(canvas, mimeType, quality) {
            return new Promise(function(resolve) {
                canvas.toBlob(function(blob) {
                    resolve(blob);
                }, mimeType, quality);
            });
        },

        /**
         * Convert a blob into a File object with a matching extension.
         * @param {Blob} blob - Blob to wrap
         * @param {File} originalFile - Source file metadata
         * @param {string} mimeType - Output mime type
         * @return {File} File object ready for upload
         */
        blobToFile: function(blob, originalFile, mimeType) {
            var extensionMap = {
                'image/jpeg': '.jpg',
                'image/png': '.png',
                'image/webp': '.webp'
            };
            var targetExtension = extensionMap[mimeType] || '.jpg';
            var nextName = originalFile.name.replace(/\.[^.]+$/, '') + targetExtension;

            if (nextName === originalFile.name && !/\.[^.]+$/.test(originalFile.name)) {
                nextName = originalFile.name + targetExtension;
            }

            var optimizedFile = new File([blob], nextName, {
                type: mimeType,
                lastModified: originalFile.lastModified
            });

            optimizedFile.upkeepifyOptimized = true;
            optimizedFile.upkeepifyOriginalSize = originalFile.size;

            return optimizedFile;
        },

        /**
         * Pick output mime types to try while compressing.
         * @param {File} file - Source image file
         * @return {string[]} Ordered list of mime types
         */
        getOptimizationMimeTypes: function(file) {
            if (file.type === 'image/png') {
                return ['image/png', 'image/jpeg'];
            }

            if (file.type === 'image/webp') {
                return ['image/webp', 'image/jpeg'];
            }

            return ['image/jpeg'];
        },

        /**
         * Encode a canvas to a size-limited File, reducing quality as needed.
         * @param {HTMLCanvasElement} canvas - Canvas with image content
         * @param {File} originalFile - Original file metadata
         * @param {Object} settings - Optimization settings
         * @return {Promise<File>} Promise resolving to the best file candidate
         */
        canvasToSizedFile: function(canvas, originalFile, settings) {
            var self = this;
            var mimeTypes = self.getOptimizationMimeTypes(originalFile);

            function attemptMimeType(index) {
                var mimeType = mimeTypes[index];
                var quality = settings.quality;

                function attemptQuality() {
                    return self.canvasToBlob(canvas, mimeType, quality).then(function(blob) {
                        if (!blob) {
                            return originalFile;
                        }

                        var isLosslessType = mimeType === 'image/png';
                        var canLowerQuality = !isLosslessType && (quality - settings.qualityStep) >= settings.minQuality;

                        if (blob.size <= settings.maxBytes) {
                            return self.blobToFile(blob, originalFile, mimeType);
                        }

                        if (canLowerQuality) {
                            quality -= settings.qualityStep;
                            return attemptQuality();
                        }

                        if (index + 1 < mimeTypes.length) {
                            return attemptMimeType(index + 1);
                        }

                        return self.blobToFile(blob, originalFile, mimeType);
                    });
                }

                return attemptQuality();
            }

            return attemptMimeType(0).then(function(candidateFile) {
                return candidateFile.size < originalFile.size ? candidateFile : originalFile;
            });
        },

        /**
         * Format file size for display
         * @param {number} bytes - File size in bytes
         * @return {string} Formatted file size string
         */
        formatFileSize: function(bytes) {
            if (bytes === 0) return '0 Bytes';
            var k = 1024;
            var sizes = ['Bytes', 'KB', 'MB', 'GB'];
            var i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },

        /**
         * Debounce function to limit execution frequency
         * @param {Function} func - Function to debounce
         * @param {number} wait - Wait time in milliseconds
         * @return {Function} Debounced function
         */
        debounce: function(func, wait) {
            var timeout;
            return function() {
                var context = this;
                var args = arguments;
                clearTimeout(timeout);
                timeout = setTimeout(function() {
                    func.apply(context, args);
                }, wait);
            };
        },

        /**
         * Throttle function to limit execution frequency
         * @param {Function} func - Function to throttle
         * @param {number} limit - Time limit in milliseconds
         * @return {Function} Throttled function
         */
        throttle: function(func, limit) {
            var inThrottle;
            return function() {
                var context = this;
                var args = arguments;
                if (!inThrottle) {
                    func.apply(context, args);
                    inThrottle = true;
                    setTimeout(function() {
                        inThrottle = false;
                    }, limit);
                }
            };
        },

        /**
         * Format currency value with symbol
         * @param {number} amount - Amount to format
         * @param {string} symbol - Currency symbol (default: $)
         * @return {string} Formatted currency string
         */
        formatCurrency: function(amount, symbol) {
            symbol = symbol || '$';
            return symbol + parseFloat(amount).toFixed(2);
        },

        /**
         * Serialize form data to object
         * @param {jQuery} $form - jQuery form object
         * @return {Object} Serialized form data
         */
        serializeFormData: function($form) {
            var formData = {};
            $form.serializeArray().forEach(function(item) {
                if (formData[item.name]) {
                    if (!Array.isArray(formData[item.name])) {
                        formData[item.name] = [formData[item.name]];
                    }
                    formData[item.name].push(item.value);
                } else {
                    formData[item.name] = item.value;
                }
            });
            return formData;
        },

        /**
         * AJAX wrapper with common options
         * @param {Object} options - AJAX options
         * @return {Promise} jQuery promise
         */
        ajaxRequest: function(options) {
            var defaults = {
                type: 'POST',
                dataType: 'json',
                beforeSend: function() {
                    // Show loading state if element provided
                    if (options.$loadingElement) {
                        options.$loadingElement.addClass('loading').prop('disabled', true);
                    }
                },
                complete: function() {
                    // Remove loading state
                    if (options.$loadingElement) {
                        options.$loadingElement.removeClass('loading').prop('disabled', false);
                    }
                }
            };
            return $.ajax({}, defaults, options);
        },

        /**
         * Generate unique ID
         * @return {string} Unique ID string
         */
        generateUniqueId: function() {
            return 'upkeepify_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        },

        /**
         * Check if element is in viewport
         * @param {jQuery} $element - jQuery element
         * @return {boolean} True if element is visible in viewport
         */
        isInViewport: function($element) {
            var elementTop = $element.offset().top;
            var elementBottom = elementTop + $element.outerHeight();
            var viewportTop = $(window).scrollTop();
            var viewportBottom = viewportTop + $(window).height();
            return elementBottom > viewportTop && elementTop < viewportBottom;
        },

        /**
         * Smooth scroll to element
         * @param {jQuery|string} target - Target element or selector
         * @param {number} offset - Offset from top (default: 20)
         * @param {number} duration - Animation duration in ms (default: 500)
         */
        scrollToElement: function(target, offset, duration) {
            offset = offset || 20;
            duration = duration || 500;
            var $target = typeof target === 'string' ? $(target) : target;
            
            if ($target.length) {
                $('html, body').animate({
                    scrollTop: $target.offset().top - offset
                }, duration);
            }
        },

        /**
         * Escape HTML to prevent XSS
         * @param {string} text - Text to escape
         * @return {string} Escaped HTML
         */
        escapeHtml: function(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) {
                return map[m];
            });
        },

        /**
         * Parse date string to Date object
         * @param {string} dateString - Date string to parse
         * @return {Date|null} Date object or null if invalid
         */
        parseDate: function(dateString) {
            var date = new Date(dateString);
            return isNaN(date.getTime()) ? null : date;
        },

        /**
         * Format date for display
         * @param {Date|string} date - Date to format
         * @param {string} format - Format type ('short', 'long', 'time')
         * @return {string} Formatted date string
         */
        formatDate: function(date, format) {
            var d = typeof date === 'string' ? this.parseDate(date) : date;
            if (!d) return '';

            var options = {};
            
            switch(format) {
                case 'short':
                    options = { month: 'short', day: 'numeric', year: 'numeric' };
                    break;
                case 'long':
                    options = { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' };
                    break;
                case 'time':
                    options = { hour: '2-digit', minute: '2-digit' };
                    break;
                default:
                    options = { month: 'short', day: 'numeric', year: 'numeric' };
            }

            return d.toLocaleDateString(undefined, options);
        },

        /**
         * Get cookie value by name
         * @param {string} name - Cookie name
         * @return {string|null} Cookie value or null
         */
        getCookie: function(name) {
            var value = '; ' + document.cookie;
            var parts = value.split('; ' + name + '=');
            if (parts.length === 2) {
                return parts.pop().split(';').shift();
            }
            return null;
        },

        /**
         * Set cookie value
         * @param {string} name - Cookie name
         * @param {string} value - Cookie value
         * @param {number} days - Expiration in days
         */
        setCookie: function(name, value, days) {
            var expires = '';
            if (days) {
                var date = new Date();
                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                expires = '; expires=' + date.toUTCString();
            }
            document.cookie = name + '=' + value + expires + '; path=/';
        },

        /**
         * Delete cookie by name
         * @param {string} name - Cookie name
         */
        deleteCookie: function(name) {
            document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
        },

        /**
         * Check if device is mobile
         * @return {boolean} True if mobile device
         */
        isMobile: function() {
            return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        },

        /**
         * Copy text to clipboard
         * @param {string} text - Text to copy
         * @return {Promise} Promise resolving when copied
         */
        copyToClipboard: function(text) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                return navigator.clipboard.writeText(text);
            } else {
                // Fallback for older browsers
                var textArea = document.createElement('textarea');
                textArea.value = text;
                textArea.style.position = 'fixed';
                textArea.style.left = '-999999px';
                document.body.appendChild(textArea);
                textArea.select();
                try {
                    document.execCommand('copy');
                    document.body.removeChild(textArea);
                    return $.Deferred().resolve();
                } catch (err) {
                    document.body.removeChild(textArea);
                    return $.Deferred().reject(err);
                }
            }
        }
    };

})(jQuery);
