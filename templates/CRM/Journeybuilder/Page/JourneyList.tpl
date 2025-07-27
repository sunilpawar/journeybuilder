<div class="crm-container journey-list-container">

  <!-- Page Header -->
  <div class="journey-list-header">
    <div class="header-left">
      <h1>Journey Builder</h1>
      <p class="subtitle">Manage your email marketing journeys</p>
    </div>
    <div class="header-right">
      <a href="{crmURL p='civicrm/journey/builder' q='reset=1'}" class="btn btn-primary">
        <i class="crm-i fa-plus"></i> Create New Journey
      </a>
    </div>
  </div>

  <!-- Stats Dashboard -->
  <div class="journey-stats-grid">
    <div class="stat-card">
      <div class="stat-number">{$stats.overall.total_journeys|default:0}</div>
      <div class="stat-label">Total Journeys</div>
    </div>
    <div class="stat-card">
      <div class="stat-number">{$stats.draft.count|default:0}</div>
      <div class="stat-label">Draft</div>
    </div>
    <div class="stat-card">
      <div class="stat-number">{$stats.active.count|default:0}</div>
      <div class="stat-label">Active</div>
    </div>
    <div class="stat-card">
      <div class="stat-number">{$stats.overall.active_participants|default:0}</div>
      <div class="stat-label">Active Participants</div>
    </div>
  </div>

  <!-- Filters and Search -->
  <div class="journey-filters">
    <div class="filter-section">
      <label>Filter by Status:</label>
      <select id="status-filter" class="form-control">
        <option value="">All Statuses</option>
        <option value="draft" {if $currentStatus == 'draft'}selected{/if}>Draft</option>
        <option value="active" {if $currentStatus == 'active'}selected{/if}>Active</option>
        <option value="paused" {if $currentStatus == 'paused'}selected{/if}>Paused</option>
        <option value="completed" {if $currentStatus == 'completed'}selected{/if}>Completed</option>
        <option value="archived" {if $currentStatus == 'archived'}selected{/if}>Archived</option>
      </select>
    </div>
    
    <div class="search-section">
      <label>Search:</label>
      <div class="search-input-group">
        <input type="text" id="journey-search" class="form-control" 
               placeholder="Search journeys..." value="{$currentSearch}">
        <button type="button" class="btn btn-secondary" onclick="searchJourneys()">
          <i class="crm-i fa-search"></i>
        </button>
      </div>
    </div>
  </div>

  <!-- Journey List -->
  <div class="journey-list">
    {if $journeys}
      <div class="journey-grid">
        {foreach from=$journeys item=journey}
          <div class="journey-card {$journey.status}">
            
            <!-- Journey Header -->
            <div class="journey-card-header">
              <div class="journey-title">
                <h3>
                  <a href="{crmURL p='civicrm/journey/builder' q="reset=1&id=`$journey.id`"}">
                    {$journey.name}
                  </a>
                </h3>
                <span class="journey-status status-{$journey.status}">
                  {$journey.status|capitalize}
                </span>
              </div>
              <div class="journey-menu">
                <div class="dropdown">
                  <button class="dropdown-toggle" data-toggle="dropdown">
                    <i class="crm-i fa-ellipsis-v"></i>
                  </button>
                  <ul class="dropdown-menu">
                    <li>
                      <a href="{crmURL p='civicrm/journey/builder' q="reset=1&id=`$journey.id`"}">
                        <i class="crm-i fa-edit"></i> Edit
                      </a>
                    </li>
                    <li>
                      <a href="{crmURL p='civicrm/journey/analytics' q="reset=1&id=`$journey.id`"}">
                        <i class="crm-i fa-bar-chart"></i> Analytics
                      </a>
                    </li>
                    <li class="divider"></li>
                    {if $journey.status == 'draft'}
                      <li>
                        <a href="{crmURL p='civicrm/journey/list' q="action=activate&id=`$journey.id`"}" 
                           onclick="return confirm('Activate this journey?')">
                          <i class="crm-i fa-play"></i> Activate
                        </a>
                      </li>
                    {elseif $journey.status == 'active'}
                      <li>
                        <a href="{crmURL p='civicrm/journey/list' q="action=pause&id=`$journey.id`"}" 
                           onclick="return confirm('Pause this journey?')">
                          <i class="crm-i fa-pause"></i> Pause
                        </a>
                      </li>
                    {/if}
                    <li>
                      <a href="{crmURL p='civicrm/journey/list' q="action=duplicate&id=`$journey.id`"}" 
                         onclick="return confirm('Duplicate this journey?')">
                        <i class="crm-i fa-copy"></i> Duplicate
                      </a>
                    </li>
                    <li>
                      <a href="{crmURL p='civicrm/journey/list' q="action=archive&id=`$journey.id`"}" 
                         onclick="return confirm('Archive this journey?')">
                        <i class="crm-i fa-archive"></i> Archive
                      </a>
                    </li>
                  </ul>
                </div>
              </div>
            </div>

            <!-- Journey Content -->
            <div class="journey-card-content">
              {if $journey.description}
                <p class="journey-description">{$journey.description|truncate:120}</p>
              {else}
                <p class="journey-description text-muted">No description provided</p>
              {/if}

              <div class="journey-stats">
                <div class="stat-item">
                  <span class="stat-value">{$journey.step_count}</span>
                  <span class="stat-label">Steps</span>
                </div>
                <div class="stat-item">
                  <span class="stat-value">{$journey.participant_count}</span>
                  <span class="stat-label">Participants</span>
                </div>
              </div>
            </div>

            <!-- Journey Footer -->
            <div class="journey-card-footer">
              <div class="journey-dates">
                <div class="date-item">
                  <small class="text-muted">Created:</small>
                  <small>{$journey.created_date|crmDate}</small>
                </div>
                {if $journey.activated_date}
                  <div class="date-item">
                    <small class="text-muted">Activated:</small>
                    <small>{$journey.activated_date|crmDate}</small>
                  </div>
                {/if}
              </div>
              {if $journey.creator_name}
                <div class="journey-creator">
                  <small class="text-muted">by {$journey.creator_name}</small>
                </div>
              {/if}
            </div>

          </div>
        {/foreach}
      </div>
    {else}
      <div class="empty-state">
        <div class="empty-icon">
          <i class="crm-i fa-map-o fa-4x"></i>
        </div>
        <h3>No Journeys Found</h3>
        <p>
          {if $currentStatus || $currentSearch}
            No journeys match your current filters. Try adjusting your search criteria.
          {else}
            Get started by creating your first journey.
          {/if}
        </p>
        {if !$currentStatus && !$currentSearch}
          <a href="{crmURL p='civicrm/journey/builder' q='reset=1'}" class="btn btn-primary">
            <i class="crm-i fa-plus"></i> Create Your First Journey
          </a>
        {/if}
      </div>
    {/if}
  </div>

</div>

<script type="text/javascript">
{literal}
function searchJourneys() {
  var search = document.getElementById('journey-search').value;
  var status = document.getElementById('status-filter').value;
  
  var params = [];
  if (search) params.push('search=' + encodeURIComponent(search));
  if (status) params.push('status=' + encodeURIComponent(status));
  
  var url = {/literal}'{crmURL p="civicrm/journey/list" q="reset=1"}'{literal};
  if (params.length > 0) {
    url += '&' + params.join('&');
  }
  
  window.location.href = url;
}

document.addEventListener('DOMContentLoaded', function() {
  // Handle status filter changes
  document.getElementById('status-filter').addEventListener('change', function() {
    searchJourneys();
  });
  
  // Handle enter key in search
  document.getElementById('journey-search').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
      searchJourneys();
    }
  });
});
{/literal}
</script>