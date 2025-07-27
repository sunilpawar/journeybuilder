<div id="journey-builder-app" class="crm-container">

  <!-- Toolbar -->
  <div class="journey-toolbar">
    <div class="toolbar-left">
      <h2>Journey Builder</h2>
      {if $journeyId}
        <span class="journey-id">#{$journeyId}</span>
      {/if}
    </div>

    <div class="toolbar-center">
      <div class="journey-controls">
        <button id="btn-save" class="btn btn-primary">
          <i class="crm-i fa-save"></i> Save Journey
        </button>
        <button id="btn-preview" class="btn btn-secondary">
          <i class="crm-i fa-eye"></i> Preview
        </button>
        <button id="btn-test" class="btn btn-secondary">
          <i class="crm-i fa-play"></i> Test Journey
        </button>
        {if $journey.status == 'draft'}
          <button id="btn-activate" class="btn btn-success">
            <i class="crm-i fa-play-circle"></i> Activate
          </button>
        {else}
          <button id="btn-pause" class="btn btn-warning">
            <i class="crm-i fa-pause"></i> Pause
          </button>
        {/if}
      </div>
    </div>

    <div class="toolbar-right">
      <div class="view-controls">
        <button id="btn-zoom-in" class="btn btn-sm"><i class="crm-i fa-search-plus"></i></button>
        <button id="btn-zoom-out" class="btn btn-sm"><i class="crm-i fa-search-minus"></i></button>
        <button id="btn-fit-screen" class="btn btn-sm"><i class="crm-i fa-expand"></i></button>
      </div>
    </div>
  </div>

  <div class="journey-workspace">

    <!-- Element Palette -->
    <div class="element-palette">
      <h4>Journey Elements</h4>

      <div class="element-category">
        <h5>Triggers</h5>
        <div class="element-item" data-type="entry-form">
          <i class="crm-i fa-wpforms"></i>
          <span>Form Submission</span>
        </div>
        <div class="element-item" data-type="entry-event">
          <i class="crm-i fa-calendar"></i>
          <span>Event Registration</span>
        </div>
        <div class="element-item" data-type="entry-date">
          <i class="crm-i fa-clock-o"></i>
          <span>Date Trigger</span>
        </div>
        <div class="element-item" data-type="entry-manual">
          <i class="crm-i fa-hand-pointer-o"></i>
          <span>Manual Entry</span>
        </div>
      </div>

      <div class="element-category">
        <h5>Actions</h5>
        <div class="element-item" data-type="action-email">
          <i class="crm-i fa-envelope"></i>
          <span>Send Email</span>
        </div>
        <div class="element-item" data-type="action-sms">
          <i class="crm-i fa-mobile"></i>
          <span>Send SMS</span>
        </div>
        <div class="element-item" data-type="action-update">
          <i class="crm-i fa-edit"></i>
          <span>Update Contact</span>
        </div>
        <div class="element-item" data-type="action-tag">
          <i class="crm-i fa-tag"></i>
          <span>Add Tag</span>
        </div>
      </div>

      <div class="element-category">
        <h5>Logic</h5>
        <div class="element-item" data-type="condition">
          <i class="crm-i fa-code-fork"></i>
          <span>Condition</span>
        </div>
        <div class="element-item" data-type="wait">
          <i class="crm-i fa-hourglass-half"></i>
          <span>Wait</span>
        </div>
        <div class="element-item" data-type="split-test">
          <i class="crm-i fa-random"></i>
          <span>A/B Split</span>
        </div>
      </div>
    </div>

    <!-- Canvas Area -->
    <div class="journey-canvas-container">
      <div class="canvas-header">
        <input type="text" id="journey-name" placeholder="Journey Name"
               value="{$journey.name|default:'New Journey'}" class="form-control">
        <textarea id="journey-description" placeholder="Journey Description"
                  class="form-control">{$journey.description|default:''}</textarea>
      </div>

      <!-- Main Canvas -->
      <div id="journey-canvas" class="journey-canvas">
        <svg id="journey-svg" width="100%" height="100%">
          <defs>
            <marker id="arrowhead" markerWidth="10" markerHeight="7"
                    refX="9" refY="3.5" orient="auto">
              <polygon points="0 0, 10 3.5, 0 7" fill="#666" />
            </marker>
          </defs>
        </svg>
        <div id="journey-nodes"></div>
      </div>

      <!-- Minimap -->
      <div class="journey-minimap">
        <canvas id="minimap-canvas" width="200" height="150"></canvas>
      </div>
    </div>

    <!-- Properties Panel -->
    <div class="properties-panel">
      <div class="panel-header">
        <h4>Properties</h4>
      </div>
      <div id="properties-content" class="panel-content">
        <p class="text-muted">Select an element to edit its properties</p>
      </div>
    </div>
  </div>

