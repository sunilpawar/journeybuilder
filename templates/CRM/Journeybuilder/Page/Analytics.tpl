<div class="crm-container journey-analytics-container">

  {if $viewType == 'overview'}
    <!-- Overview Analytics -->
    <div class="analytics-header">
      <h1>Journey Analytics Overview</h1>
      <p class="subtitle">Performance metrics across all journeys</p>
    </div>

    <!-- Overall Metrics Cards -->
    <div class="metrics-grid">
      <div class="metric-card">
        <div class="metric-number">{$overallMetrics.total_journeys|default:0}</div>
        <div class="metric-label">Total Journeys</div>
      </div>
      <div class="metric-card">
        <div class="metric-number">{$overallMetrics.active_journeys|default:0}</div>
        <div class="metric-label">Active Journeys</div>
      </div>
      <div class="metric-card">
        <div class="metric-number">{$overallMetrics.total_participants|default:0}</div>
        <div class="metric-label">Total Participants</div>
      </div>
      <div class="metric-card">
        <div class="metric-number">{$overallMetrics.total_emails_sent|default:0}</div>
        <div class="metric-label">Emails Sent</div>
      </div>
    </div>

    <!-- Journeys List -->
    <div class="journeys-analytics">
      <h3>Journey Performance</h3>
      <div class="table-responsive">
        <table class="table table-striped">
          <thead>
            <tr>
              <th>Journey Name</th>
              <th>Status</th>
              <th>Participants</th>
              <th>Completion Rate</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            {foreach from=$journeys item=journey}
              <tr>
                <td>
                  <a href="{crmURL p='civicrm/journey/analytics' q="reset=1&id=`$journey.id`"}">
                    {$journey.name}
                  </a>
                </td>
                <td>
                  <span class="badge badge-{$journey.status}">{$journey.status|capitalize}</span>
                </td>
                <td>{$journey.total_participants}</td>
                <td>{$journey.completion_rate}%</td>
                <td>
                  <a href="{crmURL p='civicrm/journey/analytics' q="reset=1&id=`$journey.id`"}" class="btn btn-sm btn-primary">
                    View Details
                  </a>
                </td>
              </tr>
            {/foreach}
          </tbody>
        </table>
      </div>
    </div>

  {else}
    <!-- Journey Specific Analytics -->
    <div class="analytics-header">
      <div class="header-left">
        <h1>{$journey.name} Analytics</h1>
        <p class="subtitle">Performance metrics and insights</p>
        <div class="journey-meta">
          <span class="meta-item">Status: <strong>{$journey.status|capitalize}</strong></span>
          {if $journey.activated_date}
            <span class="meta-item">Activated: <strong>{$journey.activated_date|crmDate}</strong></span>
          {/if}
        </div>
      </div>
      <div class="header-right">
        <a href="{crmURL p='civicrm/journey/builder' q="reset=1&id=`$journey.id`"}" class="btn btn-secondary">
          <i class="crm-i fa-edit"></i> Edit Journey
        </a>
        <a href="{crmURL p='civicrm/journey/analytics' q='reset=1'}" class="btn btn-outline-secondary">
          <i class="crm-i fa-arrow-left"></i> Back to Overview
        </a>
      </div>
    </div>

    <!-- Key Metrics -->
    <div class="metrics-grid">
      <div class="metric-card">
        <div class="metric-number">{$analytics.overall_stats.total_participants}</div>
        <div class="metric-label">Total Participants</div>
      </div>
      <div class="metric-card">
        <div class="metric-number">{$analytics.overall_stats.active_participants}</div>
        <div class="metric-label">Active Participants</div>
      </div>
      <div class="metric-card">
        <div class="metric-number">{$analytics.overall_stats.completed_participants}</div>
        <div class="metric-label">Completed</div>
      </div>
      <div class="metric-card">
        <div class="metric-number">{$analytics.overall_stats.completion_rate}%</div>
        <div class="metric-label">Completion Rate</div>
      </div>
    </div>

    <!-- Email Performance -->
    <div class="analytics-section">
      <h3>Email Performance</h3>
      <div class="row">
        <div class="col-md-6">
          <div class="chart-container">
            <canvas id="emailPerformanceChart" width="400" height="200"></canvas>
          </div>
        </div>
        <div class="col-md-6">
          <div class="email-stats">
            <div class="stat-row">
              <span class="stat-label">Emails Sent:</span>
              <span class="stat-value">{$analytics.email_metrics.sent|default:0}</span>
            </div>
            <div class="stat-row">
              <span class="stat-label">Emails Opened:</span>
              <span class="stat-value">{$analytics.email_metrics.opened|default:0}</span>
            </div>
            <div class="stat-row">
              <span class="stat-label">Open Rate:</span>
              <span class="stat-value">{$analytics.email_metrics.open_rate|default:0}%</span>
            </div>
            <div class="stat-row">
              <span class="stat-label">Click Rate:</span>
              <span class="stat-value">{$analytics.email_metrics.click_rate|default:0}%</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Step Performance -->
    <div class="analytics-section">
      <h3>Step Performance</h3>
      <div class="table-responsive">
        <table class="table table-striped">
          <thead>
            <tr>
              <th>Step Name</th>
              <th>Type</th>
              <th>Participants</th>
              <th>Emails Sent</th>
              <th>Open Rate</th>
              <th>Click Rate</th>
            </tr>
          </thead>
          <tbody>
            {foreach from=$stepPerformance item=step}
              <tr>
                <td>{$step.name}</td>
                <td>
                  <span class="badge badge-secondary">{$step.type|replace:'_':' '|capitalize}</span>
                </td>
                <td>{$step.participants}</td>
                <td>{$step.avg_time_hours}h avg</td>
                <td>-</td>
                <td>-</td>
              </tr>
            {/foreach}
          </tbody>
        </table>
      </div>
    </div>

    <!-- Timeline Chart -->
    <div class="analytics-section">
      <h3>Activity Timeline</h3>
      <div class="chart-container">
        <canvas id="timelineChart" width="800" height="300"></canvas>
      </div>
    </div>

  {/if}

