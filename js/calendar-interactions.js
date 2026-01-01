/**
 * Upkeepify Calendar Interactions
 * Enhanced calendar view with interactive date selection and task display
 */

(function($) {
    'use strict';

    var UpkeepifyCalendar = {
        
        currentDate: new Date(),
        selectedDate: null,
        tasksByDate: {},
        
        /**
         * Initialize calendar interactions
         */
        init: function() {
            this.setupCalendars();
            this.loadTaskDates();
            this.bindCalendarEvents();
        },

        /**
         * Setup calendar elements
         */
        setupCalendars: function() {
            var self = this;
            
            $('#upkeepify-task-calendar').each(function() {
                var $calendar = $(this);
                self.createCalendarUI($calendar);
            });
        },

        /**
         * Create calendar UI
         * @param {jQuery} $container - Calendar container
         */
        createCalendarUI: function($container) {
            // Clear existing content
            $container.empty();
            
            // Create calendar header
            var $header = $('<div class="upkeepify-calendar-header">' +
                '<button type="button" class="upkeepify-calendar-nav prev" aria-label="Previous month">←</button>' +
                '<div class="upkeepify-calendar-month"></div>' +
                '<button type="button" class="upkeepify-calendar-nav next" aria-label="Next month">→</button>' +
            '</div>');
            
            // Create day names header
            var $dayNames = $('<div class="upkeepify-calendar-daynames">' +
                '<div class="dayname">Sun</div>' +
                '<div class="dayname">Mon</div>' +
                '<div class="dayname">Tue</div>' +
                '<div class="dayname">Wed</div>' +
                '<div class="dayname">Thu</div>' +
                '<div class="dayname">Fri</div>' +
                '<div class="dayname">Sat</div>' +
            '</div>');
            
            // Create calendar grid
            var $grid = $('<div class="upkeepify-calendar-grid"></div>');
            
            // Create task details panel
            var $taskPanel = $('<div class="upkeepify-calendar-tasks" style="display:none;">' +
                '<div class="task-panel-header">' +
                    '<h3>Tasks for <span class="selected-date"></span></h3>' +
                    '<button type="button" class="close-task-panel" aria-label="Close panel">×</button>' +
                '</div>' +
                '<div class="task-panel-content"></div>' +
            '</div>');
            
            $container.append($header, $dayNames, $grid, $taskPanel);
            
            // Render current month
            this.renderMonth($container);
        },

        /**
         * Load task dates
         */
        loadTaskDates: function() {
            var self = this;
            
            // Get all tasks with dates
            $('.upkeepify-task-item, .task-item, [data-task-date]').each(function() {
                var $task = $(this);
                var taskDate = $task.data('task-date');
                var taskTitle = $task.data('task-title') || $task.find('h2, h3, .task-title').text().trim();
                var taskStatus = $task.data('task-status');
                var taskLink = $task.find('a').first().attr('href');
                
                if (taskDate) {
                    var dateKey = this.formatDateKey(new Date(taskDate));
                    
                    if (!self.tasksByDate[dateKey]) {
                        self.tasksByDate[dateKey] = [];
                    }
                    
                    self.tasksByDate[dateKey].push({
                        title: taskTitle,
                        status: taskStatus,
                        link: taskLink,
                        element: $task
                    });
                }
            }.bind(this));
            
            // Also load from task data if available
            if (typeof upkeepifyCalendarTasks !== 'undefined') {
                $.each(upkeepifyCalendarTasks, function(index, task) {
                    if (task.date) {
                        var dateKey = self.formatDateKey(new Date(task.date));
                        
                        if (!self.tasksByDate[dateKey]) {
                            self.tasksByDate[dateKey] = [];
                        }
                        
                        self.tasksByDate[dateKey].push({
                            title: task.title,
                            status: task.status,
                            link: task.link
                        });
                    }
                });
            }
        },

        /**
         * Format date as key for lookup
         * @param {Date} date - Date object
         * @return {string} Date key (YYYY-MM-DD)
         */
        formatDateKey: function(date) {
            var year = date.getFullYear();
            var month = String(date.getMonth() + 1).padStart(2, '0');
            var day = String(date.getDate()).padStart(2, '0');
            return year + '-' + month + '-' + day;
        },

        /**
         * Render calendar month
         * @param {jQuery} $container - Calendar container
         */
        renderMonth: function($container) {
            var year = this.currentDate.getFullYear();
            var month = this.currentDate.getMonth();
            
            // Update month/year display
            var monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                            'July', 'August', 'September', 'October', 'November', 'December'];
            $container.find('.upkeepify-calendar-month').text(monthNames[month] + ' ' + year);
            
            // Clear grid
            var $grid = $container.find('.upkeepify-calendar-grid');
            $grid.empty();
            
            // Get first day of month and number of days
            var firstDay = new Date(year, month, 1).getDay();
            var daysInMonth = new Date(year, month + 1, 0).getDate();
            var today = new Date();
            
            // Add empty cells for days before first of month
            for (var i = 0; i < firstDay; i++) {
                $grid.append('<div class="calendar-day empty"></div>');
            }
            
            // Add days
            for (var day = 1; day <= daysInMonth; day++) {
                var date = new Date(year, month, day);
                var dateKey = this.formatDateKey(date);
                var tasks = this.tasksByDate[dateKey] || [];
                var taskCount = tasks.length;
                
                var $day = $('<div class="calendar-day" data-date="' + dateKey + '">' +
                    '<span class="day-number">' + day + '</span>' +
                '</div>');
                
                // Add task count indicator
                if (taskCount > 0) {
                    $day.addClass('has-tasks');
                    $day.append('<span class="task-count">' + taskCount + '</span>');
                }
                
                // Highlight today
                if (date.toDateString() === today.toDateString()) {
                    $day.addClass('today');
                }
                
                // Highlight selected date
                if (this.selectedDate && date.toDateString() === this.selectedDate.toDateString()) {
                    $day.addClass('selected');
                }
                
                // Make clickable if has tasks
                if (taskCount > 0) {
                    $day.addClass('clickable').attr('role', 'button').attr('tabindex', '0');
                }
                
                $grid.append($day);
            }
        },

        /**
         * Bind calendar event handlers
         */
        bindCalendarEvents: function() {
            var self = this;
            
            // Previous month button
            $(document).on('click', '.upkeepify-calendar-nav.prev', function(e) {
                e.preventDefault();
                var $container = $(this).closest('#upkeepify-task-calendar');
                self.navigateMonth($container, -1);
            });
            
            // Next month button
            $(document).on('click', '.upkeepify-calendar-nav.next', function(e) {
                e.preventDefault();
                var $container = $(this).closest('#upkeepify-task-calendar');
                self.navigateMonth($container, 1);
            });
            
            // Date click
            $(document).on('click', '.calendar-day.clickable', function(e) {
                e.preventDefault();
                var $container = $(this).closest('#upkeepify-task-calendar');
                var dateKey = $(this).data('date');
                self.selectDate($container, new Date(dateKey));
            });
            
            // Date keyboard navigation
            $(document).on('keypress', '.calendar-day.clickable', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    var $container = $(this).closest('#upkeepify-task-calendar');
                    var dateKey = $(this).data('date');
                    self.selectDate($container, new Date(dateKey));
                }
            });
            
            // Close task panel
            $(document).on('click', '.close-task-panel', function(e) {
                e.preventDefault();
                var $container = $(this).closest('#upkeepify-task-calendar');
                self.closeTaskPanel($container);
            });
        },

        /**
         * Navigate to previous/next month
         * @param {jQuery} $container - Calendar container
         * @param {number} direction - Direction (-1 for prev, 1 for next)
         */
        navigateMonth: function($container, direction) {
            // Add transition class
            $container.addClass('month-transitioning');
            
            // Navigate
            this.currentDate.setMonth(this.currentDate.getMonth() + direction);
            
            // Re-render
            this.renderMonth($container);
            
            // Remove transition class after animation
            setTimeout(function() {
                $container.removeClass('month-transitioning');
            }, 300);
        },

        /**
         * Select a date and show tasks
         * @param {jQuery} $container - Calendar container
         * @param {Date} date - Selected date
         */
        selectDate: function($container, date) {
            this.selectedDate = date;
            
            // Update selected state
            $container.find('.calendar-day').removeClass('selected');
            $container.find('.calendar-day[data-date="' + this.formatDateKey(date) + '"]').addClass('selected');
            
            // Show tasks for date
            this.showTasksForDate($container, date);
        },

        /**
         * Show tasks for selected date
         * @param {jQuery} $container - Calendar container
         * @param {Date} date - Date to show tasks for
         */
        showTasksForDate: function($container, date) {
            var dateKey = this.formatDateKey(date);
            var tasks = this.tasksByDate[dateKey] || [];
            var $taskPanel = $container.find('.upkeepify-calendar-tasks');
            var $taskContent = $taskPanel.find('.task-panel-content');
            var $selectedDate = $taskPanel.find('.selected-date');
            
            // Update selected date display
            var options = { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' };
            $selectedDate.text(date.toLocaleDateString(undefined, options));
            
            // Clear and populate task list
            $taskContent.empty();
            
            if (tasks.length === 0) {
                $taskContent.html('<p class="no-tasks">No tasks scheduled for this date.</p>');
            } else {
                var $taskList = $('<ul class="calendar-task-list"></ul>');
                
                tasks.forEach(function(task) {
                    var $taskItem = $('<li class="calendar-task-item">' +
                        '<span class="task-status-indicator status-' + (task.status || 'pending') + '"></span>' +
                        (task.link ? 
                            '<a href="' + task.link + '" class="task-title">' + UpkeepifyUtils.escapeHtml(task.title) + '</a>' :
                            '<span class="task-title">' + UpkeepifyUtils.escapeHtml(task.title) + '</span>'
                        ) +
                        (task.status ? '<span class="task-status">' + UpkeepifyUtils.escapeHtml(task.status) + '</span>' : '') +
                    '</li>');
                    
                    $taskList.append($taskItem);
                });
                
                $taskContent.append($taskList);
            }
            
            // Show task panel with animation
            $taskPanel.slideDown(300);
            
            // Scroll to task panel
            UpkeepifyUtils.scrollToElement($taskPanel, 20);
        },

        /**
         * Close task panel
         * @param {jQuery} $container - Calendar container
         */
        closeTaskPanel: function($container) {
            var $taskPanel = $container.find('.upkeepify-calendar-tasks');
            $taskPanel.slideUp(300);
            
            // Clear selected date
            this.selectedDate = null;
            $container.find('.calendar-day').removeClass('selected');
        },

        /**
         * Go to today
         * @param {jQuery} $container - Calendar container
         */
        goToToday: function($container) {
            this.currentDate = new Date();
            this.renderMonth($container);
        },

        /**
         * Add task to calendar
         * @param {Date} date - Task date
         * @param {Object} task - Task object
         */
        addTask: function(date, task) {
            var dateKey = this.formatDateKey(date);
            
            if (!this.tasksByDate[dateKey]) {
                this.tasksByDate[dateKey] = [];
            }
            
            this.tasksByDate[dateKey].push(task);
            
            // Re-render calendar
            $('#upkeepify-task-calendar').each(function() {
                self.renderMonth($(this));
            });
        },

        /**
         * Remove task from calendar
         * @param {Date} date - Task date
         * @param {string} taskTitle - Task title
         */
        removeTask: function(date, taskTitle) {
            var dateKey = this.formatDateKey(date);
            
            if (this.tasksByDate[dateKey]) {
                this.tasksByDate[dateKey] = this.tasksByDate[dateKey].filter(function(task) {
                    return task.title !== taskTitle;
                });
                
                // Remove empty array
                if (this.tasksByDate[dateKey].length === 0) {
                    delete this.tasksByDate[dateKey];
                }
            }
            
            // Re-render calendar
            $('#upkeepify-task-calendar').each(function() {
                self.renderMonth($(this));
            });
        },

        /**
         * Refresh calendar data
         */
        refresh: function() {
            this.tasksByDate = {};
            this.loadTaskDates();
            
            $('#upkeepify-task-calendar').each(function() {
                self.renderMonth($(this));
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        if (typeof UpkeepifyUtils !== 'undefined') {
            UpkeepifyCalendar.init();
        }
    });

    // Expose globally
    window.UpkeepifyCalendar = UpkeepifyCalendar;

})(jQuery);
