/**
 * Journey Analytics JavaScript Module
 * Handles analytics dashboard interactions and chart rendering
 */

(function($) {
  'use strict';

  window.JourneyAnalytics = {
    charts: {},
    
    init: function() {
      this.initializeDatePickers();
      this.initializeFilters();
      this.initializeCharts();
      this.bindEvents();
    },

    initializeDatePickers: function() {
      // Initialize date range picker if available
      if ($.fn.daterangepicker) {
        $('#date-range-picker').daterangepicker({
          ranges: {
            'Today': [moment(), moment()],
            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
          },
          startDate: moment().subtract(29, 'days'),
          endDate: moment(),
          locale: {
            format: 'YYYY-MM-DD'
          }
        }, function(start, end, label) {
          JourneyAnalytics.updateDateRange(start.format('YYYY-MM-DD'), end.format('YYYY-MM-DD'));
        });
      }
    },

    initializeFilters: function() {
      var self = this;
      
      // Journey filter
      $('#journey-filter').on('change', function() {
        self.applyFilters();
      });

      // Status filter
      $('#status-filter').on('change', function() {
        self.applyFilters();
      });

      // Step type filter
      $('#step-type-filter').on('change', function() {
        self.applyFilters();
      });
    },

    initializeCharts: function() {
      this.loadChartLibrary(function() {
        JourneyAnalytics.renderAllCharts();
      });
    },

    loadChartLibrary: function(callback) {
      if (typeof Chart !== 'undefined') {
        callback();
        return;
      }

      // Load Chart.js dynamically
      var script = document.createElement('script');
      script.src = 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js';
      script.onload = callback;
      script.onerror = function() {
        console.error('Failed to load Chart.js library');
      };
      document.head.appendChild(script);
    },

    renderAllCharts: function() {
      this.renderEmailPerformanceChart();
      this.renderTimelineChart();
      this.renderConversionFunnelChart();
      this.renderStepPerformanceChart();
      this.renderParticipantStatusChart();
    },

    renderEmailPerformanceChart: function() {
      var ctx = document.getElementById('emailPerformanceChart');
      if (!ctx) return;

      if (this.charts.emailPerformance) {
        this.charts.emailPerformance.destroy();
      }

      var data = this.getEmailPerformanceData();
      
      this.charts.emailPerformance = new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels: ['Opened', 'Clicked', 'Bounced', 'Not Opened'],
          datasets: [{
            data: [
              data.opened || 0,
              data.clicked || 0,
              data.bounced || 0,
              (data.sent || 0) - (data.opened || 0)
            ],
            backgroundColor: [
              '#28a745',
              '#007bff', 
              '#dc3545',
              '#e9ecef'
            ],
            borderWidth: 2,
            borderColor: '#fff'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom',
              labels: {
                padding: 20,
                usePointStyle: true
              }
            },
            tooltip: {
              callbacks: {
                label: function(context) {
                  var label = context.label || '';
                  var value = context.parsed || 0;
                  var total = context.dataset.data.reduce((a, b) => a + b, 0);
                  var percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                  return label + ': ' + value + ' (' + percentage + '%)';
                }
              }
            }
          }
        }
      });
    },

    renderTimelineChart: function() {
      var ctx = document.getElementById('timelineChart');
      if (!ctx) return;

      if (this.charts.timeline) {
        this.charts.timeline.destroy();
      }

      var timelineData = this.getTimelineData();

      this.charts.timeline = new Chart(ctx, {
        type: 'line',
        data: {
          labels: timelineData.labels,
          datasets: [{
            label: 'Participants Entered',
            data: timelineData.entered,
            borderColor: '#007bff',
            backgroundColor: 'rgba(0,123,255,0.1)',
            tension: 0.4,
            fill: false
          }, {
            label: 'Emails Sent',
            data: timelineData.emails,
            borderColor: '#28a745',
            backgroundColor: 'rgba(40,167,69,0.1)',
            tension: 0.4,
            fill: false
          }, {
            label: 'Emails Opened',
            data: timelineData.opened,
            borderColor: '#17a2b8',
            backgroundColor: 'rgba(23,162,184,0.1)',
            tension: 0.4,
            fill: false
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          interaction: {
            intersect: false,
            mode: 'index'
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                stepSize: 1
              }
            }
          },
          plugins: {
            legend: {
              position: 'top'
            }
          }
        }
      });
    },

    renderConversionFunnelChart: function() {
      var ctx = document.getElementById('conversionFunnelChart');
      if (!ctx) return;

      if (this.charts.funnel) {
        this.charts.funnel.destroy();
      }

      var funnelData = this.getConversionFunnelData();

      this.charts.funnel = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: funnelData.labels,
          datasets: [{
            label: 'Participants',
            data: funnelData.values,
            backgroundColor: [
              'rgba(40, 167, 69, 0.8)',
              'rgba(0, 123, 255, 0.8)',
              'rgba(255, 193, 7, 0.8)',
              'rgba(220, 53, 69, 0.8)',
              'rgba(108, 117, 125, 0.8)'
            ],
            borderColor: [
              'rgba(40, 167, 69, 1)',
              'rgba(0, 123, 255, 1)',
              'rgba(255, 193, 7, 1)',
              'rgba(220, 53, 69, 1)',
              'rgba(108, 117, 125, 1)'
            ],
            borderWidth: 1
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          indexAxis: 'y',
          plugins: {
            legend: {
              display: false
            },
            tooltip: {
              callbacks: {
                label: function(context) {
                  var value = context.parsed.x;
                  var total = Math.max(...context.dataset.data);
                  var percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                  return 'Participants: ' + value + ' (' + percentage + '%)';
                }
              }
            }
          },
          scales: {
            x: {
              beginAtZero: true
            }
          }
        }
      });
    },

    renderStepPerformanceChart: function() {
      var ctx = document.getElementById('stepPerformanceChart');
      if (!ctx) return;

      if (this.charts.stepPerformance) {
        this.charts.stepPerformance.destroy();
      }

      var stepData = this.getStepPerformanceData();

      this.charts.stepPerformance = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: stepData.labels,
          datasets: [{
            label: 'Completion Rate (%)',
            data: stepData.completionRates,
            backgroundColor: 'rgba(0, 123, 255, 0.6)',
            borderColor: 'rgba(0, 123, 255, 1)',
            borderWidth: 1,
            yAxisID: 'y'
          }, {
            label: 'Participants',
            data: stepData.participants,
            type: 'line',
            borderColor: 'rgba(40, 167, 69, 1)',
            backgroundColor: 'rgba(40, 167, 69, 0.1)',
            yAxisID: 'y1',
            tension: 0.4
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          interaction: {
            intersect: false,
            mode: 'index'
          },
          scales: {
            y: {
              type: 'linear',
              display: true,
              position: 'left',
              beginAtZero: true,
              max: 100,
              ticks: {
                callback: function(value) {
                  return value + '%';
                }
              }
            },
            y1: {
              type: 'linear',
              display: true,
              position: 'right',
              beginAtZero: true,
              grid: {
                drawOnChartArea: false,
              },
            }
          }
        }
      });
    },

    renderParticipantStatusChart: function() {
      var ctx = document.getElementById('participantStatusChart');
      if (!ctx) return;

      if (this.charts.participantStatus) {
        this.charts.participantStatus.destroy();
      }

      var statusData = this.getParticipantStatusData();

      this.charts.participantStatus = new Chart(ctx, {
        type: 'pie',
        data: {
          labels: ['Active', 'Completed', 'Paused', 'Exited', 'Error'],
          datasets: [{
            data: [
              statusData.active || 0,
              statusData.completed || 0,
              statusData.paused || 0,
              statusData.exited || 0,
              statusData.error || 0
            ],
            backgroundColor: [
              '#28a745',
              '#007bff',
              '#ffc107',
              '#6c757d',
              '#dc3545'
            ]
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom'
            }
          }
        }
      });
    },

    bindEvents: function() {
      var self = this;

      // Export functionality
      $('#export-csv').on('click', function() {
        self.exportToCSV();
      });

      $('#export-pdf').on('click', function() {
        self.exportToPDF();
      });

      // Refresh data
      $('#refresh-data').on('click', function() {
        self.refreshAnalytics();
      });

      // Real-time updates toggle
      $('#realtime-toggle').on('change', function() {
        if (this.checked) {
          self.startRealTimeUpdates();
        } else {
          self.stopRealTimeUpdates();
        }
      });

      // Chart type switcher
      $('.chart-type-switcher').on('click', function() {
        var chartId = $(this).data('chart');
        var newType = $(this).data('type');
        self.switchChartType(chartId, newType);
      });
    },

    // Data retrieval methods (these would normally call APIs)
    getEmailPerformanceData: function() {
      return window.emailMetrics || {
        sent: 0,
        opened: 0,
        clicked: 0,
        bounced: 0
      };
    },

    getTimelineData: function() {
      return window.timelineData || {
        labels: [],
        entered: [],
        emails: [],
        opened: []
      };
    },

    getConversionFunnelData: function() {
      return window.funnelData || {
        labels: [],
        values: []
      };
    },

    getStepPerformanceData: function() {
      return window.stepPerformanceData || {
        labels: [],
        completionRates: [],
        participants: []
      };
    },

    getParticipantStatusData: function() {
      return window.participantStatusData || {
        active: 0,
        completed: 0,
        paused: 0,
        exited: 0,
        error: 0
      };
    },

    // Utility methods
    applyFilters: function() {
      var filters = {
        journey: $('#journey-filter').val(),
        status: $('#status-filter').val(),
        stepType: $('#step-type-filter').val()
      };

      this.refreshAnalytics(filters);
    },

    updateDateRange: function(startDate, endDate) {
      this.refreshAnalytics({
        startDate: startDate,
        endDate: endDate
      });
    },

    refreshAnalytics: function(filters) {
      var self = this;
      
      // Show loading state
      $('.analytics-loading').show();
      
      // Make API call to refresh data
      CRM.api3('Journey', 'analytics', {
        id: window.journeyId || null,
        filters: filters || {}
      }).done(function(result) {
        // Update global data variables
        window.emailMetrics = result.values.email_metrics;
        window.timelineData = result.values.timeline_data;
        window.funnelData = result.values.funnel_data;
        window.stepPerformanceData = result.values.step_performance;
        window.participantStatusData = result.values.participant_status;
        
        // Re-render charts
        self.renderAllCharts();
        
        // Hide loading state
        $('.analytics-loading').hide();
      }).fail(function(error) {
        console.error('Failed to refresh analytics:', error);
        $('.analytics-loading').hide();
      });
    },

    exportToCSV: function() {
      var csvContent = "data:text/csv;charset=utf-8,";
      csvContent += "Step Name,Type,Participants,Completion Rate\n";
      
      // Add data rows (this would use actual data)
      var stepData = this.getStepPerformanceData();
      for (var i = 0; i < stepData.labels.length; i++) {
        csvContent += stepData.labels[i] + ",";
        csvContent += "Email," + stepData.participants[i] + ",";  
        csvContent += stepData.completionRates[i] + "%\n";
      }
      
      var encodedUri = encodeURI(csvContent);
      var link = document.createElement("a");
      link.setAttribute("href", encodedUri);
      link.setAttribute("download", "journey_analytics.csv");
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    },

    exportToPDF: function() {
      // This would integrate with a PDF library like jsPDF
      alert('PDF export functionality would be implemented here');
    },

    switchChartType: function(chartId, newType) {
      if (this.charts[chartId]) {
        var chart = this.charts[chartId];
        chart.config.type = newType;
        chart.update();
      }
    },

    startRealTimeUpdates: function() {
      var self = this;
      this.realTimeInterval = setInterval(function() {
        self.refreshAnalytics();
      }, 30000); // Update every 30 seconds
    },

    stopRealTimeUpdates: function() {
      if (this.realTimeInterval) {
        clearInterval(this.realTimeInterval);
        this.realTimeInterval = null;
      }
    }
  };

  // Initialize when document is ready
  $(document).ready(function() {
    JourneyAnalytics.init();
  });

})(CRM.$);