</div>

<style>
.journey-analytics-container {
  padding: 20px;
}

.analytics-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 30px;
  padding-bottom: 20px;
  border-bottom: 1px solid #e9ecef;
}

.analytics-header h1 {
  margin: 0 0 5px 0;
  color: #2c3e50;
}

.subtitle {
  color: #6c757d;
  margin: 0 0 10px 0;
}

.journey-meta .meta-item {
  margin-right: 20px;
  font-size: 14px;
  color: #495057;
}

.metrics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.metric-card {
  background: #fff;
  border: 1px solid #e9ecef;
  border-radius: 8px;
  padding: 20px;
  text-align: center;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.metric-number {
  font-size: 2.5rem;
  font-weight: 700;
  color: #007bff;
  margin-bottom: 5px;
}

.metric-label {
  color: #6c757d;
  font-size: 14px;
  font-weight: 500;
}

.analytics-section {
  background: #fff;
  border: 1px solid #e9ecef;
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 20px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.analytics-section h3 {
  margin: 0 0 20px 0;
  color: #2c3e50;
  font-size: 1.25rem;
}

.chart-container {
  position: relative;
  height: 300px;
  margin: 20px 0;
}

.email-stats {
  padding: 20px;
}

.stat-row {
  display: flex;
  justify-content: space-between;
  padding: 10px 0;
  border-bottom: 1px solid #f8f9fa;
}

.stat-label {
  font-weight: 500;
  color: #495057;
}

.stat-value {
  font-weight: 600;
  color: #007bff;
}

.badge {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
}

.badge-draft { background: #6c757d; color: white; }
.badge-active { background: #28a745; color: white; }
.badge-paused { background: #ffc107; color: black; }
.badge-completed { background: #17a2b8; color: white; }
.badge-archived { background: #dc3545; color: white; }
.badge-secondary { background: #6c757d; color: white; }

.table-responsive {
  border: 1px solid #dee2e6;
  border-radius: 6px;
}

.table th {
  background: #f8f9fa;
  border-top: none;
  font-weight: 600;
  color: #495057;
}

@media (max-width: 768px) {
  .analytics-header {
    flex-direction: column;
    gap: 15px;
  }
  
  .metrics-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>

<script>
{literal}
document.addEventListener('DOMContentLoaded', function() {
  // Initialize charts if Chart.js is available
  if (typeof Chart !== 'undefined') {
    initializeCharts();
  } else {
    // Load Chart.js dynamically
    var script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
    script.onload = initializeCharts;
    document.head.appendChild(script);
  }
});

function initializeCharts() {
  // Email Performance Chart
  var emailChart = document.getElementById('emailPerformanceChart');
  if (emailChart) {
    new Chart(emailChart, {
      type: 'doughnut',
      data: {
        labels: ['Opened', 'Clicked', 'Bounced', 'Not Opened'],
        datasets: [{
          data: [
            {/literal}{$analytics.email_metrics.opened|default:0}{literal},
            {/literal}{$analytics.email_metrics.clicked|default:0}{literal},
            {/literal}{$analytics.email_metrics.bounced|default:0}{literal},
            {/literal}{$analytics.email_metrics.sent|default:0} - {$analytics.email_metrics.opened|default:0}{literal}
          ],
          backgroundColor: ['#28a745', '#007bff', '#dc3545', '#e9ecef']
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
  }

  // Timeline Chart
  var timelineChart = document.getElementById('timelineChart');
  if (timelineChart) {
    new Chart(timelineChart, {
      type: 'line',
      data: {
        labels: ['Day 1', 'Day 2', 'Day 3', 'Day 4', 'Day 5', 'Day 6', 'Day 7'],
        datasets: [{
          label: 'Participants Entered',
          data: [10, 15, 8, 12, 20, 18, 14],
          borderColor: '#007bff',
          backgroundColor: 'rgba(0,123,255,0.1)',
          tension: 0.1
        }, {
          label: 'Emails Sent',
          data: [8, 12, 6, 10, 16, 15, 11],
          borderColor: '#28a745',
          backgroundColor: 'rgba(40,167,69,0.1)',
          tension: 0.1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: true
          }
        }
      }
    });
  }
}
{/literal}
</script>
