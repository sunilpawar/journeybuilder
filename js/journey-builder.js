/**
 * Journey Builder JavaScript Module
 * Handles the visual journey builder interface
 */

(function($) {
  'use strict';

  window.JourneyBuilder = {
    // Core properties
    canvas: null,
    svg: null,
    nodes: [],
    connections: [],
    selectedNode: null,
    journeyId: null,

    // Canvas state
    scale: 1,
    panX: 0,
    panY: 0,
    isDragging: false,
    isConnecting: false,
    connectionStart: null,

    // Initialize the journey builder
    init: function() {
      this.setupCanvas();
      this.setupEventListeners();
      this.loadExistingJourney();
      this.setupTemplateSelection();
    },

    setupCanvas: function() {
      this.canvas = document.getElementById('journey-canvas');
      this.svg = document.getElementById('journey-svg');
      this.nodesContainer = document.getElementById('journey-nodes');

      // Set initial canvas size
      this.resizeCanvas();

      // Setup canvas interactions
      this.setupCanvasInteractions();
    },

    setupCanvasInteractions: function() {
      var self = this;

      // Canvas click (deselect nodes)
      this.canvas.addEventListener('click', function(e) {
        if (e.target === self.canvas) {
          self.deselectAllNodes();
        }
      });

      // Canvas drag for panning
      var isPanning = false;
      var startPanX, startPanY, startMouseX, startMouseY;

      this.canvas.addEventListener('mousedown', function(e) {
        if (e.target === self.canvas && !self.isConnecting) {
          isPanning = true;
          startPanX = self.panX;
          startPanY = self.panY;
          startMouseX = e.clientX;
          startMouseY = e.clientY;
          self.canvas.style.cursor = 'grabbing';
        }
      });

      document.addEventListener('mousemove', function(e) {
        if (isPanning) {
          var deltaX = e.clientX - startMouseX;
          var deltaY = e.clientY - startMouseY;
          self.panCanvas(startPanX + deltaX, startPanY + deltaY);
        }
      });

      document.addEventListener('mouseup', function() {
        if (isPanning) {
          isPanning = false;
          self.canvas.style.cursor = 'default';
        }
      });

      // Zoom with mouse wheel
      this.canvas.addEventListener('wheel', function(e) {
        e.preventDefault();
        var delta = e.deltaY > 0 ? 0.9 : 1.1;
        self.zoomCanvas(delta, e.clientX, e.clientY);
      });
    },

    setupEventListeners: function() {
      var self = this;

      // Element palette drag events
      $('.element-item').each(function() {
        this.draggable = true;

        $(this).on('dragstart', function(e) {
          e.originalEvent.dataTransfer.setData('text/plain', $(this).data('type'));
          $(this).addClass('dragging');
        });

        $(this).on('dragend', function() {
          $(this).removeClass('dragging');
        });
      });

      // Canvas drop events
      this.canvas.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'copy';
      });

      this.canvas.addEventListener('drop', function(e) {
        e.preventDefault();
        var elementType = e.dataTransfer.getData('text/plain');
        var rect = self.canvas.getBoundingClientRect();
        var x = (e.clientX - rect.left - self.panX) / self.scale;
        var y = (e.clientY - rect.top - self.panY) / self.scale;
        self.addNode(elementType, x, y);
      });

      // Toolbar button events
      $('#btn-save').click(function() { self.saveJourney(); });
      $('#btn-preview').click(function() { self.previewJourney(); });
      $('#btn-test').click(function() { self.testJourney(); });
      $('#btn-activate').click(function() { self.activateJourney(); });
      $('#btn-pause').click(function() { self.pauseJourney(); });

      // View controls
      $('#btn-zoom-in').click(function() { self.zoomCanvas(1.2); });
      $('#btn-zoom-out').click(function() { self.zoomCanvas(0.8); });
      $('#btn-fit-screen').click(function() { self.fitToScreen(); });

      // Journey name and description
      $('#journey-name').on('input', function() {
        self.updateJourneyInfo();
      });

      $('#journey-description').on('input', function() {
        self.updateJourneyInfo();
      });
    },

    setupTemplateSelection: function() {
      var self = this;

      // Show template modal for new journeys
      if (!this.journeyId) {
        this.showTemplateModal();
      }

      // Template selection
      $('.template-item').click(function() {
        var templateId = $(this).data('template');
        self.loadTemplate(templateId);
        self.hideTemplateModal();
      });

      // Email template selection
      $(document).on('click', '.select-template', function() {
        var templateId = $(this).closest('.email-template-item').data('template-id');
        var templateType = $(this).closest('.email-template-item').data('template-type');
        self.assignEmailTemplate(templateId, templateType);
        self.hideEmailTemplateModal();
      });
    },

    addNode: function(type, x, y) {
      var nodeId = 'node-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
      var node = {
        id: nodeId,
        type: type,
        x: x,
        y: y,
        name: this.getDefaultNodeName(type),
        configuration: this.getDefaultConfiguration(type),
        connections: {
          inputs: [],
          outputs: []
        }
      };

      this.nodes.push(node);
      this.renderNode(node);
      this.selectNode(node);

      return node;
    },

    renderNode: function(node) {
      var nodeElement = $('<div>', {
        class: 'journey-node ' + node.type.replace('*', ''),
        id: node.id,
        css: {
          left: node.x + 'px',
          top: node.y + 'px'
        }
      });

      var icon = this.getNodeIcon(node.type);
      var name = node.name || this.getDefaultNodeName(node.type);
      var statusText = this.getNodeStatus(node);

      var nodeHTML = `
        <div class="node-header">
          <i class="crm-i ${icon}"></i>
          <span class="node-title">${name}</span>
          <div class="node-menu">
            <i class="crm-i fa-ellipsis-v"></i>
          </div>
        </div>
        <div class="node-status">${statusText}</div>
        <div class="node-connections">
          <div class="connection-point input" data-type="input" data-node-id="${node.id}"></div>
          <div class="connection-point output" data-type="output" data-node-id="${node.id}"></div>
        </div>
      `;

      nodeElement.html(nodeHTML);

      var self = this;

      // Node selection
      nodeElement.on('click', function(e) {
        e.stopPropagation();
        self.selectNode(node);
      });

      // Node dragging
      this.makeDraggable(nodeElement, node);

      // Connection points
      nodeElement.find('.connection-point').on('mousedown', function(e) {
        e.stopPropagation();
        self.startConnection($(this), node);
      });

      // Node menu
      nodeElement.find('.node-menu').on('click', function(e) {
        e.stopPropagation();
        self.showNodeMenu(node, e.pageX, e.pageY);
      });

      $('#journey-nodes').append(nodeElement);
    },

    makeDraggable: function(element, node) {
      var self = this;
      var isDragging = false;
      var startX, startY, startNodeX, startNodeY;

      element.on('mousedown', '.node-header', function(e) {
        if ($(e.target).hasClass('node-menu') || $(e.target).closest('.node-menu').length) {
          return;
        }

        isDragging = true;
        startX = e.clientX;
        startY = e.clientY;
        startNodeX = node.x;
        startNodeY = node.y;

        element.addClass('dragging');

        $(document).on('mousemove.nodeDrag', function(e) {
          if (!isDragging) return;

          var deltaX = (e.clientX - startX) / self.scale;
          var deltaY = (e.clientY - startY) / self.scale;

          node.x = startNodeX + deltaX;
          node.y = startNodeY + deltaY;

          element.css({
            left: node.x + 'px',
            top: node.y + 'px'
          });

          self.updateConnections();
        });

        $(document).on('mouseup.nodeDrag', function() {
          isDragging = false;
          element.removeClass('dragging');
          $(document).off('.nodeDrag');
        });
      });
    },

    startConnection: function(connectionPoint, node) {
      var self = this;
      this.isConnecting = true;
      this.connectionStart = {
        node: node,
        type: connectionPoint.data('type'),
        element: connectionPoint
      };

      // Create temporary connection line
      var tempLine = document.createElementNS('http://www.w3.org/2000/svg', 'line');
      tempLine.id = 'temp-connection';
      tempLine.setAttribute('stroke', '#007bff');
      tempLine.setAttribute('stroke-width', '2');
      tempLine.setAttribute('stroke-dasharray', '5,5');
      this.svg.appendChild(tempLine);

      // Update temp line on mouse move
      $(document).on('mousemove.connection', function(e) {
        var rect = self.canvas.getBoundingClientRect();
        var x = (e.clientX - rect.left - self.panX) / self.scale;
        var y = (e.clientY - rect.top - self.panY) / self.scale;

        var startPos = self.getConnectionPointPosition(self.connectionStart.node, self.connectionStart.type);
        tempLine.setAttribute('x1', startPos.x);
        tempLine.setAttribute('y1', startPos.y);
        tempLine.setAttribute('x2', x);
        tempLine.setAttribute('y2', y);
      });

      // Handle connection completion
      $(document).on('mouseup.connection', function(e) {
        var target = $(e.target);
        if (target.hasClass('connection-point') && target.data('node-id') !== node.id) {
          var targetNodeId = target.data('node-id');
          var targetType = target.data('type');
          var targetNode = self.getNodeById(targetNodeId);

          if (targetNode && self.canConnect(self.connectionStart, {node: targetNode, type: targetType})) {
            self.createConnection(self.connectionStart.node, targetNode, self.connectionStart.type, targetType);
          }
        }

        // Clean up
        self.isConnecting = false;
        self.connectionStart = null;
        $('#temp-connection').remove();
        $(document).off('.connection');
      });
    },

    canConnect: function(start, end) {
      // Can't connect to same node
      if (start.node.id === end.node.id) return false;

      // Output can only connect to input
      if (start.type === 'output' && end.type === 'input') return true;
      if (start.type === 'input' && end.type === 'output') return true;

      return false;
    },

    createConnection: function(fromNode, toNode, fromType, toType) {
      var connection = {
        id: 'conn-' + Date.now(),
        from: fromNode.id,
        to: toNode.id,
        fromType: fromType,
        toType: toType
      };

      this.connections.push(connection);
      this.renderConnection(connection);

      // Update node connections
      if (fromType === 'output') {
        fromNode.connections.outputs.push(toNode.id);
        toNode.connections.inputs.push(fromNode.id);
      } else {
        fromNode.connections.inputs.push(toNode.id);
        toNode.connections.outputs.push(fromNode.id);
      }
    },

    renderConnection: function(connection) {
      var fromNode = this.getNodeById(connection.from);
      var toNode = this.getNodeById(connection.to);

      if (!fromNode || !toNode) return;

      var fromPos = this.getConnectionPointPosition(fromNode, connection.fromType);
      var toPos = this.getConnectionPointPosition(toNode, connection.toType);

      // Create curved path
      var path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
      path.id = connection.id;
      path.setAttribute('stroke', '#6c757d');
      path.setAttribute('stroke-width', '2');
      path.setAttribute('fill', 'none');
      path.setAttribute('marker-end', 'url(#arrowhead)');

      var pathData = this.createCurvedPath(fromPos, toPos);
      path.setAttribute('d', pathData);

      // Add click handler for connection selection
      var self = this;
      path.addEventListener('click', function(e) {
        e.stopPropagation();
        self.selectConnection(connection);
      });

      this.svg.appendChild(path);
    },

    createCurvedPath: function(from, to) {
      var dx = to.x - from.x;
      var dy = to.y - from.y;
      var curve = Math.abs(dx) * 0.5;

      return `M ${from.x} ${from.y} C ${from.x} ${from.y + curve}, ${to.x} ${to.y - curve}, ${to.x} ${to.y}`;
    },

    getConnectionPointPosition: function(node, type) {
      var nodeElement = $('#' + node.id);
      var rect = nodeElement[0].getBoundingClientRect();
      var canvasRect = this.canvas.getBoundingClientRect();

      var x = (rect.left + rect.width / 2 - canvasRect.left - this.panX) / this.scale;
      var y = type === 'input' ?
        (rect.top - canvasRect.top - this.panY) / this.scale :
        (rect.bottom - canvasRect.top - this.panY) / this.scale;

      return { x: x, y: y };
    },

    updateConnections: function() {
      var self = this;
      this.connections.forEach(function(connection) {
        var pathElement = document.getElementById(connection.id);
        if (pathElement) {
          var fromNode = self.getNodeById(connection.from);
          var toNode = self.getNodeById(connection.to);

          if (fromNode && toNode) {
            var fromPos = self.getConnectionPointPosition(fromNode, connection.fromType);
            var toPos = self.getConnectionPointPosition(toNode, connection.toType);
            var pathData = self.createCurvedPath(fromPos, toPos);
            pathElement.setAttribute('d', pathData);
          }
        }
      });
    },

    selectNode: function(node) {
      this.deselectAllNodes();
      $('#' + node.id).addClass('selected');
      this.selectedNode = node;
      this.showNodeProperties(node);
    },

    deselectAllNodes: function() {
      $('.journey-node').removeClass('selected');
      this.selectedNode = null;
      this.showDefaultProperties();
    },

    showNodeProperties: function(node) {
      var propertiesContent = $('#properties-content');
      var html = this.generatePropertiesHTML(node);
      propertiesContent.html(html);
      this.bindPropertyEvents(node);
    },

    showDefaultProperties: function() {
      $('#properties-content').html('<p class="text-muted">Select an element to edit its properties</p>');
    },

    generatePropertiesHTML: function(node) {
      var html = `
        <div class="node-properties">
          <h5>${node.name}</h5>
          <div class="form-group">
            <label>Element Name</label>
            <input type="text" class="form-control" id="prop-name" value="${node.name}">
          </div>
      `;

      // Add type-specific properties
      html += this.getTypeSpecificProperties(node);

      html += `
          <div class="form-group">
            <button class="btn btn-sm btn-danger" onclick="JourneyBuilder.deleteNode('${node.id}')">
              <i class="crm-i fa-trash"></i> Delete Element
            </button>
          </div>
        </div>
      `;

      return html;
    },

    getTypeSpecificProperties: function(node) {
      switch(node.type) {
        case 'action-email':
          return this.getEmailActionProperties(node);
        case 'condition':
          return this.getConditionProperties(node);
        case 'wait':
          return this.getWaitProperties(node);
        case 'entry-form':
          return this.getFormEntryProperties(node);
        default:
          return '<p class="text-muted">No additional properties for this element type.</p>';
      }
    },

    getEmailActionProperties: function(node) {
      var config = node.configuration || {};
      return `
        <div class="form-group">
          <label>Email Template</label>
          <div class="input-group">
            <input type="text" class="form-control" id="prop-template-name"
                   value="${config.template_name || 'No template selected'}" readonly>
            <div class="input-group-append">
              <button class="btn btn-outline-secondary" onclick="JourneyBuilder.selectEmailTemplate('${node.id}')">
                Select
              </button>
            </div>
          </div>
        </div>
        <div class="form-group">
          <label>Subject Line</label>
          <input type="text" class="form-control" id="prop-subject"
                 value="${config.subject || ''}" placeholder="Enter email subject">
        </div>
        <div class="form-group">
          <label>Send Delay</label>
          <select class="form-control" id="prop-delay">
            <option value="immediate" ${config.delay === 'immediate' ? 'selected' : ''}>Send Immediately</option>
            <option value="1hour" ${config.delay === '1hour' ? 'selected' : ''}>1 Hour</option>
            <option value="1day" ${config.delay === '1day' ? 'selected' : ''}>1 Day</option>
            <option value="1week" ${config.delay === '1week' ? 'selected' : ''}>1 Week</option>
          </select>
        </div>
        <div class="form-group">
          <label>Personalization</label>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="prop-personalize"
                   ${config.personalize ? 'checked' : ''}>
            <label class="form-check-label" for="prop-personalize">
              Enable dynamic personalization
            </label>
          </div>
        </div>
      `;
    },

    getConditionProperties: function(node) {
      var config = node.configuration || {};
      return `
        <div class="form-group">
          <label>Condition Type</label>
          <select class="form-control" id="prop-condition-type">
            <option value="contact_field" ${config.condition_type === 'contact_field' ? 'selected' : ''}>Contact Field</option>
            <option value="activity" ${config.condition_type === 'activity' ? 'selected' : ''}>Activity</option>
            <option value="contribution" ${config.condition_type === 'contribution' ? 'selected' : ''}>Contribution</option>
            <option value="membership" ${config.condition_type === 'membership' ? 'selected' : ''}>Membership</option>
            <option value="custom" ${config.condition_type === 'custom' ? 'selected' : ''}>Custom Field</option>
          </select>
        </div>
        <div class="form-group">
          <label>Field</label>
          <select class="form-control" id="prop-field">
            <option value="email" ${config.field === 'email' ? 'selected' : ''}>Email</option>
            <option value="first_name" ${config.field === 'first_name' ? 'selected' : ''}>First Name</option>
            <option value="last_name" ${config.field === 'last_name' ? 'selected' : ''}>Last Name</option>
            <option value="contact_type" ${config.field === 'contact_type' ? 'selected' : ''}>Contact Type</option>
          </select>
        </div>
        <div class="form-group">
          <label>Operator</label>
          <select class="form-control" id="prop-operator">
            <option value="equals" ${config.operator === 'equals' ? 'selected' : ''}>Equals</option>
            <option value="not_equals" ${config.operator === 'not_equals' ? 'selected' : ''}>Not Equals</option>
            <option value="contains" ${config.operator === 'contains' ? 'selected' : ''}>Contains</option>
            <option value="not_contains" ${config.operator === 'not_contains' ? 'selected' : ''}>Not Contains</option>
            <option value="is_null" ${config.operator === 'is_null' ? 'selected' : ''}>Is Empty</option>
            <option value="is_not_null" ${config.operator === 'is_not_null' ? 'selected' : ''}>Is Not Empty</option>
          </select>
        </div>
        <div class="form-group">
          <label>Value</label>
          <input type="text" class="form-control" id="prop-value"
                 value="${config.value || ''}" placeholder="Enter comparison value">
        </div>
      `;
    },

    getWaitProperties: function(node) {
      var config = node.configuration || {};
      return `
        <div class="form-group">
          <label>Wait Type</label>
          <select class="form-control" id="prop-wait-type">
            <option value="duration" ${config.wait_type === 'duration' ? 'selected' : ''}>Duration</option>
            <option value="date" ${config.wait_type === 'date' ? 'selected' : ''}>Specific Date</option>
            <option value="condition" ${config.wait_type === 'condition' ? 'selected' : ''}>Until Condition</option>
          </select>
        </div>
        <div class="form-group duration-settings" ${config.wait_type !== 'duration' ? 'style="display:none"' : ''}>
          <label>Duration</label>
          <div class="row">
            <div class="col-6">
              <input type="number" class="form-control" id="prop-duration"
                     value="${config.duration || 1}" min="1">
            </div>
            <div class="col-6">
              <select class="form-control" id="prop-duration-unit">
                <option value="hours" ${config.duration_unit === 'hours' ? 'selected' : ''}>Hours</option>
                <option value="days" ${config.duration_unit === 'days' ? 'selected' : ''}>Days</option>
                <option value="weeks" ${config.duration_unit === 'weeks' ? 'selected' : ''}>Weeks</option>
                <option value="months" ${config.duration_unit === 'months' ? 'selected' : ''}>Months</option>
              </select>
            </div>
          </div>
        </div>
        <div class="form-group date-settings" ${config.wait_type !== 'date' ? 'style="display:none"' : ''}>
          <label>Wait Until Date</label>
          <input type="datetime-local" class="form-control" id="prop-wait-date"
                 value="${config.wait_date || ''}">
        </div>
      `;
    },

    getFormEntryProperties: function(node) {
      var config = node.configuration || {};
      return `
        <div class="form-group">
          <label>Form Selection</label>
          <select class="form-control" id="prop-form-id">
            <option value="">Select a form...</option>
            <!-- Form options would be populated via API -->
          </select>
        </div>
        <div class="form-group">
          <label>Entry Conditions</label>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="prop-new-contacts-only"
                   ${config.new_contacts_only ? 'checked' : ''}>
            <label class="form-check-label" for="prop-new-contacts-only">
              New contacts only
            </label>
          </div>
        </div>
      `;
    },

    bindPropertyEvents: function(node) {
      var self = this;

      // Bind all property input changes
      $('#properties-content input, #properties-content select, #properties-content textarea').on('change input', function() {
        var propertyId = this.id.replace('prop-', '');
        var value = $(this).is(':checkbox') ? this.checked : this.value;

        if (propertyId === 'name') {
          node.name = value;
          $('#' + node.id + ' .node-title').text(value);
        } else {
          if (!node.configuration) node.configuration = {};
          node.configuration[propertyId] = value;
        }

        // Update node status display
        self.updateNodeStatus(node);
      });

      // Special handling for wait type changes
      $('#prop-wait-type').on('change', function() {
        var waitType = this.value;
        $('.duration-settings, .date-settings').hide();
        if (waitType === 'duration') {
          $('.duration-settings').show();
        } else if (waitType === 'date') {
          $('.date-settings').show();
        }
      });
    },

    updateNodeStatus: function(node) {
      var status = this.getNodeStatus(node);
      $('#' + node.id + ' .node-status').html(status);
    },

    getNodeStatus: function(node) {
      switch(node.type) {
        case 'action-email':
          if (!node.configuration.template_id) {
            return '<span class="text-warning">⚠ No template selected</span>';
          }
          return '<span class="text-success">✓ Ready to send</span>';

        case 'condition':
          if (!node.configuration.field || !node.configuration.operator) {
            return '<span class="text-warning">⚠ Incomplete condition</span>';
          }
          return '<span class="text-success">✓ Condition configured</span>';

        case 'wait':
          var config = node.configuration || {};
          if (config.wait_type === 'duration') {
            return `<span class="text-info">⏱ Wait ${config.duration || 1} ${config.duration_unit || 'days'}</span>`;
          }
          return '<span class="text-info">⏱ Wait configured</span>';

        default:
          return '<span class="text-muted">Ready</span>';
      }
    },

    selectEmailTemplate: function(nodeId) {
      this.currentEmailNodeId = nodeId;
      this.showEmailTemplateModal();
    },

    assignEmailTemplate: function(templateId, templateType) {
      if (this.currentEmailNodeId) {
        var node = this.getNodeById(this.currentEmailNodeId);
        if (node) {
          node.configuration.template_id = templateId;
          node.configuration.template_type = templateType;
          node.configuration.template_name = this.getTemplateName(templateId, templateType);

          // Update properties panel if this node is selected
          if (this.selectedNode && this.selectedNode.id === node.id) {
            this.showNodeProperties(node);
          }

          this.updateNodeStatus(node);
        }
      }
    },

    getTemplateName: function(templateId, templateType) {
      // This would normally fetch from the template data
      return `Template ${templateId}`;
    },

    deleteNode: function(nodeId) {
      if (confirm('Are you sure you want to delete this element?')) {
        // Remove connections
        this.connections = this.connections.filter(function(conn) {
          if (conn.from === nodeId || conn.to === nodeId) {
            $('#' + conn.id).remove();
            return false;
          }
          return true;
        });

        // Remove node
        this.nodes = this.nodes.filter(function(node) {
          return node.id !== nodeId;
        });

        $('#' + nodeId).remove();
        this.showDefaultProperties();
      }
    },

    getNodeById: function(nodeId) {
      return this.nodes.find(function(node) {
        return node.id === nodeId;
      });
    },

    // Canvas manipulation methods
    panCanvas: function(x, y) {
      this.panX = x;
      this.panY = y;
      this.nodesContainer.style.transform = `translate(${x}px, ${y}px) scale(${this.scale})`;
      this.updateConnections();
    },

    zoomCanvas: function(factor, centerX, centerY) {
      var newScale = Math.max(0.1, Math.min(3, this.scale * factor));

      if (centerX !== undefined && centerY !== undefined) {
        var rect = this.canvas.getBoundingClientRect();
        var mouseX = centerX - rect.left;
        var mouseY = centerY - rect.top;

        this.panX = mouseX - (mouseX - this.panX) * (newScale / this.scale);
        this.panY = mouseY - (mouseY - this.panY) * (newScale / this.scale);
      }

      this.scale = newScale;
      this.nodesContainer.style.transform = `translate(${this.panX}px, ${this.panY}px) scale(${this.scale})`;
      this.updateConnections();
    },

    fitToScreen: function() {
      if (this.nodes.length === 0) return;

      var bounds = this.getNodesBounds();
      var canvasRect = this.canvas.getBoundingClientRect();

      var scaleX = (canvasRect.width - 100) / bounds.width;
      var scaleY = (canvasRect.height - 100) / bounds.height;
      var scale = Math.min(scaleX, scaleY, 1);

      this.scale = scale;
      this.panX = (canvasRect.width - bounds.width * scale) / 2 - bounds.left * scale;
      this.panY = (canvasRect.height - bounds.height * scale) / 2 - bounds.top * scale;

      this.nodesContainer.style.transform = `translate(${this.panX}px, ${this.panY}px) scale(${this.scale})`;
      this.updateConnections();
    },

    getNodesBounds: function() {
      if (this.nodes.length === 0) return { left: 0, top: 0, width: 0, height: 0 };

      var minX = Math.min.apply(Math, this.nodes.map(function(n) { return n.x; }));
      var maxX = Math.max.apply(Math, this.nodes.map(function(n) { return n.x + 180; }));
      var minY = Math.min.apply(Math, this.nodes.map(function(n) { return n.y; }));
      var maxY = Math.max.apply(Math, this.nodes.map(function(n) { return n.y + 120; }));

      return {
        left: minX,
        top: minY,
        width: maxX - minX,
        height: maxY - minY
      };
    },

    resizeCanvas: function() {
      var rect = this.canvas.getBoundingClientRect();
      this.svg.setAttribute('width', rect.width);
      this.svg.setAttribute('height', rect.height);
    },

    // Journey management methods
    saveJourney: function() {
      var journeyData = {
        name: $('#journey-name').val(),
        description: $('#journey-description').val(),
        steps: this.nodes.map(function(node) {
          return {
            id: node.database_id || null,
            type: node.type,
            name: node.name,
            configuration: node.configuration || {},
            position: { x: node.x, y: node.y }
          };
        }),
        connections: this.connections,
        configuration: {
          entry_criteria: this.getEntryCriteria(),
          settings: {
            scale: this.scale,
            panX: this.panX,
            panY: this.panY
          }
        }
      };

      if (this.journeyId) {
        journeyData.id = this.journeyId;
      }

      var self = this;
      CRM.api3('Journey', 'save', journeyData)
        .done(function(result) {
          if (result.values && result.values.journey_id) {
            self.journeyId = result.values.journey_id;
            CRM.alert('Journey saved successfully', 'Success', 'success');

            // Update URL for new journeys
            if (!window.location.search.includes('id=')) {
              var newUrl = window.location.pathname + '?id=' + self.journeyId;
              window.history.replaceState(null, null, newUrl);
            }
          }
        })
        .fail(function(error) {
          CRM.alert('Error saving journey: ' + (error.error_message || 'Unknown error'), 'Error', 'error');
        });
    },

    previewJourney: function() {
      if (!this.journeyId) {
        CRM.alert('Please save the journey before previewing', 'Warning', 'warning');
        return;
      }

      // Open preview in new window
      var previewUrl = CRM.url('civicrm/journey/preview', { id: this.journeyId });
      window.open(previewUrl, '_blank');
    },

    testJourney: function() {
      if (!this.journeyId) {
        CRM.alert('Please save the journey before testing', 'Warning', 'warning');
        return;
      }

      // Show test dialog
      this.showTestDialog();
    },

    showTestDialog: function() {
      var dialogHtml = `
        <div class="test-journey-dialog">
          <h4>Test Journey</h4>
          <div class="form-group">
            <label>Test with Contact</label>
            <input type="text" class="form-control" id="test-contact" placeholder="Search for contact...">
          </div>
          <div class="form-group">
            <label>Test Mode</label>
            <select class="form-control" id="test-mode">
              <option value="simulation">Simulation Only</option>
              <option value="live">Live Test (sends actual emails)</option>
            </select>
          </div>
          <div class="alert alert-info">
            <strong>Simulation Mode:</strong> Shows what would happen without sending emails.<br>
            <strong>Live Test:</strong> Actually executes journey steps including email sends.
          </div>
        </div>
      `;

      CRM.confirm({
        title: 'Test Journey',
        message: dialogHtml,
        options: {
          yes: 'Start Test',
          no: 'Cancel'
        }
      }).on('crmConfirm:yes', function() {
        var contactId = $('#test-contact').val();
        var testMode = $('#test-mode').val();

        if (!contactId) {
          CRM.alert('Please select a contact for testing', 'Warning', 'warning');
          return;
        }

        JourneyBuilder.executeTest(contactId, testMode);
      });
    },

    executeTest: function(contactId, testMode) {
      var self = this;
      CRM.api3('Journey', 'test', {
        id: this.journeyId,
        contact_id: contactId,
        mode: testMode
      }).done(function(result) {
        self.showTestResults(result.values);
      }).fail(function(error) {
        CRM.alert('Test failed: ' + error.error_message, 'Error', 'error');
      });
    },

    showTestResults: function(results) {
      var resultsHtml = '<div class="test-results">';
      resultsHtml += '<h4>Test Results</h4>';
      resultsHtml += '<div class="test-steps">';

      results.steps.forEach(function(step, index) {
        var statusClass = step.success ? 'success' : 'danger';
        var statusIcon = step.success ? '✓' : '✗';

        resultsHtml += `
          <div class="test-step alert alert-${statusClass}">
            <strong>${statusIcon} Step ${index + 1}: ${step.name}</strong><br>
            ${step.message}
            ${step.details ? '<br><small>' + step.details + '</small>' : ''}
          </div>
        `;
      });

      resultsHtml += '</div></div>';

      CRM.alert(resultsHtml, 'Test Results', 'info', { expires: 0 });
    },

    activateJourney: function() {
      if (!this.journeyId) {
        CRM.alert('Please save the journey before activating', 'Warning', 'warning');
        return;
      }

      var self = this;
      CRM.confirm({
        title: 'Activate Journey',
        message: 'Are you sure you want to activate this journey? Once activated, it will start processing contacts based on the entry criteria.'
      }).on('crmConfirm:yes', function() {
        CRM.api3('Journey', 'activate', { id: self.journeyId })
          .done(function(result) {
            if (result.values && result.values.error) {
              var errors = Array.isArray(result.values.error) ?
                result.values.error.join('<br>') : result.values.error;
              CRM.alert('Validation errors:<br>' + errors, 'Error', 'error');
            } else {
              CRM.alert('Journey activated successfully', 'Success', 'success');
              setTimeout(function() { location.reload(); }, 1000);
            }
          })
          .fail(function(error) {
            CRM.alert('Activation failed: ' + error.error_message, 'Error', 'error');
          });
      });
    },

    pauseJourney: function() {
      var self = this;
      CRM.confirm({
        title: 'Pause Journey',
        message: 'Are you sure you want to pause this journey? Contacts currently in the journey will be paused at their current step.'
      }).on('crmConfirm:yes', function() {
        CRM.api3('Journey', 'pause', { id: self.journeyId })
          .done(function(result) {
            CRM.alert('Journey paused successfully', 'Success', 'success');
            setTimeout(function() { location.reload(); }, 1000);
          })
          .fail(function(error) {
            CRM.alert('Pause failed: ' + error.error_message, 'Error', 'error');
          });
      });
    },

    getEntryCriteria: function() {
      // Extract entry criteria from entry nodes
      var entryCriteria = {};
      var entryNodes = this.nodes.filter(function(node) {
        return node.type.startsWith('entry-');
      });

      entryNodes.forEach(function(node) {
        entryCriteria[node.id] = {
          type: node.type,
          configuration: node.configuration || {}
        };
      });

      return entryCriteria;
    },

    loadTemplate: function(templateId) {
      if (templateId === 'blank') {
        // Start with blank canvas
        return;
      }

      // Load predefined template
      var template = this.getTemplateDefinition(templateId);
      if (template) {
        this.loadTemplateDefinition(template);
      }
    },

    getTemplateDefinition: function(templateId) {
      var templates = {
        'welcome_series': {
          name: 'Welcome Series',
          nodes: [
            { type: 'entry-form', x: 100, y: 100, name: 'Form Submission' },
            { type: 'wait', x: 100, y: 250, name: 'Wait 1 Day', configuration: { duration: 1, duration_unit: 'days' } },
            { type: 'action-email', x: 100, y: 400, name: 'Welcome Email' },
            { type: 'wait', x: 100, y: 550, name: 'Wait 3 Days', configuration: { duration: 3, duration_unit: 'days' } },
            { type: 'action-email', x: 100, y: 700, name: 'Follow-up Email' }
          ],
          connections: [
            { from: 0, to: 1 },
            { from: 1, to: 2 },
            { from: 2, to: 3 },
            { from: 3, to: 4 }
          ]
        },
        'donor_nurture': {
          name: 'Donor Nurture Campaign',
          nodes: [
            { type: 'entry-manual', x: 100, y: 100, name: 'New Donor' },
            { type: 'action-email', x: 100, y: 250, name: 'Thank You Email' },
            { type: 'wait', x: 100, y: 400, name: 'Wait 1 Week', configuration: { duration: 1, duration_unit: 'weeks' } },
            { type: 'condition', x: 100, y: 550, name: 'Check Engagement' },
            { type: 'action-email', x: 300, y: 700, name: 'Impact Story' },
            { type: 'action-email', x: -100, y: 700, name: 'Re-engagement Email' }
          ],
          connections: [
            { from: 0, to: 1 },
            { from: 1, to: 2 },
            { from: 2, to: 3 },
            { from: 3, to: 4, condition: 'engaged' },
            { from: 3, to: 5, condition: 'not_engaged' }
          ]
        }
      };

      return templates[templateId];
    },

    loadTemplateDefinition: function(template) {
      // Clear existing nodes
      this.nodes = [];
      this.connections = [];
      $('#journey-nodes').empty();
      $(this.svg).find('path').remove();

      // Set journey name
      $('#journey-name').val(template.name);

      var self = this;
      var nodeMap = {};

      // Create nodes
      template.nodes.forEach(function(nodeData, index) {
        var node = self.addNode(nodeData.type, nodeData.x, nodeData.y);
        node.name = nodeData.name;
        if (nodeData.configuration) {
          node.configuration = nodeData.configuration;
        }
        nodeMap[index] = node;

        // Update node display
        $('#' + node.id + ' .node-title').text(node.name);
        self.updateNodeStatus(node);
      });

      // Create connections
      if (template.connections) {
        template.connections.forEach(function(connData) {
          var fromNode = nodeMap[connData.from];
          var toNode = nodeMap[connData.to];

          if (fromNode && toNode) {
            self.createConnection(fromNode, toNode, 'output', 'input');
          }
        });
      }

      // Fit to screen
      setTimeout(function() {
        self.fitToScreen();
      }, 100);
    },

    loadExistingJourney: function() {
      // This would be populated by the template if editing existing journey
      // Implementation handled in template
    },

    updateJourneyInfo: function() {
      // Debounce the update to avoid too frequent saves
      clearTimeout(this.updateTimeout);
      this.updateTimeout = setTimeout(function() {
        // Auto-save journey info
        if (JourneyBuilder.journeyId) {
          // Could implement auto-save here
        }
      }, 2000);
    },

    // Utility methods
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
            delay: 'immediate',
            personalize: false
          };
        case 'condition':
          return {
            condition_type: 'contact_field',
            field: 'email',
            operator: 'is_not_null',
            value: ''
          };
        case 'wait':
          return {
            wait_type: 'duration',
            duration: 1,
            duration_unit: 'days'
          };
        case 'entry-form':
          return {
            form_id: null,
            new_contacts_only: false
          };
        default:
          return {};
      }
    },

    // Custom modal functions to replace Bootstrap modals
    showTemplateModal: function() {
      var self = this;
      $('#template-modal').show().addClass('show');
      $('.modal-backdrop').remove();
      $('body').append('<div class="modal-backdrop fade show"></div>').addClass('modal-open');
      
      // Bind close events
      $('.modal-backdrop').one('click', function() {
        self.hideTemplateModal();
      });
      
      $(document).on('keydown.templateModal', function(e) {
        if (e.key === 'Escape') {
          self.hideTemplateModal();
        }
      });
    },

    hideTemplateModal: function() {
      $('#template-modal').hide().removeClass('show');
      $('.modal-backdrop').remove();
      $('body').removeClass('modal-open');
      $(document).off('keydown.templateModal');
    },

    showEmailTemplateModal: function() {
      var self = this;
      $('#email-template-modal').show().addClass('show');
      $('.modal-backdrop').remove();
      $('body').append('<div class="modal-backdrop fade show"></div>').addClass('modal-open');
      
      // Bind close events
      $('.modal-backdrop').one('click', function() {
        self.hideEmailTemplateModal();
      });
      
      $(document).on('keydown.emailTemplateModal', function(e) {
        if (e.key === 'Escape') {
          self.hideEmailTemplateModal();
        }
      });
    },

    hideEmailTemplateModal: function() {
      $('#email-template-modal').hide().removeClass('show');
      $('.modal-backdrop').remove();
      $('body').removeClass('modal-open');
      $(document).off('keydown.emailTemplateModal');
    }
  };

  // Initialize when document is ready
  $(document).ready(function() {
    JourneyBuilder.init();

    // Handle window resize
    $(window).on('resize', function() {
      JourneyBuilder.resizeCanvas();
      JourneyBuilder.updateConnections();
    });
  });

})(CRM.$);
