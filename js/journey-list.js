/**
 * Journey List JavaScript Module
 * Handles journey list interactions, filtering, and bulk actions
 */

(function($) {
  'use strict';

  window.JourneyList = {
    currentPage: 1,
    itemsPerPage: 12,
    totalItems: 0,
    selectedJourneys: [],
    currentFilters: {},
    
    init: function() {
      this.bindEvents();
      this.initializeFilters();
      this.loadJourneys();
    },

    bindEvents: function() {
      var self = this;

      // Search functionality
      $('#journey-search').on('input', $.debounce(300, function() {
        self.applyFilters();
      }));

      // Filter dropdowns
      $('#status-filter, #step-type-filter, #date-filter').on('change', function() {
        self.applyFilters();
      });

      // Filter buttons
      $('#apply-filters').on('click', function() {
        self.applyFilters();
      });

      $('#clear-filters').on('click', function() {
        self.clearFilters();
      });

      // Journey actions
      $(document).on('click', '.btn-edit', function(e) {
        e.preventDefault();
        var journeyId = $(this).data('journey-id');
        self.editJourney(journeyId);
      });

      $(document).on('click', '.btn-view', function(e) {
        e.preventDefault();
        var journeyId = $(this).data('journey-id');
        self.viewJourney(journeyId);
      });

      $(document).on('click', '.btn-duplicate', function(e) {
        e.preventDefault();
        var journeyId = $(this).data('journey-id');
        self.duplicateJourney(journeyId);
      });

      $(document).on('click', '.btn-delete', function(e) {
        e.preventDefault();
        var journeyId = $(this).data('journey-id');
        self.deleteJourney(journeyId);
      });

      $(document).on('click', '.btn-pause', function(e) {
        e.preventDefault();
        var journeyId = $(this).data('journey-id');
        self.pauseJourney(journeyId);
      });

      // Bulk actions
      $(document).on('change', '.journey-checkbox', function() {
        self.handleJourneySelection($(this));
      });

      $('#bulk-select-all').on('change', function() {
        self.selectAllJourneys($(this).is(':checked'));
      });

      $('#bulk-delete').on('click', function() {
        self.bulkDeleteJourneys();
      });

      $('#bulk-pause').on('click', function() {
        self.bulkPauseJourneys();
      });

      $('#bulk-activate').on('click', function() {
        self.bulkActivateJourneys();
      });

      // Pagination
      $(document).on('click', '.pagination-btn', function(e) {
        e.preventDefault();
        if (!$(this).hasClass('disabled')) {
          var page = $(this).data('page');
          self.loadPage(page);
        }
      });

      // Keyboard shortcuts
      $(document).on('keydown', function(e) {
        if (e.ctrlKey || e.metaKey) {
          switch (e.key) {
            case 'n':
              e.preventDefault();
              window.location.href = CRM.url('civicrm/journey/builder', 'reset=1');
              break;
            case 'f':
              e.preventDefault();
              $('#journey-search').focus();
              break;
          }
        }
      });

      // Auto-refresh
      if (window.journeyListAutoRefresh) {
        setInterval(function() {
          self.refreshJourneyStats();
        }, 30000);
      }
    },

    initializeFilters: function() {
      // Initialize date picker if available
      if ($.fn.datepicker) {
        $('#date-from, #date-to').datepicker({
          dateFormat: 'yy-mm-dd',
          changeMonth: true,
          changeYear: true,
          onSelect: function() {
            JourneyList.applyFilters();
          }
        });
      }

      // Load filter options
      this.loadFilterOptions();
    },

    loadFilterOptions: function() {
      var self = this;
      
      // Load available journey statuses
      CRM.api3('Journey', 'getoptions', {
        field: 'status',
        context: 'search'
      }).done(function(result) {
        var statusFilter = $('#status-filter');
        statusFilter.empty().append('<option value="">All Statuses</option>');
        
        $.each(result.values, function(value, label) {
          statusFilter.append('<option value="' + value + '">' + label + '</option>');
        });
      });

      // Load available step types
      CRM.api3('JourneyStep', 'getoptions', {
        field: 'type',
        context: 'search'
      }).done(function(result) {
        var stepTypeFilter = $('#step-type-filter');
        stepTypeFilter.empty().append('<option value="">All Step Types</option>');
        
        $.each(result.values, function(value, label) {
          stepTypeFilter.append('<option value="' + value + '">' + label + '</option>');
        });
      });
    },

    applyFilters: function() {
      this.currentFilters = {
        search: $('#journey-search').val(),
        status: $('#status-filter').val(),
        step_type: $('#step-type-filter').val(),
        date_from: $('#date-from').val(),
        date_to: $('#date-to').val(),
        date_filter: $('#date-filter').val()
      };

      this.currentPage = 1;
      this.loadJourneys();
    },

    clearFilters: function() {
      $('#journey-search').val('');
      $('#status-filter').val('');
      $('#step-type-filter').val('');
      $('#date-from').val('');
      $('#date-to').val('');
      $('#date-filter').val('');

      this.currentFilters = {};
      this.currentPage = 1;
      this.loadJourneys();
    },

    loadJourneys: function() {
      var self = this;
      
      this.showLoading();

      var params = {
        sequential: 1,
        options: {
          limit: this.itemsPerPage,
          offset: (this.currentPage - 1) * this.itemsPerPage,
          sort: 'created_date DESC'
        },
        return: [
          'id', 'name', 'description', 'status', 'created_date',
          'activated_date', 'total_participants', 'active_participants',
          'completed_participants', 'step_count', 'email_count'
        ]
      };

      // Apply filters
      if (this.currentFilters.search) {
        params.name = {'LIKE': '%' + this.currentFilters.search + '%'};
      }
      if (this.currentFilters.status) {
        params.status = this.currentFilters.status;
      }
      if (this.currentFilters.date_from) {
        params.created_date = {'>=' : this.currentFilters.date_from};
      }
      if (this.currentFilters.date_to) {
        if (params.created_date) {
          params.created_date['<='] = this.currentFilters.date_to + ' 23:59:59';
        } else {
          params.created_date = {'<=' : this.currentFilters.date_to + ' 23:59:59'};
        }
      }

      CRM.api3('Journey', 'get', params).done(function(result) {
        self.renderJourneys(result.values);
        self.totalItems = result.count;
        self.renderPagination();
        self.hideLoading();
      }).fail(function(error) {
        self.showError('Failed to load journeys: ' + error.error_message);
        self.hideLoading();
      });
    },

    renderJourneys: function(journeys) {
      var container = $('#journey-grid');
      
      if (journeys.length === 0) {
        this.showEmptyState();
        return;
      }

      container.empty();

      var self = this;
      journeys.forEach(function(journey) {
        var card = self.createJourneyCard(journey);
        container.append(card);
      });

      // Update journey counts
      this.updateJourneyCounts(journeys);
    },

    createJourneyCard: function(journey) {
      var statusClass = 'status-' + journey.status.toLowerCase();
      var statusLabel = journey.status.charAt(0).toUpperCase() + journey.status.slice(1);
      
      var completionRate = journey.total_participants > 0 ? 
        Math.round((journey.completed_participants / journey.total_participants) * 100) : 0;

      return $(`
        <div class="journey-card" data-journey-id="${journey.id}">
          <input type="checkbox" class="journey-checkbox" data-journey-id="${journey.id}">
          <div class="journey-status ${statusClass}">${statusLabel}</div>
          
          <div class="journey-card-header">
            <h3 class="journey-title">
              <a href="${CRM.url('civicrm/journey/builder', 'reset=1&id=' + journey.id)}">
                ${this.escapeHtml(journey.name)}
              </a>
            </h3>
            
            ${journey.description ? `
              <p class="journey-description">${this.escapeHtml(journey.description)}</p>
            ` : ''}
            
            <div class="journey-meta">
              <span class="meta-item">
                <i class="meta-icon crm-i fa-calendar"></i>
                Created ${this.formatDate(journey.created_date)}
              </span>
              ${journey.activated_date ? `
                <span class="meta-item">
                  <i class="meta-icon crm-i fa-play"></i>
                  Activated ${this.formatDate(journey.activated_date)}
                </span>
              ` : ''}
              <span class="meta-item">
                <i class="meta-icon crm-i fa-list"></i>
                ${journey.step_count || 0} steps
              </span>
            </div>
          </div>

          <div class="journey-stats">
            <div class="stats-grid">
              <div class="stat-item">
                <div class="stat-number">${journey.total_participants || 0}</div>
                <div class="stat-label">Participants</div>
              </div>
              <div class="stat-item">
                <div class="stat-number">${completionRate}%</div>
                <div class="stat-label">Complete</div>
              </div>
              <div class="stat-item">
                <div class="stat-number">${journey.email_count || 0}</div>
                <div class="stat-label">Emails</div>
              </div>
            </div>
          </div>

          <div class="journey-actions">
            <div class="action-buttons">
              <a href="${CRM.url('civicrm/journey/builder', 'reset=1&id=' + journey.id)}" 
                 class="btn-action btn-edit" data-journey-id="${journey.id}">
                <i class="crm-i fa-edit"></i> Edit
              </a>
              <a href="${CRM.url('civicrm/journey/analytics', 'reset=1&id=' + journey.id)}" 
                 class="btn-action btn-view" data-journey-id="${journey.id}">
                <i class="crm-i fa-bar-chart"></i> View
              </a>
              <button class="btn-action btn-duplicate" data-journey-id="${journey.id}">
                <i class="crm-i fa-copy"></i> Copy
              </button>
              ${journey.status === 'active' ? `
                <button class="btn-action btn-pause" data-journey-id="${journey.id}">
                  <i class="crm-i fa-pause"></i> Pause
                </button>
              ` : ''}
              <button class="btn-action btn-delete" data-journey-id="${journey.id}">
                <i class="crm-i fa-trash"></i> Delete
              </button>
            </div>
            
            ${journey.status === 'active' ? `
              <div class="journey-quick-stats">
                <span class="quick-stat-dot"></span>
                ${journey.active_participants || 0} active now
              </div>
            ` : ''}
          </div>
        </div>
      `);
    },

    showEmptyState: function() {
      $('#journey-grid').html(`
        <div class="journey-empty-state">
          <div class="empty-state-icon">
            <i class="crm-i fa-route"></i>
          </div>
          <h3 class="empty-state-title">No Journeys Found</h3>
          <p class="empty-state-description">
            ${Object.keys(this.currentFilters).length > 0 ? 
              'No journeys match your current filters. Try adjusting your search criteria.' :
              'You haven\'t created any customer journeys yet. Get started by creating your first journey.'
            }
          </p>
          <a href="${CRM.url('civicrm/journey/builder', 'reset=1')}" class="btn-create-journey">
            <i class="crm-i fa-plus"></i>
            Create Your First Journey
          </a>
        </div>
      `);
    },

    renderPagination: function() {
      var totalPages = Math.ceil(this.totalItems / this.itemsPerPage);
      if (totalPages <= 1) {
        $('#journey-pagination').hide();
        return;
      }

      var pagination = $('#journey-pagination');
      var controls = pagination.find('.pagination-controls');
      var info = pagination.find('.pagination-info');

      // Update info
      var startItem = ((this.currentPage - 1) * this.itemsPerPage) + 1;
      var endItem = Math.min(this.currentPage * this.itemsPerPage, this.totalItems);
      info.text(`Showing ${startItem}-${endItem} of ${this.totalItems} journeys`);

      // Update controls
      controls.empty();

      // Previous button
      controls.append(`
        <button class="pagination-btn ${this.currentPage === 1 ? 'disabled' : ''}" 
                data-page="${this.currentPage - 1}">
          <i class="crm-i fa-chevron-left"></i>
        </button>
      `);

      // Page numbers
      var startPage = Math.max(1, this.currentPage - 2);
      var endPage = Math.min(totalPages, this.currentPage + 2);

      if (startPage > 1) {
        controls.append('<button class="pagination-btn" data-page="1">1</button>');
        if (startPage > 2) {
          controls.append('<span class="pagination-ellipsis">...</span>');
        }
      }

      for (var i = startPage; i <= endPage; i++) {
        controls.append(`
          <button class="pagination-btn ${i === this.currentPage ? 'active' : ''}" 
                  data-page="${i}">${i}</button>
        `);
      }

      if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
          controls.append('<span class="pagination-ellipsis">...</span>');
        }
        controls.append(`<button class="pagination-btn" data-page="${totalPages}">${totalPages}</button>`);
      }

      // Next button
      controls.append(`
        <button class="pagination-btn ${this.currentPage === totalPages ? 'disabled' : ''}" 
                data-page="${this.currentPage + 1}">
          <i class="crm-i fa-chevron-right"></i>
        </button>
      `);

      pagination.show();
    },

    loadPage: function(page) {
      this.currentPage = page;
      this.loadJourneys();
    },

    // Journey Actions
    editJourney: function(journeyId) {
      window.location.href = CRM.url('civicrm/journey/builder', 'reset=1&id=' + journeyId);
    },

    viewJourney: function(journeyId) {
      window.location.href = CRM.url('civicrm/journey/analytics', 'reset=1&id=' + journeyId);
    },

    duplicateJourney: function(journeyId) {
      var self = this;
      
      if (!confirm('Are you sure you want to duplicate this journey?')) {
        return;
      }

      CRM.api3('Journey', 'duplicate', {
        id: journeyId
      }).done(function(result) {
        self.showSuccess('Journey duplicated successfully');
        self.loadJourneys();
      }).fail(function(error) {
        self.showError('Failed to duplicate journey: ' + error.error_message);
      });
    },

    deleteJourney: function(journeyId) {
      var self = this;
      
      if (!confirm('Are you sure you want to delete this journey? This action cannot be undone.')) {
        return;
      }

      CRM.api3('Journey', 'delete', {
        id: journeyId
      }).done(function(result) {
        self.showSuccess('Journey deleted successfully');
        self.loadJourneys();
      }).fail(function(error) {
        self.showError('Failed to delete journey: ' + error.error_message);
      });
    },

    pauseJourney: function(journeyId) {
      var self = this;
      
      CRM.api3('Journey', 'pause', {
        id: journeyId
      }).done(function(result) {
        self.showSuccess('Journey paused successfully');
        self.loadJourneys();
      }).fail(function(error) {
        self.showError('Failed to pause journey: ' + error.error_message);
      });
    },

    // Bulk Actions
    handleJourneySelection: function(checkbox) {
      var journeyId = checkbox.data('journey-id');
      var isChecked = checkbox.is(':checked');

      if (isChecked) {
        if (this.selectedJourneys.indexOf(journeyId) === -1) {
          this.selectedJourneys.push(journeyId);
        }
      } else {
        var index = this.selectedJourneys.indexOf(journeyId);
        if (index > -1) {
          this.selectedJourneys.splice(index, 1);
        }
      }

      this.updateBulkActions();
    },

    selectAllJourneys: function(selectAll) {
      var self = this;
      
      $('.journey-checkbox').prop('checked', selectAll);
      
      if (selectAll) {
        this.selectedJourneys = [];
        $('.journey-checkbox').each(function() {
          self.selectedJourneys.push($(this).data('journey-id'));
        });
      } else {
        this.selectedJourneys = [];
      }

      this.updateBulkActions();
    },

    updateBulkActions: function() {
      var bulkActions = $('.bulk-actions');
      
      if (this.selectedJourneys.length > 0) {
        bulkActions.addClass('show');
        $('#selected-count').text(this.selectedJourneys.length);
      } else {
        bulkActions.removeClass('show');
      }
    },

    bulkDeleteJourneys: function() {
      var self = this;
      
      if (this.selectedJourneys.length === 0) return;
      
      if (!confirm(`Are you sure you want to delete ${this.selectedJourneys.length} journeys? This action cannot be undone.`)) {
        return;
      }

      var promises = this.selectedJourneys.map(function(journeyId) {
        return CRM.api3('Journey', 'delete', { id: journeyId });
      });

      Promise.all(promises).then(function() {
        self.showSuccess(`${self.selectedJourneys.length} journeys deleted successfully`);
        self.selectedJourneys = [];
        self.updateBulkActions();
        self.loadJourneys();
      }).catch(function(error) {
        self.showError('Failed to delete some journeys');
      });
    },

    bulkPauseJourneys: function() {
      var self = this;
      
      if (this.selectedJourneys.length === 0) return;

      var promises = this.selectedJourneys.map(function(journeyId) {
        return CRM.api3('Journey', 'pause', { id: journeyId });
      });

      Promise.all(promises).then(function() {
        self.showSuccess(`${self.selectedJourneys.length} journeys paused successfully`);
        self.selectedJourneys = [];
        self.updateBulkActions();
        self.loadJourneys();
      }).catch(function(error) {
        self.showError('Failed to pause some journeys');
      });
    },

    bulkActivateJourneys: function() {
      var self = this;
      
      if (this.selectedJourneys.length === 0) return;

      var promises = this.selectedJourneys.map(function(journeyId) {
        return CRM.api3('Journey', 'activate', { id: journeyId });
      });

      Promise.all(promises).then(function() {
        self.showSuccess(`${self.selectedJourneys.length} journeys activated successfully`);
        self.selectedJourneys = [];
        self.updateBulkActions();
        self.loadJourneys();
      }).catch(function(error) {
        self.showError('Failed to activate some journeys');
      });
    },

    // Utility functions
    refreshJourneyStats: function() {
      var self = this;
      var visibleJourneys = $('.journey-card').map(function() {
        return $(this).data('journey-id');
      }).get();

      if (visibleJourneys.length === 0) return;

      CRM.api3('Journey', 'get', {
        id: { 'IN': visibleJourneys },
        return: ['id', 'total_participants', 'active_participants', 'completed_participants']
      }).done(function(result) {
        result.values.forEach(function(journey) {
          var card = $(`.journey-card[data-journey-id="${journey.id}"]`);
          var completionRate = journey.total_participants > 0 ? 
            Math.round((journey.completed_participants / journey.total_participants) * 100) : 0;

          card.find('.stat-number').eq(0).text(journey.total_participants || 0);
          card.find('.stat-number').eq(1).text(completionRate + '%');
          card.find('.journey-quick-stats').html(`
            <span class="quick-stat-dot"></span>
            ${journey.active_participants || 0} active now
          `);
        });
      });
    },

    updateJourneyCounts: function(journeys) {
      var counts = {
        total: journeys.length,
        active: journeys.filter(j => j.status === 'active').length,
        draft: journeys.filter(j => j.status === 'draft').length,
        paused: journeys.filter(j => j.status === 'paused').length,
        completed: journeys.filter(j => j.status === 'completed').length
      };

      // Update any count displays if they exist
      Object.keys(counts).forEach(function(key) {
        $(`.journey-count-${key}`).text(counts[key]);
      });
    },

    showLoading: function() {
      $('#journey-grid').html(`
        <div class="journey-loading">
          <div class="loading-spinner"></div>
        </div>
      `);
    },

    hideLoading: function() {
      // Loading is hidden when content is rendered
    },

    showSuccess: function(message) {
      CRM.alert(message, 'Success', 'success');
    },

    showError: function(message) {
      CRM.alert(message, 'Error', 'error');
    },

    escapeHtml: function(text) {
      var div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    },

    formatDate: function(dateString) {
      var date = new Date(dateString);
      return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
    }
  };

  // jQuery debounce function
  $.debounce = function(delay, fn) {
    var timer = null;
    return function() {
      var context = this, args = arguments;
      clearTimeout(timer);
      timer = setTimeout(function() {
        fn.apply(context, args);
      }, delay);
    };
  };

  // Initialize when document is ready
  $(document).ready(function() {
    if ($('#journey-grid').length > 0) {
      JourneyList.init();
    }
  });

})(CRM.$);