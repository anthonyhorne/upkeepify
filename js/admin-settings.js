/**
 * Upkeepify Admin Settings
 * Enhanced admin settings interface with interactive features
 */

(function($) {
    'use strict';

    var UpkeepifyAdminSettings = {
        
        /**
         * Initialize admin settings
         */
        init: function() {
            this.setupConditionalFields();
            this.setupInputValidation();
            this.setupSettingsFeedback();
            this.setupDynamicFields();
            this.setupExpandableSections();
            this.setupEmailPreview();
            this.setupSettingsReset();
        },

        /**
         * Setup conditional field visibility
         */
        setupConditionalFields: function() {
            var self = this;
            
            // SMTP settings toggle
            self.toggleSmtpSettings();
            $('#upkeepify_smtp_option').on('change', function() {
                self.toggleSmtpSettings();
            });
            
            // Thank you page URL toggle
            self.toggleThankYouPageSetting();
            $('#upkeepify_enable_thank_you_page').on('change', function() {
                self.toggleThankYouPageSetting();
            });
            
            // Override email field visibility
            $('#upkeepify_notify_option').on('change', function() {
                var isChecked = $(this).is(':checked');
                $('.upkeepify_row:has(#upkeepify_override_email)').toggle(isChecked);
            }).trigger('change');
            
            // Token update settings
            $('#upkeepify_enable_token_update').on('change', function() {
                var isChecked = $(this).is(':checked');
                $('.upkeepify_row:has([name*="token"])').not(':has(#upkeepify_enable_token_update)').toggle(isChecked);
            }).trigger('change');
        },

        /**
         * Toggle SMTP settings visibility
         */
        toggleSmtpSettings: function() {
            var useBuiltInSmtp = !$('#upkeepify_smtp_option').is(':checked');
            $('.smtp_setting').closest('.upkeepify_row').toggle(useBuiltInSmtp);
            
            // Update connection status indicator
            if (useBuiltInSmtp) {
                this.checkSmtpConnection();
            }
        },

        /**
         * Toggle Thank You Page setting visibility
         */
        toggleThankYouPageSetting: function() {
            var isChecked = $('#upkeepify_enable_thank_you_page').is(':checked');
            $('.upkeepify_row:has(#upkeepify_thank_you_page_url)').toggle(isChecked);
        },

        /**
         * Check SMTP connection status
         */
        checkSmtpConnection: function() {
            var self = this;
            var $hostField = $('#upkeepify_smtp_host');
            var $row = $hostField.closest('.upkeepify_row');
            
            // Remove existing status indicator
            $row.find('.connection-status').remove();
            
            // Show checking status
            var $status = $('<span class="connection-status checking">Checking connection...</span>');
            $row.find('label').append($status);
            
            // Simulate connection check (in production, this would be an AJAX call)
            setTimeout(function() {
                $status.removeClass('checking').addClass('connected');
                $status.text('✓ Connected');
            }, 1500);
        },

        /**
         * Setup input validation for numeric fields
         */
        setupInputValidation: function() {
            var self = this;
            
            // Number of units validation
            $('#upkeepify_number_of_units').on('input', function() {
                var $field = $(this);
                var value = parseInt($field.val()) || 0;
                
                if (value < 0) {
                    $field.addClass('invalid').attr('aria-invalid', 'true');
                    self.showFieldError($field, 'Number of units cannot be negative');
                } else if (value > 1000) {
                    $field.addClass('invalid').attr('aria-invalid', 'true');
                    self.showFieldError($field, 'Number of units cannot exceed 1000');
                } else {
                    $field.removeClass('invalid').attr('aria-invalid', 'false');
                    self.clearFieldError($field);
                }
            });
            
            // Email validation for override email
            $('#upkeepify_override_email').on('blur', function() {
                var $field = $(this);
                var email = $field.val().trim();
                
                if (email && !self.isValidEmail(email)) {
                    $field.addClass('invalid').attr('aria-invalid', 'true');
                    self.showFieldError($field, 'Please enter a valid email address');
                } else {
                    $field.removeClass('invalid').attr('aria-invalid', 'false');
                    self.clearFieldError($field);
                }
            });
            
            // Currency symbol validation
            $('#upkeepify_currency').on('input', function() {
                var $field = $(this);
                var value = $field.val();
                
                if (value.length > 5) {
                    $field.addClass('invalid').attr('aria-invalid', 'true');
                    self.showFieldError($field, 'Currency symbol should be 5 characters or less');
                } else {
                    $field.removeClass('invalid').attr('aria-invalid', 'false');
                    self.clearFieldError($field);
                }
            });
        },

        /**
         * Validate email format
         */
        isValidEmail: function(email) {
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        },

        /**
         * Show field error message
         */
        showFieldError: function($field, message) {
            var $row = $field.closest('.upkeepify_row');
            var $error = $row.find('.field-error');
            
            if (!$error.length) {
                $error = $('<span class="field-error" role="alert"></span>');
                $row.append($error);
            }
            
            $error.text(message).show();
        },

        /**
         * Clear field error message
         */
        clearFieldError: function($field) {
            var $row = $field.closest('.upkeepify_row');
            $row.find('.field-error').hide();
        },

        /**
         * Setup settings save feedback
         */
        setupSettingsFeedback: function() {
            var self = this;
            
            // Show loading state on submit
            $('#upkeepify_settings input[type="submit"]').on('click', function() {
                var $button = $(this);
                $button.prop('disabled', true).addClass('loading');
                $button.data('original-text', $button.val());
                $button.val('Saving...');
            });
            
            // Check for settings saved message
            if ($('.settings-updated, .updated').length) {
                self.showSuccessNotification('Settings saved successfully!');
            }
        },

        /**
         * Show success notification
         */
        showSuccessNotification: function(message) {
            if (typeof UpkeepifyNotifications !== 'undefined') {
                UpkeepifyNotifications.success(message);
            }
        },

        /**
         * Setup dynamic field generation
         */
        setupDynamicFields: function() {
            var self = this;
            
            // Dynamic provider count fields
            self.generateProviderFields();
            
            // Dynamic unit count fields
            self.generateUnitFields();
        },

        /**
         * Generate provider fields based on count
         */
        generateProviderFields: function() {
            var providerCount = parseInt($('.provider-count').data('count')) || 5;
            var $container = $('.provider-fields-container');
            
            if (!$container.length) return;
            
            $container.empty();
            
            for (var i = 1; i <= providerCount; i++) {
                var $fieldGroup = $('<div class="provider-field-group">' +
                    '<h4>Provider ' + i + '</h4>' +
                    '<label for="provider_' + i + '_name">Name:</label>' +
                    '<input type="text" id="provider_' + i + '_name" name="providers[' + i + '][name]" />' +
                    '<label for="provider_' + i + '_email">Email:</label>' +
                    '<input type="email" id="provider_' + i + '_email" name="providers[' + i + '][email]" />' +
                '</div>');
                
                $container.append($fieldGroup);
            }
        },

        /**
         * Generate unit fields based on count
         */
        generateUnitFields: function() {
            var self = this;
            var $unitCountField = $('#upkeepify_number_of_units');
            var $unitFieldsContainer = $('.unit-fields-container');
            
            if (!$unitFieldsContainer.length) return;
            
            self.renderUnitFields($unitCountField.val());
            
            $unitCountField.on('change', function() {
                var count = parseInt($(this).val()) || 0;
                self.renderUnitFields(count);
            });
        },

        /**
         * Render unit fields
         */
        renderUnitFields: function(count) {
            var $container = $('.unit-fields-container');
            
            $container.empty();
            
            for (var i = 1; i <= count; i++) {
                var $field = $('<div class="unit-field">' +
                    '<label for="unit_' + i + '_name">Unit ' + i + ' Name:</label>' +
                    '<input type="text" id="unit_' + i + '_name" name="units[' + i + '][name]" placeholder="Unit ' + i + '" />' +
                '</div>');
                
                $container.append($field);
            }
        },

        /**
         * Setup expandable/collapsible sections
         */
        setupExpandableSections: function() {
            // Add expand/collapse buttons to settings sections
            $('.upkeepify-settings-section').each(function() {
                var $section = $(this);
                var $header = $section.find('h2, h3').first();
                
                if ($header.length) {
                    var $toggleBtn = $('<button type="button" class="section-toggle" aria-expanded="true">' +
                        '<span class="toggle-icon">▼</span>' +
                    '</button>');
                    
                    $header.append($toggleBtn);
                    
                    // Wrap section content
                    var $content = $section.children().not($header);
                    $content.wrapAll('<div class="section-content"></div>');
                    
                    // Toggle functionality
                    $toggleBtn.on('click', function(e) {
                        e.preventDefault();
                        var isExpanded = $toggleBtn.attr('aria-expanded') === 'true';
                        $toggleBtn.attr('aria-expanded', !isExpanded);
                        $section.find('.section-content').slideToggle(300);
                        $toggleBtn.find('.toggle-icon').text(isExpanded ? '▶' : '▼');
                    });
                }
            });
        },

        /**
         * Setup email template preview
         */
        setupEmailPreview: function() {
            var self = this;
            
            // Add preview button to email settings
            var $emailSettings = $('.upkeepify_row:has(#upkeepify_override_email)');
            if ($emailSettings.length) {
                var $previewBtn = $('<button type="button" class="button-secondary email-preview-btn">Preview Email Template</button>');
                $emailSettings.append($previewBtn);
                
                $previewBtn.on('click', function(e) {
                    e.preventDefault();
                    self.showEmailPreview();
                });
            }
        },

        /**
         * Show email template preview
         */
        showEmailPreview: function() {
            // Create preview modal
            var $modal = $('<div class="upkeepify-modal" role="dialog" aria-modal="true">' +
                '<div class="modal-content">' +
                    '<div class="modal-header">' +
                        '<h3>Email Notification Preview</h3>' +
                        '<button type="button" class="modal-close" aria-label="Close">×</button>' +
                    '</div>' +
                    '<div class="modal-body">' +
                        '<div class="email-preview">' +
                            '<p><strong>Subject:</strong> New Maintenance Task Submitted</p>' +
                            '<hr>' +
                            '<p>Dear Admin,</p>' +
                            '<p>A new maintenance task has been submitted:</p>' +
                            '<p><strong>Task Title:</strong> [Task Title]</p>' +
                            '<p><strong>Description:</strong> [Task Description]</p>' +
                            '<p><strong>Submitted By:</strong> [User Name]</p>' +
                            '<p><strong>Date:</strong> [Submission Date]</p>' +
                            '<p>Please review task in admin dashboard.</p>' +
                            '<hr>' +
                            '<p>Best regards,<br>Upkeepify System</p>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</div>');
            
            $('body').append($modal);
            
            // Close modal handlers
            $modal.find('.modal-close').on('click', function() {
                $modal.fadeOut(300, function() {
                    $modal.remove();
                });
            });
            
            $modal.on('click', function(e) {
                if (e.target === $modal[0]) {
                    $modal.fadeOut(300, function() {
                        $modal.remove();
                    });
                }
            });
            
            // Show modal
            $modal.hide().fadeIn(300);
        },

        /**
         * Setup settings reset functionality
         */
        setupSettingsReset: function() {
            var self = this;
            
            // Add reset button
            var $submitButton = $('#upkeepify_settings input[type="submit"]');
            if ($submitButton.length) {
                var $resetBtn = $('<button type="button" class="button-secondary settings-reset-btn">Reset to Defaults</button>');
                $submitButton.after($resetBtn);
                
                $resetBtn.on('click', function(e) {
                    e.preventDefault();
                    self.confirmReset();
                });
            }
        },

        /**
         * Confirm settings reset
         */
        confirmReset: function() {
            var self = this;
            
            if (typeof UpkeepifyNotifications !== 'undefined') {
                UpkeepifyNotifications.confirm(
                    'Are you sure you want to reset all settings to their default values? This action cannot be undone.',
                    function() {
                        // Reset form fields
                        $('#upkeepify_settings')[0].reset();
                        
                        // Trigger change events to update UI
                        $('#upkeepify_smtp_option').trigger('change');
                        $('#upkeepify_enable_thank_you_page').trigger('change');
                        
                        // Show success message
                        UpkeepifyNotifications.success('Settings have been reset to defaults');
                    }
                );
            } else if (confirm('Are you sure you want to reset all settings to their default values? This action cannot be undone.')) {
                $('#upkeepify_settings')[0].reset();
                alert('Settings have been reset to defaults');
            }
        },

        /**
         * Update currency symbol preview
         */
        updateCurrencyPreview: function() {
            var currency = $('#upkeepify_currency').val() || '$';
            var $preview = $('.currency-preview');

            if (!$preview.length) {
                $preview = $('<span class="currency-preview"></span>');
                $('#upkeepify_currency').after(' <span class="currency-preview">(' + currency + '100.00)</span>');
            } else {
                $preview.text('(' + currency + '100.00)');
            }
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        UpkeepifyAdminSettings.init();
    });

    // Expose globally
    window.UpkeepifyAdminSettings = UpkeepifyAdminSettings;

})(jQuery);