</div>

<!-- Templates Selection Modal -->
<div id="template-modal" class="modal fade" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Choose Journey Template</h4>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <div class="template-grid">
          <div class="template-item" data-template="blank">
            <div class="template-preview">
              <i class="crm-i fa-file-o fa-3x"></i>
            </div>
            <h5>Blank Journey</h5>
            <p>Start with an empty canvas</p>
          </div>

          {foreach from=$templates item=template}
            <div class="template-item" data-template="{$template.id}">
              <div class="template-preview">
                <i class="crm-i fa-sitemap fa-3x"></i>
              </div>
              <h5>{$template.name}</h5>
              <p>{$template.description}</p>
            </div>
          {/foreach}
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Email Template Selection Modal -->
<div id="email-template-modal" class="modal fade" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Select Email Template</h4>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <div class="template-categories">
          <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item">
              <a class="nav-link active" data-toggle="tab" href="#mosaico-templates">Mosaico Templates</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" data-toggle="tab" href="#journey-templates">Journey Templates</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" data-toggle="tab" href="#custom-templates">Custom HTML</a>
            </li>
          </ul>
        </div>

        <div class="tab-content">
          <div id="mosaico-templates" class="tab-pane active">
            <div class="email-template-grid">
              {foreach from=$emailTemplates item=template}
                <div class="email-template-item" data-template-id="{$template.id}" data-template-type="mosaico">
                  <div class="template-thumbnail">
                    {if $template.thumbnail}
                      <img src="{$template.thumbnail}" alt="{$template.title}">
                    {else}
                      <div class="no-thumbnail">
                        <i class="crm-i fa-envelope-o fa-3x"></i>
                      </div>
                    {/if}
                  </div>
                  <h6>{$template.title}</h6>
                  <div class="template-actions">
                    <button class="btn btn-sm btn-primary select-template">Select</button>
                    <button class="btn btn-sm btn-secondary preview-template">Preview</button>
                  </div>
                </div>
              {/foreach}
            </div>
          </div>

          <div id="journey-templates" class="tab-pane">
            <div class="email-template-grid">
              <div class="email-template-item" data-template-type="journey" data-template="welcome">
                <div class="template-thumbnail">
                  <div class="template-preview-content">
                    <h4>Welcome!</h4>
                    <p>Thank you for joining us...</p>
                  </div>
                </div>
                <h6>Welcome Email</h6>
                <div class="template-actions">
                  <button class="btn btn-sm btn-primary select-template">Select</button>
                </div>
              </div>

              <div class="email-template-item" data-template-type="journey" data-template="thank-you">
                <div class="template-thumbnail">
                  <div class="template-preview-content">
                    <h4>Thank You!</h4>
                    <p>We appreciate your support...</p>
                  </div>
                </div>
                <h6>Thank You Email</h6>
                <div class="template-actions">
                  <button class="btn btn-sm btn-primary select-template">Select</button>
                </div>
              </div>
            </div>
          </div>

          <div id="custom-templates" class="tab-pane">
            <div class="custom-template-form">
              <div class="form-group">
                <label>Template Name</label>
                <input type="text" class="form-control" id="custom-template-name">
              </div>
              <div class="form-group">
                <label>HTML Content</label>
                <textarea class="form-control" id="custom-template-html" rows="10"></textarea>
              </div>
              <button class="btn btn-primary" id="create-custom-template">Create Template</button>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript">
  // Initialize Journey Builder
  {literal}
  var JourneyBuilder = {
    canvas: null,
    svg: null,
    nodes: [],
    connections: [],
    selectedNode: null,
    isDragging: false,
    dragOffset: { x: 0, y: 0 },

    init: function() {
      this.canvas = document.getElementById('journey-canvas');
      this.svg = document.getElementById('journey-svg');
      this.setupEventListeners();
      this.loadExistingJourney();
    },

    setupEventListeners: function() {
      var self = this;

      // Element palette drag events
      document.querySelectorAll('.element-item').forEach(function(item) {
        item.addEventListener('dragstart', function(e) {
          e.dataTransfer.setData('text/plain', this.dataset.type);
        });
        item.draggable = true;
      });

      // Canvas drop events
      this.canvas.addEventListener('dragover', function(e) {
        e.preventDefault();
      });

      this.canvas.addEventListener('drop', function(e) {
        e.preventDefault();
        var elementType = e.dataTransfer.getData('text/plain');
        var rect = self.canvas.getBoundingClientRect();
        var x = e.clientX - rect.left;
        var y = e.clientY - rect.top;
        self.addNode(elementType, x, y);
      });

      // Toolbar buttons
      document.getElementById('btn-save').addEventListener('click', function() {
        self.saveJourney();
      });

      document.getElementById('btn-preview').addEventListener('click', function() {
        self.previewJourney();
      });

      document.getElementById('btn-activate').addEventListener('click', function() {
        self.activateJourney();
      });

      // Template selection
      document.querySelectorAll('.template-item').forEach(function(item) {
        item.addEventListener('click', function() {
          var templateId = this.dataset.template;
          self.loadTemplate(templateId);
          $('#template-modal').modal('hide');
        });
      });
    },

    addNode: function(type, x, y) {
      var nodeId = 'node-' + Date.now();
      var node = {
        id: nodeId,
        type: type,
        x: x,
        y: y,
        name: this.getDefaultNodeName(type),
        configuration: this.getDefaultConfiguration(type)
      };

      this.nodes.push(node);
      this.renderNode(node);
      this.selectNode(node);
    },

    renderNode: function(node) {
      var nodeElement = document.createElement('div');
      nodeElement.className = 'journey-node ' + node.type;
      nodeElement.id = node.id;
      nodeElement.style.left = node.x + 'px';
      nodeElement.style.top = node.y + 'px';

      var icon = this.getNodeIcon(node.type);
      var name = node.name || this.getDefaultNodeName(node.type);

      nodeElement.innerHTML = `
      <div class="node-header">
        <i class="crm-i ${icon}"></i>
        <span class="node-title">${name}</span>
      </div>
      <div class="node-status"></div>
      <div class="node-connections">
        <div class="connection-point input" data-type="input"></div>
        <div class="connection-point output" data-type="output"></div>
      </div>
    `;

      var self = this;
      nodeElement.addEventListener('click', function(e) {
        e.stopPropagation();
        self.selectNode(node);
      });

      // Make node draggable
      this.makeDraggable(nodeElement, node);

      document.getElementById('journey-nodes').appendChild(nodeElement);
    },

    makeDraggable: function(element, node) {
      var self = this;
      var isDragging = false;
      var startX, startY, startNodeX, startNodeY;

      element.addEventListener('mousedown', function(e) {
        if (e.target.classList.contains('connection-point')) return;

        isDragging = true;
        startX = e.clientX;
        startY = e.clientY;
        startNodeX = node.x;
        startNodeY = node.y;

        document.addEventListener('mousemove', mouseMoveHandler);
        document.addEventListener('mouseup', mouseUpHandler);
      });

      function mouseMoveHandler(e) {
        if (!isDragging) return;

        var deltaX = e.clientX - startX;
        var deltaY = e.clientY - startY;

        node.x = startNodeX + deltaX;
        node.y = startNodeY + deltaY;

        element.style.left = node.x + 'px';
        element.style.top = node.y + 'px';

        self.updateConnections();
      }

      function mouseUpHandler() {
        isDragging = false;
        document.removeEventListener('mousemove', mouseMoveHandler);
        document.removeEventListener('mouseup', mouseUpHandler);
      }
    },

    selectNode: function(node) {
      // Remove previous selection
      document.querySelectorAll('.journey-node').forEach(function(n) {
        n.classList.remove('selected');
      });

      // Select current node
      document.getElementById(node.id).classList.add('selected');
      this.selectedNode = node;
      this.showNodeProperties(node);
    },

    showNodeProperties: function(node) {
      var propertiesContent = document.getElementById('properties-content');
      var html = this.generatePropertiesHTML(node);
      propertiesContent.innerHTML = html;

      // Bind property change events
      this.bindPropertyEvents(node);
    },

    generatePropertiesHTML: function(node) {
      var html = `<h5>${node.name}</h5>`;

      switch(node.type) {
        case 'action-email':
          html += `
          <div class="form-group">
            <label>Email Template</label>
            <button class="btn btn-secondary btn-block" onclick="JourneyBuilder.selectEmailTemplate('${node.id}')">
              Select Template
            </button>
          </div>
          <div class="form-group">
            <label>Subject Line</label>
            <input type="text" class="form-control" id="prop-subject" value="${node.configuration.subject || ''}">
          </div>
          <div class="form-group">
            <label>Send Delay</label>
            <select class="form-control" id="prop-delay">
              <option value="immediate">Send Immediately</option>
              <option value="1hour">1 Hour</option>
              <option value="1day">1 Day</option>
              <option value="1week">1 Week</option>
            </select>
          </div>
        `;
          break;

        case 'condition':
          html += `
          <div class="form-group">
            <label>Condition Type</label>
            <select class="form-control" id="prop-condition-type">
              <option value="contact_field">Contact Field</option>
              <option value="activity">Activity</option>
              <option value="contribution">Contribution</option>
              <option value="membership">Membership</option>
            </select>
          </div>
          <div class="form-group">
            <label>Field</label>
            <select class="form-control" id="prop-field">
              <option value="email">Email</option>
              <option value="first_name">First Name</option>
              <option value="last_name">Last Name</option>
            </select>
          </div>
          <div class="form-group">
            <label>Operator</label>
            <select class="form-control" id="prop-operator">
              <option value="equals">Equals</option>
              <option value="contains">Contains</option>
              <option value="not_empty">Not Empty</option>
            </select>
          </div>
          <div class="form-group">
            <label>Value</label>
            <input type="text" class="form-control" id="prop-value">
          </div>
        `;
          break;

        case 'wait':
          html += `
          <div class="form-group">
            <label>Wait Type</label>
            <select class="form-control" id="prop-wait-type">
              <option value="duration">Duration</option>
              <option value="date">Specific Date</option>
              <option value="condition">Until Condition</option>
            </select>
          </div>
          <div class="form-group">
            <label>Duration</label>
            <div class="row">
              <div class="col-6">
                <input type="number" class="form-control" id="prop-duration" value="1">
              </div>
              <div class="col-6">
                <select class="form-control" id="prop-duration-unit">
                  <option value="hours">Hours</option>
                  <option value="days">Days</option>
                  <option value="weeks">Weeks</option>
                </select>
              </div>
            </div>
          </div>
        `;
          break;
      }

      return html;
    },

    bindPropertyEvents: function(node) {
      var self = this;
      var inputs = document.querySelectorAll('#properties-content input, #properties-content select');

      inputs.forEach(function(input) {
        input.addEventListener('change', function() {
          self.updateNodeConfiguration(node, this.id, this.value);
        });
      });
    },

    updateNodeConfiguration: function(node, propertyId, value) {
      var property = propertyId.replace('prop-', '');
      node.configuration[property] = value;
    },

    selectEmailTemplate: function(nodeId) {
      this.currentEmailNodeId = nodeId;
      $('#email-template-modal').modal('show');
    },

    getNodeIcon: function(type) {
      var icons = {
        'entry-form': 'fa-wpforms',
        'entry-event': 'fa-calendar',
        'entry-date': 'fa-clock-o',
        'entry-manual': 'fa-hand-pointer-o',
        'action-email': 'fa-envelope',
        'action-sms': 'fa-mobile',
        'action-update': 'fa-edit',
        'action-tag': 'fa-tag',
        'condition': 'fa-code-fork',
        'wait': 'fa-hourglass-half',
        'split-test': 'fa-random'
      };
      return icons[type] || 'fa-circle';
    },

    getDefaultNodeName: function(type) {
      var names = {
        'entry-form': 'Form Submission',
        'entry-event': 'Event Registration',
        'entry-date': 'Date Trigger',
        'entry-manual': 'Manual Entry',
        'action-email': 'Send Email',
        'action-sms': 'Send SMS',
        'action-update': 'Update Contact',
        'action-tag': 'Add Tag',
        'condition': 'Condition',
        'wait': 'Wait',
        'split-test': 'A/B Test'
      };
      return names[type] || 'Unknown';
    },

    getDefaultConfiguration: function(type) {
      switch(type) {
        case 'action-email':
          return {
            template_id: null,
            subject: '',
            delay: 'immediate'
          };
        case 'condition':
          return {
            condition_type: 'contact_field',
            field: 'email',
            operator: 'not_empty',
            value: ''
          };
        case 'wait':
          return {
            wait_type: 'duration',
            duration: 1,
            duration_unit: 'days'
          };
        default:
          return {};
      }
    },

    saveJourney: function() {
      var journeyData = {
        name: document.getElementById('journey-name').value,
        description: document.getElementById('journey-description').value,
        steps: this.nodes,
        connections: this.connections,
        configuration: {
          entry_criteria: {},
          settings: {}
        }
      };

      var self = this;
      CRM.api3('Journey', 'save', journeyData)
        .done(function(result) {
          CRM.alert('Journey saved successfully', 'Success', 'success');
          if (!self.journeyId && result.journey_id) {
            // Update URL to include journey ID for new journeys
            window.history.replaceState(null, null,
              window.location.pathname + '?id=' + result.journey_id);
          }
        })
        .fail(function(error) {
          CRM.alert('Error saving journey: ' + error.error_message, 'Error', 'error');
        });
    },

    activateJourney: function() {
      if (!this.journeyId) {
        CRM.alert('Please save the journey before activating', 'Warning', 'warning');
        return;
      }

      CRM.api3('Journey', 'activate', { id: this.journeyId })
        .done(function(result) {
          if (result.error) {
            CRM.alert('Validation errors: ' + result.error.join(', '), 'Error', 'error');
          } else {
            CRM.alert('Journey activated successfully', 'Success', 'success');
            location.reload();
          }
        });
    },

    loadExistingJourney: function() {
      // Load journey data if editing existing journey
      {/literal}
      {if $journey}
      var journeyData = {$journey|@json_encode};
      this.journeyId = journeyData.id;

      // Load nodes
      if (journeyData.steps) {
        journeyData.steps.forEach(function(step) {
          JourneyBuilder.nodes.push({
            id: 'node-' + step.id,
            type: step.step_type,
            x: step.position_x || 100,
            y: step.position_y || 100,
            name: step.name,
            configuration: step.configuration || {}
          });
        });

        // Render nodes
        this.nodes.forEach(function(node) {
          JourneyBuilder.renderNode(node);
        });
      }
      {/if}
      {literal}
    }
  };

  // Initialize when document is ready
  document.addEventListener('DOMContentLoaded', function() {
    JourneyBuilder.init();
  });
  {/literal}
</script>

