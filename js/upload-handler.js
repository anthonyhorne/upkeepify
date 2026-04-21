/**
 * Upkeepify Upload Handler
 * Enhanced file upload functionality with preview, drag-and-drop, and progress tracking
 */

(function($) {
    'use strict';

    var UpkeepifyUpload = {
        
        maxFileSize: 2 * 1024 * 1024, // 2MB
        maxImageDimension: 1600,
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
            var self = this;
            var files = $input[0].files;
            var selectedFiles = Array.prototype.slice.call(files || []);
            
            // Clear previous state
            this.clearPreview($container);
            
            if (!files || files.length === 0) {
                return;
            }

            $input.data('upkeepify-processing', true);
            this.showProcessingState($container, files);

            this.prepareFiles(files).then(function(preparedFiles) {
                var validation;
                var filesChanged = self.filesChanged(selectedFiles, preparedFiles);

                $input.removeData('upkeepify-processing');

                if (!preparedFiles.length) {
                    self.showError($container, 'We could not prepare that photo on this device. Please try again.');
                    $input.val('');
                    self.notifyValidation($input);
                    return;
                }

                if (filesChanged && !UpkeepifyUtils.setInputFiles($input[0], preparedFiles)) {
                    self.showError($container, 'We could not prepare that photo on this device. Please try again.');
                    $input.val('');
                    self.notifyValidation($input);
                    return;
                }

                validation = self.validateFiles(preparedFiles);

                if (!validation.valid) {
                    self.showError($container, validation.message);
                    $input.val('');
                    self.notifyValidation($input);
                    return;
                }

                self.showFileInfo($container, preparedFiles);

                if (preparedFiles[0].type.indexOf('image/') === 0) {
                    self.showImagePreview($container, preparedFiles[0]);
                }

                self.addRemoveButton($container, $input);
                self.notifyValidation($input);
            }).catch(function() {
                $input.removeData('upkeepify-processing');
                self.showError($container, 'We could not prepare that photo on this device. Please try again.');
                $input.val('');
                self.notifyValidation($input);
            });
        },

        /**
         * Prepare selected files for upload.
         * @param {FileList} files - Selected files
         * @return {Promise<File[]>} Promise resolving to prepared files
         */
        prepareFiles: function(files) {
            var self = this;
            var selectedFiles = Array.prototype.slice.call(files || []);

            return Promise.all(selectedFiles.map(function(file) {
                return self.prepareFile(file);
            }));
        },

        /**
         * Prepare a single file for upload, optimizing images when needed.
         * @param {File} file - Selected file
         * @return {Promise<File>} Promise resolving to the prepared file
         */
        prepareFile: function(file) {
            if (!file || !UpkeepifyUtils.canOptimizeImage(file) || file.size <= this.maxFileSize) {
                return Promise.resolve(file);
            }

            return UpkeepifyUtils.optimizeImageFile(file, {
                maxBytes: this.maxFileSize,
                maxDimension: this.maxImageDimension
            });
        },

        /**
         * Determine whether prepared files differ from the original selection.
         * @param {File[]} originalFiles - Files from the initial input event
         * @param {File[]} preparedFiles - Files after preprocessing
         * @return {boolean} True if the input needs to be replaced
         */
        filesChanged: function(originalFiles, preparedFiles) {
            var index;

            if (originalFiles.length !== preparedFiles.length) {
                return true;
            }

            for (index = 0; index < originalFiles.length; index++) {
                if (originalFiles[index] !== preparedFiles[index]) {
                    return true;
                }
            }

            return false;
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
         * Validate all selected files.
         * @param {File[]} files - Files to validate
         * @return {Object} Validation result
         */
        validateFiles: function(files) {
            var index;
            var result;

            for (index = 0; index < files.length; index++) {
                result = this.validateFile(files[index]);

                if (!result.valid) {
                    return result;
                }
            }

            return { valid: true };
        },

        /**
         * Show file information
         * @param {jQuery} $container - Preview container
         * @param {File[]} files - File objects
         */
        showFileInfo: function($container, files) {
            var $fileInfo = $container.find('.upkeepify-file-info');
            var $dropZone = $container.find('.upkeepify-drop-zone');
            var file = files[0];
            var totalSize = files.reduce(function(sum, currentFile) {
                return sum + currentFile.size;
            }, 0);
            var fileLabel = files.length === 1
                ? UpkeepifyUtils.escapeHtml(file.name)
                : files.length + ' files selected';
            var statusLabel = file.upkeepifyOptimized
                ? '✓ Reduced for upload'
                : '✓ Valid file';
            
            // Hide drop zone
            $dropZone.hide();
            
            // Show file info
            $fileInfo.html(
                '<div class="file-details">' +
                    '<span class="file-name">' + fileLabel + '</span>' +
                    '<span class="file-size">' + UpkeepifyUtils.formatFileSize(totalSize) + '</span>' +
                    '<span class="file-status success">' + statusLabel + '</span>' +
                '</div>'
            ).show();
        },

        /**
         * Show processing state while an image is resized/compressed.
         * @param {jQuery} $container - Preview container
         * @param {FileList} files - Selected files
         */
        showProcessingState: function($container, files) {
            var $fileInfo = $container.find('.upkeepify-file-info');
            var $dropZone = $container.find('.upkeepify-drop-zone');
            var fileCount = files && files.length ? files.length : 1;
            var label = fileCount === 1 ? 'Preparing photo...' : 'Preparing ' + fileCount + ' photos...';

            $dropZone.hide();
            $fileInfo.html(
                '<div class="file-details">' +
                    '<span class="file-name">' + label + '</span>' +
                    '<span class="file-status">Reducing image size for upload</span>' +
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
            var self = this;
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
                self.notifyValidation($input);
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
                self.notifyValidation($input);
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
         * Re-run validation after the file list changes asynchronously.
         * @param {jQuery} $input - File input element
         */
        notifyValidation: function($input) {
            var $form = $input.closest('form');

            if (window.UpkeepifyValidation && typeof window.UpkeepifyValidation.validateFileField === 'function') {
                window.UpkeepifyValidation.validateFileField($input, $form);
            }
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
