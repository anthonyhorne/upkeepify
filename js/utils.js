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
