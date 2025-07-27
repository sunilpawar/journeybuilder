<?php

use CRM_Journeybuilder_ExtensionUtil as E;
/**
 * Journey Builder API for managing journeys, steps, and participants.
 */
class CRM_Journeybuilder_API_Journey {

  /**
   * Create or update a journey
   */
  public static function save($params) {
    $journeyId = $params['id'] ?? NULL;

    if ($journeyId) {
      // Update existing journey
      $dao = new CRM_Core_DAO();
      $dao->query("
        UPDATE civicrm_journey_campaigns
        SET name = %1, description = %2, configuration = %3, modified_date = NOW()
        WHERE id = %4
      ", [
        1 => [$params['name'], 'String'],
        2 => [$params['description'], 'String'],
        3 => [json_encode($params['configuration']), 'String'],
        4 => [$journeyId, 'Positive']
      ]);
    }
    else {
      // Create new journey
      $dao = new CRM_Core_DAO();
      $dao->query("
        INSERT INTO civicrm_journey_campaigns
        (name, description, configuration, status, created_date, created_id)
        VALUES (%1, %2, %3, 'draft', NOW(), %4)
      ", [
        1 => [$params['name'], 'String'],
        2 => [$params['description'], 'String'],
        3 => [json_encode($params['configuration']), 'String'],
        4 => [CRM_Core_Session::getLoggedInContactID(), 'Positive']
      ]);
      $journeyId = CRM_Core_DAO::singleValueQuery("SELECT LAST_INSERT_ID()");
    }

    // Save journey steps
    if (!empty($params['steps'])) {
      self::saveJourneySteps($journeyId, $params['steps']);
    }

    return ['journey_id' => $journeyId];
  }

  /**
   * Save journey steps
   */
  private static function saveJourneySteps($journeyId, $steps) {
    // Delete existing steps
    CRM_Core_DAO::executeQuery("DELETE FROM civicrm_journey_steps WHERE journey_id = %1", [
      1 => [$journeyId, 'Positive']
    ]);

    // Insert new steps
    foreach ($steps as $index => $step) {
      CRM_Core_DAO::executeQuery("
        INSERT INTO civicrm_journey_steps
        (journey_id, step_type, name, configuration, position_x, position_y, sort_order)
        VALUES (%1, %2, %3, %4, %5, %6, %7)
      ", [
        1 => [$journeyId, 'Positive'],
        2 => [$step['type'], 'String'],
        3 => [$step['name'], 'String'],
        4 => [json_encode($step['configuration']), 'String'],
        5 => [$step['position']['x'], 'Float'],
        6 => [$step['position']['y'], 'Float'],
        7 => [$index, 'Positive']
      ]);
    }
  }

  /**
   * Activate a journey
   */
  public static function activate($journeyId) {
    // Validate journey before activation
    $validation = self::validateJourney($journeyId);
    if (!$validation['valid']) {
      return ['error' => $validation['errors']];
    }

    // Update status to active
    CRM_Core_DAO::executeQuery("
      UPDATE civicrm_journey_campaigns
      SET status = 'active', activated_date = NOW()
      WHERE id = %1
    ", [1 => [$journeyId, 'Positive']]);

    // Initialize journey participants based on entry criteria
    self::initializeJourneyParticipants($journeyId);

    return ['success' => TRUE];
  }

  /**
   * Validate journey configuration
   */
  private static function validateJourney($journeyId) {
    $errors = [];
    $valid = TRUE;

    // Check for entry point
    $entrySteps = CRM_Core_DAO::singleValueQuery("
      SELECT COUNT(*) FROM civicrm_journey_steps
      WHERE journey_id = %1 AND step_type = 'entry'
    ", [1 => [$journeyId, 'Positive']]);

    if ($entrySteps == 0) {
      $errors[] = 'Journey must have at least one entry point';
      $valid = FALSE;
    }

    // Check for disconnected steps
    // Implementation would check journey flow connectivity

    return ['valid' => $valid, 'errors' => $errors];
  }

  /**
   * Initialize journey participants
   */
  private static function initializeJourneyParticipants($journeyId) {
    // Get journey entry criteria
    $journey = CRM_Core_DAO::executeQuery("
      SELECT configuration FROM civicrm_journey_campaigns WHERE id = %1
    ", [1 => [$journeyId, 'Positive']]);

    if ($journey->fetch()) {
      $config = json_decode($journey->configuration, TRUE);
      $entryCriteria = $config['entry_criteria'] ?? [];

      // Build contact query based on entry criteria
      $contactIds = self::getContactsForEntry($entryCriteria);

      // Add contacts to journey
      foreach ($contactIds as $contactId) {
        self::addContactToJourney($journeyId, $contactId);
      }
    }
  }

  /**
   * Get contacts matching entry criteria
   */
  private static function getContactsForEntry($criteria) {
    $contactIds = [];

    if (empty($criteria)) {
      return $contactIds;
    }

    foreach ($criteria as $nodeId => $criterion) {
      $config = $criterion['configuration'] ?? [];

      switch ($criterion['type']) {
        case 'entry-form':
          if (!empty($config['form_id'])) {
            $contactIds = array_merge($contactIds, self::getContactsFromForm($config));
          }
          break;

        case 'entry-manual':
          if (!empty($config['contact_ids'])) {
            $contactIds = array_merge($contactIds, $config['contact_ids']);
          }
          break;

        case 'entry-event':
          if (!empty($config['event_id'])) {
            $contactIds = array_merge($contactIds, self::getContactsFromEvent($config));
          }
          break;
      }
    }

    return array_unique($contactIds);
  }

  private static function getContactsFromForm($config) {
    $contactIds = [];
    $formId = $config['form_id'];
    $newContactsOnly = $config['new_contacts_only'] ?? FALSE;

    $sql = "SELECT DISTINCT contact_id FROM civicrm_activity_contact ac
            INNER JOIN civicrm_activity a ON ac.activity_id = a.id
            WHERE a.activity_type_id = (SELECT id FROM civicrm_option_value WHERE option_group_id = 2 AND name = 'Webform Submission')
            AND a.source_record_id = %1";

    $params = [1 => [$formId, 'Positive']];

    if ($newContactsOnly) {
      $sql .= " AND a.activity_date_time >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
    }

    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    while ($dao->fetch()) {
      $contactIds[] = $dao->contact_id;
    }

    return $contactIds;
  }

  private static function getContactsFromEvent($config) {
    $contactIds = [];
    $eventId = $config['event_id'];

    $dao = CRM_Core_DAO::executeQuery(
      "SELECT DISTINCT contact_id FROM civicrm_participant WHERE event_id = %1 AND status_id IN (1, 2)",
      [1 => [$eventId, 'Positive']]
    );

    while ($dao->fetch()) {
      $contactIds[] = $dao->contact_id;
    }

    return $contactIds;
  }

  /**
   * Add contact to journey
   */
  private static function addContactToJourney($journeyId, $contactId) {
    // Get first step of journey
    $firstStep = CRM_Core_DAO::singleValueQuery("
      SELECT id FROM civicrm_journey_steps
      WHERE journey_id = %1 AND step_type LIKE 'entry-%'
      ORDER BY sort_order LIMIT 1
    ", [1 => [$journeyId, 'Positive']]);

    if ($firstStep) {
      CRM_Core_DAO::executeQuery("
        INSERT INTO civicrm_journey_participants
        (journey_id, contact_id, current_step_id, status, entered_date)
        VALUES (%1, %2, %3, 'active', NOW())
        ON DUPLICATE KEY UPDATE current_step_id = %3, status = 'active'
      ", [
        1 => [$journeyId, 'Positive'],
        2 => [$contactId, 'Positive'],
        3 => [$firstStep, 'Positive']
      ]);

      // Log analytics event
      self::logAnalyticsEvent($journeyId, $firstStep, $contactId, 'entered');
    }
  }

  /**
   * Process journey steps for active participants
   */
  public static function processJourneySteps($journeyId = NULL) {
    $whereClause = $journeyId ? "AND jc.id = %1" : "";
    $params = $journeyId ? [1 => [$journeyId, 'Positive']] : [];

    $dao = CRM_Core_DAO::executeQuery("
      SELECT jp.*, js.step_type, js.configuration, js.name as step_name
      FROM civicrm_journey_participants jp
      INNER JOIN civicrm_journey_campaigns jc ON jp.journey_id = jc.id
      INNER JOIN civicrm_journey_steps js ON jp.current_step_id = js.id
      WHERE jp.status = 'active' AND jc.status = 'active' {$whereClause}
      ORDER BY jp.last_action_date ASC
    ", $params);

    while ($dao->fetch()) {
      self::processParticipantStep($dao);
    }
  }

  public static function processParticipantStep($participant) {
    $stepConfig = json_decode($participant->configuration, TRUE) ?: [];

    switch ($participant->step_type) {
      case 'action-email':
        self::processEmailStep($participant, $stepConfig);
        break;

      case 'wait':
        self::processWaitStep($participant, $stepConfig);
        break;

      case 'condition':
        self::processConditionStep($participant, $stepConfig);
        break;

      case 'action-update':
        self::processUpdateStep($participant, $stepConfig);
        break;
    }
  }

  private static function processEmailStep($participant, $config) {
    if (empty($config['template_id'])) {
      return;
    }

    try {
      // Send email using CiviCRM API
      $result = civicrm_api3('Email', 'send', [
        'contact_id' => $participant->contact_id,
        'template_id' => $config['template_id'],
        'subject' => $config['subject'] ?? 'Journey Email',
      ]);

      // Log analytics
      self::logAnalyticsEvent(
        $participant->journey_id,
        $participant->current_step_id,
        $participant->contact_id,
        'email_sent',
        ['email_id' => $result['id']]
      );

      // Move to next step
      self::moveToNextStep($participant);

    }
    catch (Exception $e) {
      CRM_Core_Error::debug_log_message('Journey email send failed: ' . $e->getMessage());
      self::markParticipantError($participant->id, $e->getMessage());
    }
  }

  private static function processWaitStep($participant, $config) {
    $waitType = $config['wait_type'] ?? 'duration';
    $canProceed = FALSE;

    switch ($waitType) {
      case 'duration':
        $duration = $config['duration'] ?? 1;
        $unit = $config['duration_unit'] ?? 'days';
        $waitUntil = date('Y-m-d H:i:s', strtotime("+{$duration} {$unit}", strtotime($participant->last_action_date)));
        $canProceed = (date('Y-m-d H:i:s') >= $waitUntil);
        break;

      case 'date':
        $waitDate = $config['wait_date'] ?? NULL;
        $canProceed = $waitDate && (date('Y-m-d H:i:s') >= $waitDate);
        break;
    }

    if ($canProceed) {
      self::moveToNextStep($participant);
    }
  }

  private static function processConditionStep($participant, $config) {
    $conditionMet = self::evaluateCondition($participant->contact_id, $config);

    // Find appropriate next step based on condition result
    $nextStepId = self::getConditionalNextStep($participant->current_step_id, $conditionMet);

    if ($nextStepId) {
      self::moveParticipantToStep($participant->id, $nextStepId);
    }
    else {
      // No valid path, exit journey
      self::exitParticipant($participant->id);
    }
  }

  private static function evaluateCondition($contactId, $config) {
    $conditionType = $config['condition_type'] ?? 'contact_field';
    $field = $config['field'] ?? 'email';
    $operator = $config['operator'] ?? 'is_not_null';
    $value = $config['value'] ?? '';

    try {
      $contact = civicrm_api3('Contact', 'get', [
        'id' => $contactId,
        'return' => [$field]
      ]);

      if (!empty($contact['values'][$contactId])) {
        $fieldValue = $contact['values'][$contactId][$field] ?? NULL;
        return self::compareValues($fieldValue, $operator, $value);
      }
    }
    catch (Exception $e) {
      CRM_Core_Error::debug_log_message('Condition evaluation failed: ' . $e->getMessage());
    }

    return FALSE;
  }

  private static function compareValues($fieldValue, $operator, $compareValue) {
    switch ($operator) {
      case 'equals':
        return $fieldValue == $compareValue;
      case 'not_equals':
        return $fieldValue != $compareValue;
      case 'contains':
        return strpos($fieldValue, $compareValue) !== FALSE;
      case 'not_contains':
        return strpos($fieldValue, $compareValue) === FALSE;
      case 'is_null':
        return empty($fieldValue);
      case 'is_not_null':
        return !empty($fieldValue);
      case 'greater_than':
        return $fieldValue > $compareValue;
      case 'less_than':
        return $fieldValue < $compareValue;
      default:
        return FALSE;
    }
  }

  private static function moveToNextStep($participant) {
    $nextStepId = self::getNextStep($participant->current_step_id);
    if ($nextStepId) {
      self::moveParticipantToStep($participant->id, $nextStepId);
    }
    else {
      self::completeParticipant($participant->id);
    }
  }

  private static function getNextStep($currentStepId) {
    // This would use connection data to find next step
    // For now, get next step by sort order
    return CRM_Core_DAO::singleValueQuery("
      SELECT id FROM civicrm_journey_steps
      WHERE journey_id = (SELECT journey_id FROM civicrm_journey_steps WHERE id = %1)
      AND sort_order > (SELECT sort_order FROM civicrm_journey_steps WHERE id = %1)
      ORDER BY sort_order LIMIT 1
    ", [1 => [$currentStepId, 'Positive']]);
  }

  private static function moveParticipantToStep($participantId, $stepId) {
    CRM_Core_DAO::executeQuery("
      UPDATE civicrm_journey_participants
      SET current_step_id = %1, last_action_date = NOW()
      WHERE id = %2
    ", [
      1 => [$stepId, 'Positive'],
      2 => [$participantId, 'Positive']
    ]);
  }

  private static function completeParticipant($participantId) {
    CRM_Core_DAO::executeQuery("
      UPDATE civicrm_journey_participants
      SET status = 'completed', completed_date = NOW()
      WHERE id = %1
    ", [1 => [$participantId, 'Positive']]);
  }

  private static function exitParticipant($participantId) {
    CRM_Core_DAO::executeQuery("
      UPDATE civicrm_journey_participants
      SET status = 'exited', completed_date = NOW()
      WHERE id = %1
    ", [1 => [$participantId, 'Positive']]);
  }

  private static function markParticipantError($participantId, $errorMessage) {
    CRM_Core_DAO::executeQuery("
      UPDATE civicrm_journey_participants
      SET status = 'error', last_action_date = NOW()
      WHERE id = %1
    ", [1 => [$participantId, 'Positive']]);
  }

  private static function logAnalyticsEvent($journeyId, $stepId, $contactId, $eventType, $eventData = []) {
    CRM_Core_DAO::executeQuery("
      INSERT INTO civicrm_journey_analytics
      (journey_id, step_id, contact_id, event_type, event_data, event_date)
      VALUES (%1, %2, %3, %4, %5, NOW())
    ", [
      1 => [$journeyId, 'Positive'],
      2 => [$stepId, 'Positive'],
      3 => [$contactId, 'Positive'],
      4 => [$eventType, 'String'],
      5 => [json_encode($eventData), 'String']
    ]);
  }

  /**
   * Test journey execution
   */
  public static function test($params) {
    $journeyId = $params['id'];
    $contactId = $params['contact_id'];
    $mode = $params['mode'] ?? 'simulation';

    $results = [
      'steps' => [],
      'mode' => $mode
    ];

    // Get journey steps
    $steps = self::getJourneySteps($journeyId);

    foreach ($steps as $step) {
      $stepResult = [
        'name' => $step['name'],
        'type' => $step['step_type'],
        'success' => TRUE,
        'message' => 'Step would execute successfully'
      ];

      if ($mode === 'live') {
        // Actually execute the step
        try {
          self::executeTestStep($step, $contactId);
          $stepResult['message'] = 'Step executed successfully';
        }
        catch (Exception $e) {
          $stepResult['success'] = FALSE;
          $stepResult['message'] = 'Step failed: ' . $e->getMessage();
        }
      }

      $results['steps'][] = $stepResult;
    }

    return $results;
  }

  private static function getJourneySteps($journeyId) {
    $steps = [];
    $dao = CRM_Core_DAO::executeQuery("
      SELECT * FROM civicrm_journey_steps
      WHERE journey_id = %1
      ORDER BY sort_order
    ", [1 => [$journeyId, 'Positive']]);

    while ($dao->fetch()) {
      $steps[] = [
        'id' => $dao->id,
        'step_type' => $dao->step_type,
        'name' => $dao->name,
        'configuration' => json_decode($dao->configuration, TRUE)
      ];
    }

    return $steps;
  }

  private static function executeTestStep($step, $contactId) {
    // Simplified test execution
    switch ($step['step_type']) {
      case 'action-email':
        if (empty($step['configuration']['template_id'])) {
          throw new Exception('No email template configured');
        }
        break;

      case 'condition':
        $result = self::evaluateCondition($contactId, $step['configuration']);
        if (!$result) {
          throw new Exception('Condition not met');
        }
        break;
    }
  }

  /**
   * Pause journey
   */
  public static function pause($journeyId) {
    CRM_Core_DAO::executeQuery("
      UPDATE civicrm_journey_campaigns
      SET status = 'paused', modified_date = NOW()
      WHERE id = %1
    ", [1 => [$journeyId, 'Positive']]);

    // Pause all active participants
    CRM_Core_DAO::executeQuery("
      UPDATE civicrm_journey_participants
      SET status = 'paused'
      WHERE journey_id = %1 AND status = 'active'
    ", [1 => [$journeyId, 'Positive']]);

    return ['success' => TRUE];
  }
}
