/**
 * Upkeepify Notifications
 * Toast-style notification system for user feedback
 */

(function($) {
    'use strict';

    var UpkeepifyNotifications = {
        
        container: null,
        notifications: [],
        maxVisible: 5,
        autoDismissDelay: 5000,
        
        /**
         * Initialize notification system
         */
        init: function() {
            this.createContainer();
            this.setupCloseHandlers();
            this.setupDismissOnHover();
        },

        /**
         * Create notification container
         */
        createContainer: function() {
            if ($('#upkeepify-notifications').length === 0) {
                this.container = $('<div id="upkeepify-notifications" aria-live="polite" aria-atomic="true"></div>');
                $('body').append(this.container);
            } else {
                this.container = $('#upkeepify-notifications');
            }
        },

        /**
         * Show a notification
         * @param {string} message - Notification message
         * @param {string} type - Notification type (success, error, warning, info)
         * @param {Object} options - Additional options
         */
        show: function(message, type, options) {
            options = options || {};
            
            var notification = {
                id: UpkeepifyUtils.generateUniqueId(),
                message: message,
                type: type || 'info',
                dismissible: options.dismissible !== false,
                autoDismiss: options.autoDismiss !== false,
                duration: options.duration || this.autoDismissDelay,
                actions: options.actions || []
            };
            
            this.notifications.push(notification);
            this.renderNotification(notification);
            
            // Limit visible notifications
            this.limitVisibleNotifications();
            
            // Auto-dismiss if enabled
            if (notification.autoDismiss) {
                this.scheduleDismiss(notification);
            }
            
            return notification.id;
        },

        /**
         * Show success notification
         * @param {string} message - Success message
         * @param {Object} options - Additional options
         */
        success: function(message, options) {
            return this.show(message, 'success', options);
        },

        /**
         * Show error notification
         * @param {string} message - Error message
         * @param {Object} options - Additional options
         */
        error: function(message, options) {
            return this.show(message, 'error', options);
        },

        /**
         * Show warning notification
         * @param {string} message - Warning message
         * @param {Object} options - Additional options
         */
        warning: function(message, options) {
            return this.show(message, 'warning', options);
        },

        /**
         * Show info notification
         * @param {string} message - Info message
         * @param {Object} options - Additional options
         */
        info: function(message, options) {
            return this.show(message, 'info', options);
        },

        /**
         * Render notification
         * @param {Object} notification - Notification object
         */
        renderNotification: function(notification) {
            var icons = {
                success: '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="currentColor" d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>',
                error: '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>',
                warning: '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="currentColor" d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>',
                info: '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>'
            };
            
            var $notification = $('<div class="upkeepify-notification notification-' + notification.type + '" id="' + notification.id + '" role="alert">' +
                '<div class="notification-content">' +
                    '<div class="notification-icon">' + icons[notification.type] + '</div>' +
                    '<div class="notification-message">' + UpkeepifyUtils.escapeHtml(notification.message) + '</div>' +
                '</div>' +
            '</div>');
            
            // Add close button if dismissible
            if (notification.dismissible) {
                $notification.append(
                    '<button type="button" class="notification-close" aria-label="Close notification">' +
                        '<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>' +
                    '</button>'
                );
            }
            
            // Add action buttons if provided
            if (notification.actions.length > 0) {
                var $actions = $('<div class="notification-actions"></div>');
                
                notification.actions.forEach(function(action) {
                    var $button = $('<button type="button" class="notification-action ' + (action.primary ? 'primary' : '') + '">' +
                        UpkeepifyUtils.escapeHtml(action.label) +
                    '</button>');
                    
                    if (action.callback) {
                        $button.on('click', action.callback);
                    }
                    
                    $actions.append($button);
                });
                
                $notification.find('.notification-content').append($actions);
            }
            
            // Add to container
            this.container.append($notification);
            
            // Animate in
            setTimeout(function() {
                $notification.addClass('show');
            }, 10);
        },

        /**
         * Dismiss notification
         * @param {string} notificationId - Notification ID
         */
        dismiss: function(notificationId) {
            var self = this;
            var $notification = $('#' + notificationId);
            
            if ($notification.length) {
                $notification.removeClass('show');
                
                setTimeout(function() {
                    $notification.remove();
                    self.notifications = self.notifications.filter(function(n) {
                        return n.id !== notificationId;
                    });
                }, 300);
            }
        },

        /**
         * Schedule auto-dismissal
         * @param {Object} notification - Notification object
         */
        scheduleDismiss: function(notification) {
            var self = this;
            
            setTimeout(function() {
                self.dismiss(notification.id);
            }, notification.duration);
        },

        /**
         * Setup close button handlers
         */
        setupCloseHandlers: function() {
            var self = this;
            
            $(document).on('click', '.notification-close', function(e) {
                e.preventDefault();
                var $notification = $(this).closest('.upkeepify-notification');
                self.dismiss($notification.attr('id'));
            });
        },

        /**
         * Pause auto-dismiss on hover
         */
        setupDismissOnHover: function() {
            var self = this;
            
            $(document).on({
                mouseenter: function() {
                    var $notification = $(this);
                    var notificationId = $notification.attr('id');
                    var notification = self.notifications.find(function(n) {
                        return n.id === notificationId;
                    });
                    
                    if (notification && notification.timeoutId) {
                        clearTimeout(notification.timeoutId);
                    }
                },
                mouseleave: function() {
                    var $notification = $(this);
                    var notificationId = $notification.attr('id');
                    var notification = self.notifications.find(function(n) {
                        return n.id === notificationId;
                    });
                    
                    if (notification && notification.autoDismiss) {
                        self.scheduleDismiss(notification);
                    }
                }
            }, '.upkeepify-notification');
        },

        /**
         * Limit visible notifications
         */
        limitVisibleNotifications: function() {
            var $notifications = this.container.children('.upkeepify-notification');
            
            if ($notifications.length > this.maxVisible) {
                var excess = $notifications.length - this.maxVisible;
                
                for (var i = 0; i < excess; i++) {
                    var $oldestNotification = $notifications.eq(i);
                    this.dismiss($oldestNotification.attr('id'));
                }
            }
        },

        /**
         * Clear all notifications
         */
        clearAll: function() {
            var self = this;
            
            this.container.children('.upkeepify-notification').each(function() {
                self.dismiss($(this).attr('id'));
            });
        },

        /**
         * Show confirmation dialog
         * @param {string} message - Confirmation message
         * @param {Function} onConfirm - Callback when confirmed
         * @param {Function} onCancel - Callback when cancelled (optional)
         */
        confirm: function(message, onConfirm, onCancel) {
            var notificationId = this.show(message, 'warning', {
                autoDismiss: false,
                actions: [
                    {
                        label: 'Cancel',
                        callback: function() {
                            if (typeof onCancel === 'function') {
                                onCancel();
                            }
                            UpkeepifyNotifications.dismiss(notificationId);
                        }
                    },
                    {
                        label: 'Confirm',
                        primary: true,
                        callback: function() {
                            if (typeof onConfirm === 'function') {
                                onConfirm();
                            }
                            UpkeepifyNotifications.dismiss(notificationId);
                        }
                    }
                ]
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        if (typeof UpkeepifyUtils !== 'undefined') {
            UpkeepifyNotifications.init();
        }
    });

    // Expose globally
    window.UpkeepifyNotifications = UpkeepifyNotifications;

})(jQuery);
