/**
 * Upkeepify Form Validation
 * Comprehensive client-side form validation for Upkeepify forms
 */

(function($) {
    'use strict';

    var UpkeepifyValidation = {
        
        validationRules: {},
        validators: {},
        
        /**
         * Initialize form validation
         */
        init: function() {
            this.setupTaskFormValidation();
            this.setupAdminFormValidation();
            this.setupProviderFormValidation();
            this.setupRealTimeValidation();
            this.bindFormSubmitHandlers();
        },

        /**
         * Setup validation rules for task submission form
         */
        setupTaskFormValidation: function() {
            this.validationRules['upkeepify-task-form'] = {
                'task_title': {
                    required: true,
                    minLength: 3,
                    maxLength: 200,
                    message: 'Task title is required (3-200 characters)'
                },
                'task_description': {
                    required: true,
                    minLength: 10,
                    message: 'Task description is required (minimum 10 characters)'
                },
                'gps_latitude': {
                    required: true,
                    pattern: /^-?\d{1,3}(\.\d+)?$/,
                    validator: 'coordinate',
                    type: 'lat',
                    message: 'Please enter a valid latitude (-90 to 90)'
                },
                'gps_longitude': {
                    required: true,
                    pattern: /^-?\d{1,3}(\.\d+)?$/,
                    validator: 'coordinate',
                    type: 'lng',
                    message: 'Please enter a valid longitude (-180 to 180)'
                },
                'math': {
                    required: true,
                    pattern: /^\d+$/,
                    message: 'Please answer the security question'
                }
            };
        },

        /**
         * Setup validation rules for admin forms
         */
        setupAdminFormValidation: function() {
            this.validationRules['admin-form'] = {
                'email': {
                    validator: 'email',
                    message: 'Please enter a valid email address'
                },
                'number_of_units': {
                    required: true,
                    pattern: /^\d+$/,
                    min: 0,
                    message: 'Please enter a valid number of units (0 or greater)'
                },
                'currency': {
                    required: true,
                    minLength: 1,
                    maxLength: 5,
                    message: 'Please enter a valid currency symbol'
                }
            };
        },

        /**
         * Setup validation rules for provider forms
         */
        setupProviderFormValidation: function() {
            this.validationRules['provider-form'] = {
                'provider_email': {
                    required: true,
                    validator: 'email',
                    message: 'Please enter a valid email address'
                },
                'provider_phone': {
                    required: false,
                    pattern: /^[\d\s\-\+\(\)]+$/,
                    message: 'Please enter a valid phone number'
                }
            };
        },

        /**
         * Custom validators
         */
        validators: {
            email: function(value) {
                return UpkeepifyUtils.isValidEmail(value);
            },
            coordinate: function(value, type) {
                return UpkeepifyUtils.isValidCoordinate(value, type);
            },
            file: function(file, maxSizeMB) {
                if (!UpkeepifyUtils.isValidImageType(file)) {
                    return {
                        valid: false,
                        message: 'Please upload a valid image file (JPG, PNG, GIF, or WebP)'
                    };
                }
                if (!UpkeepifyUtils.isValidFileSize(file, maxSizeMB || 2)) {
                    return {
                        valid: false,
                        message: 'File size must be less than ' + (maxSizeMB || 2) + 'MB'
                    };
                }
                return { valid: true };
            }
        },

        /**
         * Setup real-time validation on input change
         */
        setupRealTimeValidation: function() {
            var self = this;
            
            $('form').on('input change', 'input, textarea, select', UpkeepifyUtils.debounce(function(e) {
                var $field = $(this);
                var $form = $field.closest('form');
                self.validateField($field, $form);
            }, 300));

            // Validate file input on change
            $('form').on('change', 'input[type="file"]', function(e) {
                var $field = $(this);
                var $form = $field.closest('form');
                self.validateFileField($field, $form);
            });
        },

        /**
         * Bind form submit handlers
         */
        bindFormSubmitHandlers: function() {
            var self = this;
            
            $('form').on('submit', function(e) {
                var $form = $(this);
                var formId = $form.attr('id');
                
                if (self.validationRules[formId]) {
                    e.preventDefault();
                    if (self.validateForm($form, formId)) {
                        // Form is valid, allow submission
                        $form[0].submit();
                    }
                }
            });
        },

        /**
         * Validate a single field
         * @param {jQuery} $field - Field element
         * @param {jQuery} $form - Form element
         * @return {boolean} True if valid
         */
        validateField: function($field, $form) {
            var fieldName = $field.attr('name') || $field.attr('id');
            var value = $field.val().trim();
            var rules = this.getValidationRules($form, fieldName);
            
            this.clearFieldError($field);

            if (!rules) {
                return true;
            }

            // Check required
            if (rules.required && !value) {
                this.showFieldError($field, rules.message || 'This field is required');
                return false;
            }

            // Skip other validations if empty and not required
            if (!value && !rules.required) {
                return true;
            }

            // Check pattern
            if (rules.pattern && !rules.pattern.test(value)) {
                this.showFieldError($field, rules.message || 'Invalid format');
                return false;
            }

            // Check min length
            if (rules.minLength && value.length < rules.minLength) {
                this.showFieldError($field, rules.message || 'Minimum length is ' + rules.minLength + ' characters');
                return false;
            }

            // Check max length
            if (rules.maxLength && value.length > rules.maxLength) {
                this.showFieldError($field, rules.message || 'Maximum length is ' + rules.maxLength + ' characters');
                return false;
            }

            // Check min value
            if (rules.min !== undefined && parseFloat(value) < rules.min) {
                this.showFieldError($field, rules.message || 'Minimum value is ' + rules.min);
                return false;
            }

            // Check custom validator
            if (rules.validator) {
                if (this.validators[rules.validator]) {
                    var validatorResult = this.validators[rules.validator](value, rules.type);
                    if (typeof validatorResult === 'boolean' && !validatorResult) {
                        this.showFieldError($field, rules.message || 'Invalid value');
                        return false;
                    } else if (typeof validatorResult === 'object' && !validatorResult.valid) {
                        this.showFieldError($field, validatorResult.message || rules.message || 'Invalid value');
                        return false;
                    }
                }
            }

            // Check CAPTCHA if it's a math field
            if (fieldName === 'math') {
                var sessionMathResult = sessionStorage.getItem('upkeepify_math_result');
                if (sessionMathResult && parseInt(value) !== parseInt(sessionMathResult)) {
                    this.showFieldError($field, 'Incorrect answer. Please try again.');
                    return false;
                }
            }

            // Field is valid
            $field.addClass('valid');
            return true;
        },

        /**
         * Validate file field
         * @param {jQuery} $field - File input element
         * @param {jQuery} $form - Form element
         * @return {boolean} True if valid
         */
        validateFileField: function($field, $form) {
            var files = $field[0].files;
            
            this.clearFieldError($field);

            if (!files || files.length === 0) {
                // No file selected, check if required
                var rules = this.getValidationRules($form, $field.attr('name'));
                if (rules && rules.required) {
                    this.showFieldError($field, 'Please select a file to upload');
                    return false;
                }
                return true;
            }

            var file = files[0];
            var result = this.validators.file(file, 2); // 2MB limit

            if (!result.valid) {
                this.showFieldError($field, result.message);
                return false;
            }

            $field.addClass('valid');
            return true;
        },

        /**
         * Get validation rules for a field
         * @param {jQuery} $form - Form element
         * @param {string} fieldName - Field name
         * @return {Object|null} Validation rules
         */
        getValidationRules: function($form, fieldName) {
            var formId = $form.attr('id');
            if (!formId || !this.validationRules[formId]) {
                return null;
            }
            return this.validationRules[formId][fieldName] || null;
        },

        /**
         * Show error message for a field
         * @param {jQuery} $field - Field element
         * @param {string} message - Error message
         */
        showFieldError: function($field, message) {
            var $container = $field.closest('p, .form-group, .field');
            var $error = $container.find('.field-error');
            
            if (!$error.length) {
                $error = $('<span class="field-error"></span>');
                $container.append($error);
            }
            
            $field.addClass('error').removeClass('valid');
            $error.text(message).show();
        },

        /**
         * Clear error message for a field
         * @param {jQuery} $field - Field element
         */
        clearFieldError: function($field) {
            var $container = $field.closest('p, .form-group, .field');
            var $error = $container.find('.field-error');
            
            $field.removeClass('error');
            $error.hide();
        },

        /**
         * Validate entire form
         * @param {jQuery} $form - Form element
         * @param {string} formId - Form ID
         * @return {boolean} True if form is valid
         */
        validateForm: function($form, formId) {
            var self = this;
            var isValid = true;
            var firstInvalidField = null;

            // Get all fields with validation rules
            $form.find('input, textarea, select').each(function() {
                var $field = $(this);
                var fieldName = $field.attr('name') || $field.attr('id');
                var rules = self.getValidationRules($form, fieldName);
                
                if (rules) {
                    var fieldValid = self.validateField($field, $form);
                    
                    if (!fieldValid && isValid) {
                        isValid = false;
                        firstInvalidField = $field;
                    }
                }
            });

            // Validate file inputs
            $form.find('input[type="file"]').each(function() {
                var $field = $(this);
                var fieldValid = self.validateFileField($field, $form);
                
                if (!fieldValid && isValid) {
                    isValid = false;
                    if (!firstInvalidField) {
                        firstInvalidField = $field;
                    }
                }
            });

            // Scroll to first error if validation failed
            if (!isValid && firstInvalidField) {
                UpkeepifyUtils.scrollToElement(firstInvalidField, 50);
                firstInvalidField.focus();
                
                // Show form-level error
                self.showFormError($form, 'Please correct the errors below and try again.');
            } else {
                self.clearFormError($form);
            }

            return isValid;
        },

        /**
         * Show form-level error message
         * @param {jQuery} $form - Form element
         * @param {string} message - Error message
         */
        showFormError: function($form, message) {
            var $error = $form.find('.form-error');
            
            if (!$error.length) {
                $error = $('<div class="form-error"></div>');
                $form.prepend($error);
            }
            
            $error.text(message).show();
        },

        /**
         * Clear form-level error message
         * @param {jQuery} $form - Form element
         */
        clearFormError: function($form) {
            var $error = $form.find('.form-error');
            $error.hide();
        },

        /**
         * Reset form validation state
         * @param {jQuery} $form - Form element
         */
        resetValidation: function($form) {
            $form.find('.error').removeClass('error');
            $form.find('.valid').removeClass('valid');
            $form.find('.field-error').hide();
            $form.find('.form-error').hide();
        },

        /**
         * Add validation rules for a field
         * @param {string} formId - Form ID
         * @param {string} fieldName - Field name
         * @param {Object} rules - Validation rules
         */
        addValidationRule: function(formId, fieldName, rules) {
            if (!this.validationRules[formId]) {
                this.validationRules[formId] = {};
            }
            this.validationRules[formId][fieldName] = rules;
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        UpkeepifyValidation.init();
    });

    // Expose globally for external use
    window.UpkeepifyValidation = UpkeepifyValidation;

})(jQuery);
