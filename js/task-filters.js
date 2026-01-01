/**
 * Upkeepify Task Filters
 * Interactive task filtering functionality with AJAX support
 */

(function($) {
    'use strict';

    var UpkeepifyTaskFilters = {
        
        filters: {},
        loading: false,
        
        /**
         * Initialize task filters
         */
        init: function() {
            this.setupFilterUI();
            this.bindFilterEvents();
            this.loadSavedFilters();
        },

        /**
         * Setup filter UI elements
         */
        setupFilterUI: function() {
            // Find task listing containers
            var $taskContainers = $('.upkeepify-tasks-list-container, .upkeepify-task-list');
            
            $taskContainers.each(function() {
                var $container = $(this);
                var $filterBar = self.createFilterBar($container);
                $container.before($filterBar);
            });
            
            // Add result counter
            this.addResultCounters();
        },

        /**
         * Create filter bar
         * @param {jQuery} $container - Task list container
         * @return {jQuery} Filter bar element
         */
        createFilterBar: function($container) {
            var $filterBar = $('<div class="upkeepify-filter-bar"></div>');
            
            // Get filter options from the task list
            var statusOptions = this.extractFilterOptions($container, 'status');
            var categoryOptions = this.extractFilterOptions($container, 'category');
            var providerOptions = this.extractFilterOptions($container, 'provider');
            
            // Status filter
            if (statusOptions.length > 0) {
                $filterBar.append(this.createFilterSelect('status', 'Filter by Status', statusOptions));
            }
            
            // Category filter
            if (categoryOptions.length > 0) {
                $filterBar.append(this.createFilterSelect('category', 'Filter by Category', categoryOptions));
            }
            
            // Provider filter
            if (providerOptions.length > 0) {
                $filterBar.append(this.createFilterSelect('provider', 'Filter by Provider', providerOptions));
            }
            
            // Date range filter
            $filterBar.append(this.createDateRangeFilter());
            
            // Unit filter
            $filterBar.append(this.createUnitFilter());
            
            // Active filters display
            $filterBar.append('<div class="upkeepify-active-filters"></div>');
            
            // Clear filters button
            $filterBar.append('<button type="button" class="upkeepify-clear-filters">Clear All Filters</button>');
            
            // Results count
            $filterBar.append('<div class="upkeepify-filter-results-count">Showing <span class="count">0</span> tasks</div>');
            
            return $filterBar;
        },

        /**
         * Extract filter options from task list
         * @param {jQuery} $container - Task list container
         * @param {string} filterType - Type of filter
         * @return {Array} Filter options
         */
        extractFilterOptions: function($container, filterType) {
            var options = [];
            var selector = '';
            
            switch(filterType) {
                case 'status':
                    selector = '[data-task-status]';
                    break;
                case 'category':
                    selector = '[data-task-category]';
                    break;
                case 'provider':
                    selector = '[data-task-provider]';
                    break;
            }
            
            $container.find(selector).each(function() {
                var value = $(this).data('task-' + filterType);
                if (value && options.indexOf(value) === -1) {
                    options.push(value);
                }
            });
            
            return options;
        },

        /**
         * Create filter select dropdown
         * @param {string} filterName - Filter name
         * @param {string} label - Filter label
         * @param {Array} options - Filter options
         * @return {jQuery} Filter select element
         */
        createFilterSelect: function(filterName, label, options) {
            var $filterGroup = $('<div class="upkeepify-filter-group" data-filter="' + filterName + '"></div>');
            
            $filterGroup.append('<label for="filter-' + filterName + '">' + label + '</label>');
            
            var $select = $('<select id="filter-' + filterName + '" class="upkeepify-filter-select" multiple>');
            $select.append('<option value="">All</option>');
            
            options.forEach(function(option) {
                $select.append('<option value="' + UpkeepifyUtils.escapeHtml(option) + '">' + UpkeepifyUtils.escapeHtml(option) + '</option>');
            });
            
            $filterGroup.append($select);
            
            return $filterGroup;
        },

        /**
         * Create date range filter
         * @return {jQuery} Date range filter element
         */
        createDateRangeFilter: function() {
            var $filterGroup = $('<div class="upkeepify-filter-group" data-filter="date-range"></div>');
            
            $filterGroup.append('<label>Date Range</label>');
            
            var $dateInputs = $('<div class="upkeepify-date-range-inputs">' +
                '<input type="date" id="filter-date-from" class="upkeepify-filter-date" placeholder="From" />' +
                '<span>to</span>' +
                '<input type="date" id="filter-date-to" class="upkeepify-filter-date" placeholder="To" />' +
            '</div>');
            
            $filterGroup.append($dateInputs);
            
            return $filterGroup;
        },

        /**
         * Create unit filter
         * @return {jQuery} Unit filter element
         */
        createUnitFilter: function() {
            var $filterGroup = $('<div class="upkeepify-filter-group" data-filter="unit"></div>');
            
            $filterGroup.append('<label for="filter-unit">Filter by Unit</label>');
            
            var $select = $('<select id="filter-unit" class="upkeepify-filter-select">');
            $select.append('<option value="">All Units</option>');
            
            // Get unit count from settings or generate default
            var unitCount = parseInt($('.upkeepify-unit-count').data('unit-count')) || 10;
            
            for (var i = 1; i <= unitCount; i++) {
                $select.append('<option value="' + i + '">Unit ' + i + '</option>');
            }
            
            $filterGroup.append($select);
            
            return $filterGroup;
        },

        /**
         * Add result counters to task lists
         */
        addResultCounters: function() {
            var self = this;
            
            $('.upkeepify-tasks-list, .upkeepify-tasks-by-category, .upkeepify-tasks-by-provider, .upkeepify-tasks-by-status').each(function() {
                var $list = $(this);
                var itemCount = $list.children('li').length;
                
                if ($list.find('.upkeepify-results-count').length === 0) {
                    $list.before('<div class="upkeepify-results-count">Showing ' + itemCount + ' tasks</div>');
                }
            });
        },

        /**
         * Bind filter event handlers
         */
        bindFilterEvents: function() {
            var self = this;
            
            // Filter select changes
            $(document).on('change', '.upkeepify-filter-select', UpkeepifyUtils.debounce(function(e) {
                self.applyFilters($(this).closest('.upkeepify-filter-bar'));
            }, 300));
            
            // Date filter changes
            $(document).on('change', '.upkeepify-filter-date', UpkeepifyUtils.debounce(function(e) {
                self.applyFilters($(this).closest('.upkeepify-filter-bar'));
            }, 300));
            
            // Clear filters button
            $(document).on('click', '.upkeepify-clear-filters', function(e) {
                e.preventDefault();
                self.clearFilters($(this).closest('.upkeepify-filter-bar'));
            });
            
            // Remove individual active filter
            $(document).on('click', '.upkeepify-active-filter', function(e) {
                e.preventDefault();
                self.removeFilter($(this));
            });
        },

        /**
         * Apply filters to task list
         * @param {jQuery} $filterBar - Filter bar element
         */
        applyFilters: function($filterBar) {
            var self = this;
            var $container = $filterBar.next('.upkeepify-tasks-list-container, .upkeepify-task-list, .upkeepify-tasks-list');
            
            if (!$container.length) {
                return;
            }
            
            // Get current filters
            var filters = this.collectFilters($filterBar);
            
            // Show loading state
            $container.addClass('filtering');
            
            // Apply filters
            this.filterTasks($container, filters);
            
            // Update active filters display
            this.updateActiveFilters($filterBar, filters);
            
            // Update result count
            this.updateResultCount($filterBar, $container);
            
            // Save filters to localStorage
            this.saveFilters($filterBar, filters);
            
            // Remove loading state
            $container.removeClass('filtering');
        },

        /**
         * Collect current filter values
         * @param {jQuery} $filterBar - Filter bar element
         * @return {Object} Filter values
         */
        collectFilters: function($filterBar) {
            var filters = {};
            
            // Collect select filters
            $filterBar.find('.upkeepify-filter-select').each(function() {
                var $select = $(this);
                var filterName = $select.closest('.upkeepify-filter-group').data('filter');
                var values = $select.val();
                
                if (values && values.length > 0 && values[0] !== '') {
                    filters[filterName] = Array.isArray(values) ? values : [values];
                }
            });
            
            // Collect date filters
            var dateFrom = $('#filter-date-from', $filterBar).val();
            var dateTo = $('#filter-date-to', $filterBar).val();
            
            if (dateFrom) {
                filters.dateFrom = dateFrom;
            }
            if (dateTo) {
                filters.dateTo = dateTo;
            }
            
            return filters;
        },

        /**
         * Filter tasks based on filter values
         * @param {jQuery} $container - Task list container
         * @param {Object} filters - Filter values
         */
        filterTasks: function($container, filters) {
            var self = this;
            
            $container.children('li, .task-item, .maintenance-task').each(function() {
                var $task = $(this);
                var visible = true;
                
                // Check status filter
                if (filters.status && filters.status.length > 0) {
                    var taskStatus = $task.data('task-status');
                    if (!taskStatus || filters.status.indexOf(taskStatus) === -1) {
                        visible = false;
                    }
                }
                
                // Check category filter
                if (visible && filters.category && filters.category.length > 0) {
                    var taskCategory = $task.data('task-category');
                    if (!taskCategory || filters.category.indexOf(taskCategory) === -1) {
                        visible = false;
                    }
                }
                
                // Check provider filter
                if (visible && filters.provider && filters.provider.length > 0) {
                    var taskProvider = $task.data('task-provider');
                    if (!taskProvider || filters.provider.indexOf(taskProvider) === -1) {
                        visible = false;
                    }
                }
                
                // Check unit filter
                if (visible && filters.unit && filters.unit.length > 0) {
                    var taskUnit = $task.data('task-unit');
                    if (!taskUnit || filters.unit.indexOf(taskUnit.toString()) === -1) {
                        visible = false;
                    }
                }
                
                // Check date filter
                if (visible && (filters.dateFrom || filters.dateTo)) {
                    var taskDate = $task.data('task-date');
                    if (taskDate) {
                        var date = new Date(taskDate);
                        if (filters.dateFrom && date < new Date(filters.dateFrom)) {
                            visible = false;
                        }
                        if (visible && filters.dateTo && date > new Date(filters.dateTo)) {
                            visible = false;
                        }
                    } else {
                        visible = false;
                    }
                }
                
                // Show/hide task
                if (visible) {
                    $task.show().addClass('filter-visible');
                } else {
                    $task.hide().removeClass('filter-visible');
                }
            });
        },

        /**
         * Update active filters display
         * @param {jQuery} $filterBar - Filter bar element
         * @param {Object} filters - Filter values
         */
        updateActiveFilters: function($filterBar, filters) {
            var $activeFilters = $filterBar.find('.upkeepify-active-filters');
            $activeFilters.empty();
            
            var filterLabels = {
                status: 'Status',
                category: 'Category',
                provider: 'Provider',
                unit: 'Unit',
                dateFrom: 'From',
                dateTo: 'To'
            };
            
            // Add active filter tags
            for (var filterName in filters) {
                var values = filters[filterName];
                
                if (Array.isArray(values)) {
                    values.forEach(function(value) {
                        $activeFilters.append(
                            '<button type="button" class="upkeepify-active-filter" data-filter="' + filterName + '" data-value="' + UpkeepifyUtils.escapeHtml(value) + '">' +
                                filterLabels[filterName] + ': ' + UpkeepifyUtils.escapeHtml(value) +
                                '<span class="remove-filter">×</span>' +
                            '</button>'
                        );
                    });
                } else if (filterName === 'dateFrom' || filterName === 'dateTo') {
                    $activeFilters.append(
                        '<button type="button" class="upkeepify-active-filter" data-filter="' + filterName + '">' +
                            filterLabels[filterName] + ': ' + UpkeepifyUtils.escapeHtml(values) +
                            '<span class="remove-filter">×</span>' +
                        '</button>'
                    );
                }
            }
            
            if ($activeFilters.children().length > 0) {
                $activeFilters.show();
            } else {
                $activeFilters.hide();
            }
        },

        /**
         * Update result count
         * @param {jQuery} $filterBar - Filter bar element
         * @param {jQuery} $container - Task list container
         */
        updateResultCount: function($filterBar, $container) {
            var visibleCount = $container.children('.filter-visible').length;
            var totalCount = $container.children().length;
            
            var $count = $filterBar.find('.upkeepify-filter-results-count .count');
            $count.text(visibleCount + ' of ' + totalCount);
            
            // Also update inline result count if exists
            $container.prev('.upkeepify-results-count').text('Showing ' + visibleCount + ' of ' + totalCount + ' tasks');
        },

        /**
         * Clear all filters
         * @param {jQuery} $filterBar - Filter bar element
         */
        clearFilters: function($filterBar) {
            // Reset all filter selects
            $filterBar.find('.upkeepify-filter-select').val('');
            
            // Reset date inputs
            $filterBar.find('.upkeepify-filter-date').val('');
            
            // Apply empty filters
            this.applyFilters($filterBar);
            
            // Remove saved filters
            localStorage.removeItem('upkeepify_filters');
        },

        /**
         * Remove individual filter
         * @param {jQuery} $filterBtn - Filter button element
         */
        removeFilter: function($filterBtn) {
            var filterName = $filterBtn.data('filter');
            var filterValue = $filterBtn.data('value');
            var $filterBar = $filterBtn.closest('.upkeepify-filter-bar');
            
            // Remove from select
            var $select = $('#filter-' + filterName, $filterBar);
            if ($select.length) {
                var currentValue = $select.val();
                if (Array.isArray(currentValue)) {
                    currentValue = currentValue.filter(function(v) {
                        return v !== filterValue;
                    });
                    $select.val(currentValue);
                } else {
                    $select.val('');
                }
            }
            
            // Remove from date inputs
            if (filterName === 'dateFrom' || filterName === 'dateTo') {
                $('#filter-' + filterName, $filterBar).val('');
            }
            
            // Reapply filters
            this.applyFilters($filterBar);
        },

        /**
         * Save filters to localStorage
         * @param {jQuery} $filterBar - Filter bar element
         * @param {Object} filters - Filter values
         */
        saveFilters: function($filterBar, filters) {
            try {
                var pageId = $('body').data('page-id') || 'default';
                localStorage.setItem('upkeepify_filters_' + pageId, JSON.stringify(filters));
            } catch (e) {
                // localStorage not available
            }
        },

        /**
         * Load saved filters
         */
        loadSavedFilters: function() {
            try {
                var pageId = $('body').data('page-id') || 'default';
                var savedFilters = localStorage.getItem('upkeepify_filters_' + pageId);
                
                if (savedFilters) {
                    var filters = JSON.parse(savedFilters);
                    
                    // Restore filter values
                    for (var filterName in filters) {
                        var values = filters[filterName];
                        var $select = $('#filter-' + filterName);
                        
                        if ($select.length && Array.isArray(values)) {
                            $select.val(values);
                        }
                    }
                    
                    // Apply filters
                    $('.upkeepify-filter-bar').each(function() {
                        self.applyFilters($(this));
                    });
                }
            } catch (e) {
                // localStorage not available or invalid data
            }
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        if (typeof UpkeepifyUtils !== 'undefined') {
            UpkeepifyTaskFilters.init();
        }
    });

    // Expose globally
    window.UpkeepifyTaskFilters = UpkeepifyTaskFilters;

})(jQuery);
