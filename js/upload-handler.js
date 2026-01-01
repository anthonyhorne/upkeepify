/**
 * Upkeepify Upload Handler
 * Enhanced file upload functionality with preview, drag-and-drop, and progress tracking
 */

(function($) {
    'use strict';

    var UpkeepifyUpload = {
        
        maxFileSize: 2 * 1024 * 1024, // 2MB
        validTypes: ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'],
        
        /**
         * Initialize upload handlers
         */
        init: function() {
            this.setupFileInputs();
            this.setupDragAndDrop();
            this.setupUploadProgress();
            this.bindRemoveButtons();
        },

        /**
         * Setup file input handlers
         */
        setupFileInputs: function() {
            var self = this;
            
            $('input[type="file"]').each(function() {
                var $input = $(this);
                
                // Create preview container after input
                var $previewContainer = self.createPreviewContainer($input);
                $input.after($previewContainer);
                
                // Handle file selection
                $input.on('change', function(e) {
                    self.handleFileSelect($(this), $previewContainer);
                });
            });
        },

        /**
         * Create preview container for file input
         * @param {jQuery} $input - File input element
         * @return {jQuery} Preview container element
         */
        createPreviewContainer: function($input) {
            var $container = $('<div class="upkeepify-upload-preview"></div>');
            
            // Add drag-drop zone
            var $dropZone = $('<div class="upkeepify-drop-zone">' +
                '<div class="drop-zone-content">' +
                    '<svg class="upload-icon" viewBox="0 0 24 24" width="48" height="48">' +
                        '<path fill="currentColor" d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM14 13v4h-4v-4H7l5-5 5 5h-3z"/>' +
                    '</svg>' +
                    '<p>Drag and drop an image here or click to browse</p>' +
                    '<p class="file-size-limit">Maximum file size: 2MB</p>' +
                '</div>' +
            '</div>');
            
            $container.append($dropZone);
            
            // Add file info container
            var $fileInfo = $('<div class="upkeepify-file-info" style="display:none;"></div>');
            $container.append($fileInfo);
            
            // Add preview image container
            var $imagePreview = $('<div class="upkeepify-image-preview"></div>');
            $container.append($imagePreview);
            
            // Add error message container
            var $errorMessage = $('<div class="upkeepify-upload-error" style="display:none;"></div>');
            $container.append($errorMessage);
            
            return $container;
        },

        /**
         * Setup drag and drop functionality
         */
        setupDragAndDrop: function() {
            var self = this;
            
            $(document).on('dragover dragenter', '.upkeepify-drop-zone', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).addClass('drag-over');
            });
            
            $(document).on('dragleave dragend', '.upkeepify-drop-zone', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('drag-over');
            });
            
            $(document).on('drop', '.upkeepify-drop-zone', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('drag-over');
                
                var files = e.originalEvent.dataTransfer.files;
                if (files.length > 0) {
                    var $input = $(this).closest('.upkeepify-upload-preview').prev('input[type="file"]');
                    $input[0].files = files;
                    $input.trigger('change');
                }
            });
            
            // Click on drop zone triggers file input
            $(document).on('click', '.upkeepify-drop-zone', function(e) {
                var $input = $(this).closest('.upkeepify-upload-preview').prev('input[type="file"]');
                $input.click();
            });
        },

        /**
         * Handle file selection
         * @param {jQuery} $input - File input element
         * @param {jQuery} $container - Preview container element
         */
        handleFileSelect: function($input, $container) {
            var files = $input[0].files;
            
            // Clear previous state
            this.clearPreview($container);
            
            if (!files || files.length === 0) {
                return;
            }
            
            var file = files[0];
            
            // Validate file
            var validation = this.validateFile(file);
            
            if (!validation.valid) {
                this.showError($container, validation.message);
                $input.val(''); // Clear invalid file
                return;
            }
            
            // Show file info
            this.showFileInfo($container, file);
            
            // Show preview if it's an image
            if (file.type.startsWith('image/')) {
                this.showImagePreview($container, file);
            }
            
            // Add remove button
            this.addRemoveButton($container, $input);
            
            // Trigger validation update
            $input.trigger('validate');
        },

        /**
         * Validate uploaded file
         * @param {File} file - File to validate
         * @return {Object} Validation result {valid: boolean, message: string}
         */
        validateFile: function(file) {
            // Check file type
            if (this.validTypes.indexOf(file.type) === -1) {
                return {
                    valid: false,
                    message: 'Invalid file type. Please upload a JPG, PNG, GIF, or WebP image.'
                };
            }
            
            // Check file size
            if (file.size > this.maxFileSize) {
                return {
                    valid: false,
                    message: 'File size exceeds 2MB limit. Please choose a smaller file.'
                };
            }
            
            return { valid: true };
        },

        /**
         * Show file information
         * @param {jQuery} $container - Preview container
         * @param {File} file - File object
         */
        showFileInfo: function($container, file) {
            var $fileInfo = $container.find('.upkeepify-file-info');
            var $dropZone = $container.find('.upkeepify-drop-zone');
            
            // Hide drop zone
            $dropZone.hide();
            
            // Show file info
            $fileInfo.html(
                '<div class="file-details">' +
                    '<span class="file-name">' + UpkeepifyUtils.escapeHtml(file.name) + '</span>' +
                    '<span class="file-size">' + UpkeepifyUtils.formatFileSize(file.size) + '</span>' +
                    '<span class="file-status success">✓ Valid file</span>' +
                '</div>'
            ).show();
        },

        /**
         * Show image preview
         * @param {jQuery} $container - Preview container
         * @param {File} file - File object
         */
        showImagePreview: function($container, file) {
            var $imagePreview = $container.find('.upkeepify-image-preview');
            var reader = new FileReader();
            
            reader.onload = function(e) {
                $imagePreview.html(
                    '<div class="preview-wrapper">' +
                        '<img src="' + e.target.result + '" alt="Preview" />' +
                    '</div>'
                ).show();
            };
            
            reader.readAsDataURL(file);
        },

        /**
         * Show upload error
         * @param {jQuery} $container - Preview container
         * @param {string} message - Error message
         */
        showError: function($container, message) {
            var $error = $container.find('.upkeepify-upload-error');
            var $dropZone = $container.find('.upkeepify-drop-zone');
            
            // Highlight drop zone
            $dropZone.addClass('error');
            
            // Show error message
            $error.html(
                '<div class="error-message">' +
                    '<svg class="error-icon" viewBox="0 0 24 24" width="20" height="20">' +
                        '<path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>' +
                    '</svg>' +
                    '<span>' + UpkeepifyUtils.escapeHtml(message) + '</span>' +
                '</div>'
            ).show();
            
            // Auto-hide error after 5 seconds
            setTimeout(function() {
                $error.fadeOut(function() {
                    $dropZone.removeClass('error');
                });
            }, 5000);
        },

        /**
         * Add remove button for selected file
         * @param {jQuery} $container - Preview container
         * @param {jQuery} $input - File input element
         */
        addRemoveButton: function($container, $input) {
            var $fileInfo = $container.find('.upkeepify-file-info');
            var $removeBtn = $('<button type="button" class="upkeepify-remove-file" aria-label="Remove file">' +
                '<svg viewBox="0 0 24 24" width="16" height="16">' +
                    '<path fill="currentColor" d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>' +
                '</svg>' +
                'Remove' +
            '</button>');
            
            $fileInfo.find('.file-details').append($removeBtn);
            
            // Handle remove click
            $removeBtn.on('click', function(e) {
                e.preventDefault();
                $input.val('');
                self.clearPreview($container);
            });
        },

        /**
         * Bind remove button handlers
         */
        bindRemoveButtons: function() {
            var self = this;
            
            $(document).on('click', '.upkeepify-remove-file', function(e) {
                e.preventDefault();
                var $container = $(this).closest('.upkeepify-upload-preview');
                var $input = $container.prev('input[type="file"]');
                $input.val('');
                self.clearPreview($container);
            });
        },

        /**
         * Clear preview container
         * @param {jQuery} $container - Preview container
         */
        clearPreview: function($container) {
            $container.find('.upkeepify-drop-zone').show().removeClass('error');
            $container.find('.upkeepify-file-info').hide();
            $container.find('.upkeepify-image-preview').hide().empty();
            $container.find('.upkeepify-upload-error').hide();
        },

        /**
         * Setup upload progress tracking
         */
        setupUploadProgress: function() {
            var self = this;
            
            // Intercept form submission for AJAX upload
            $('form').on('submit', function(e) {
                var $form = $(this);
                var $fileInput = $form.find('input[type="file"]');
                
                if ($fileInput.length && $fileInput[0].files.length > 0) {
                    self.showUploadProgress($form, $fileInput);
                }
            });
        },

        /**
         * Show upload progress
         * @param {jQuery} $form - Form element
         * @param {jQuery} $fileInput - File input element
         */
        showUploadProgress: function($form, $fileInput) {
            var $container = $fileInput.next('.upkeepify-upload-preview');
            var $progressContainer = $('<div class="upkeepify-upload-progress">' +
                '<div class="progress-bar">' +
                    '<div class="progress-fill"></div>' +
                '</div>' +
                '<div class="progress-text">Uploading... 0%</div>' +
                '<div class="loading-spinner"></div>' +
            '</div>');
            
            $container.find('.upkeepify-file-info').after($progressContainer);
            
            // Simulate progress (in real implementation, this would track actual XHR progress)
            var progress = 0;
            var $progressFill = $progressContainer.find('.progress-fill');
            var $progressText = $progressContainer.find('.progress-text');
            
            var progressInterval = setInterval(function() {
                progress += Math.random() * 20;
                if (progress >= 100) {
                    progress = 100;
                    clearInterval(progressInterval);
                    $progressText.text('Upload complete!');
                    $progressContainer.addClass('complete');
                } else {
                    $progressText.text('Uploading... ' + Math.round(progress) + '%');
                }
                $progressFill.css('width', progress + '%');
            }, 200);
        },

        /**
         * Reset upload handler state
         * @param {jQuery} $input - File input element
         */
        reset: function($input) {
            var $container = $input.next('.upkeepify-upload-preview');
            this.clearPreview($container);
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        if (typeof UpkeepifyUtils !== 'undefined') {
            UpkeepifyUpload.init();
        }
    });

    // Expose globally
    window.UpkeepifyUpload = UpkeepifyUpload;

})(jQuery);
