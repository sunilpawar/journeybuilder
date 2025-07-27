<?php

use CRM_Journeybuilder_ExtensionUtil as E;

/**
 * Business Access Object for Journey Participant entity.
 */
class CRM_Journeybuilder_BAO_JourneyParticipant extends CRM_Journeybuilder_DAO_JourneyParticipant {

  /**
   * Create a new JourneyParticipant based on array-data
   */
  public static function create($params) {
    $className = 'CRM_Journeybuilder_DAO_JourneyParticipant';
    $entityName = 'JourneyParticipant';
    $hook = empty($params['id']) ? 'create' : 'edit';

    // Set defaults
    if (empty($params['id'])) {
      $params['entered_date'] = date('YmdHis');
      $params['last_action_date'] = date('YmdHis');
    }

    CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
    $instance = new $className();
    $instance->copyValues($params);
    $instance->save();
    CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

    return $instance;
  }

  /**
   * Get status options for journey participants
   */
  public static function getStatusOptions() {
    return [
      'active' => E::ts('Active'),
      'completed' => E::ts('Completed'),
      'paused' => E::ts('Paused'),
      'exited' => E::ts('Exited'),
      'error' => E::ts('Error'),
    ];
  }

  /**
   * Add contact to journey
   */
  public static function addContactToJourney($journeyId, $contactId) {
    // Get first step of journey
    $firstStep = CRM_Core_DAO::singleValueQuery("
      SELECT id FROM civicrm_journey_steps
      WHERE journey_id = %1 AND step_type LIKE 'entry%'
      ORDER BY sort_order LIMIT 1
    ", [1 => [$journeyId, 'Positive']]);

    if ($firstStep) {
      $participant = new CRM_Journeybuilder_DAO_JourneyParticipant();
      $participant->journey_id = $journeyId;
      $participant->contact_id = $contactId;
      $participant->current_step_id = $firstStep;
      $participant->status = 'active';
      $participant->entered_date = date('YmdHis');
      $participant->last_action_date = date('YmdHis');

      // Check if participant already exists
      $existing = new CRM_Journeybuilder_DAO_JourneyParticipant();
      $existing->journey_id = $journeyId;
      $existing->contact_id = $contactId;

      if ($existing->find(TRUE)) {
        // Update existing participant
        $existing->current_step_id = $firstStep;
        $existing->status = 'active';
        $existing->last_action_date = date('YmdHis');
        $existing->save();
        $participantId = $existing->id;
      }
      else {
        // Create new participant
        $participant->save();
        $participantId = $participant->id;
      }

      // Log analytics event
      CRM_Journeybuilder_BAO_JourneyAnalytics::logEvent(
        $journeyId, $firstStep, $contactId, 'entered'
      );

      return $participantId;
    }

    return FALSE;
  }

  /**
   * Move participant to next step
   */
  public static function moveToNextStep($participantId) {
    $participant = new CRM_Journeybuilder_DAO_JourneyParticipant();
    $participant->id = $participantId;
    if (!$participant->find(TRUE)) {
      return FALSE;
    }

    $nextStepId = CRM_Journeybuilder_BAO_JourneyStep::getNextStep($participant->current_step_id);

    if ($nextStepId) {
      $participant->current_step_id = $nextStepId;
      $participant->last_action_date = date('YmdHis');
      $participant->save();

      // Log analytics event
      CRM_Journeybuilder_BAO_JourneyAnalytics::logEvent(
        $participant->journey_id, $nextStepId, $participant->contact_id, 'entered'
      );

      return TRUE;
    }
    else {
      // No next step, complete the journey
      self::completeParticipant($participantId);
      return TRUE;
    }
  }

  /**
   * Complete participant journey
   */
  public static function completeParticipant($participantId) {
    $participant = new CRM_Journeybuilder_DAO_JourneyParticipant();
    $participant->id = $participantId;
    if ($participant->find(TRUE)) {
      $participant->status = 'completed';
      $participant->completed_date = date('YmdHis');
      $participant->last_action_date = date('YmdHis');
      $participant->save();

      // Log analytics event
      CRM_Journeybuilder_BAO_JourneyAnalytics::logEvent(
        $participant->journey_id, $participant->current_step_id,
        $participant->contact_id, 'completed'
      );
    }
  }

  /**
   * Pause participant
   */
  public static function pauseParticipant($participantId) {
    $participant = new CRM_Journeybuilder_DAO_JourneyParticipant();
    $participant->id = $participantId;
    if ($participant->find(TRUE)) {
      $participant->status = 'paused';
      $participant->last_action_date = date('YmdHis');
      $participant->save();
    }
  }

  /**
   * Resume participant
   */
  public static function resumeParticipant($participantId) {
    $participant = new CRM_Journeybuilder_DAO_JourneyParticipant();
    $participant->id = $participantId;
    if ($participant->find(TRUE)) {
      $participant->status = 'active';
      $participant->last_action_date = date('YmdHis');
      $participant->save();
    }
  }

  /**
   * Mark participant as error
   */
  public static function markParticipantError($participantId, $errorMessage = '') {
    $participant = new CRM_Journeybuilder_DAO_JourneyParticipant();
    $participant->id = $participantId;
    if ($participant->find(TRUE)) {
      $participant->status = 'error';
      $participant->last_action_date = date('YmdHis');
      $participant->save();

      // Log error in analytics
      CRM_Journeybuilder_BAO_JourneyAnalytics::logEvent(
        $participant->journey_id, $participant->current_step_id,
        $participant->contact_id, 'error', ['message' => $errorMessage]
      );
    }
  }

  /**
   * Get participants for a journey
   */
  public static function getJourneyParticipants($journeyId, $params = []) {
    $whereClause = "WHERE jp.journey_id = %1";
    $sqlParams = [1 => [$journeyId, 'Positive']];
    $paramIndex = 2;

    if (!empty($params['status'])) {
      $whereClause .= " AND jp.status = %{$paramIndex}";
      $sqlParams[$paramIndex] = [$params['status'], 'String'];
      $paramIndex++;
    }

    if (!empty($params['current_step_id'])) {
      $whereClause .= " AND jp.current_step_id = %{$paramIndex}";
      $sqlParams[$paramIndex] = [$params['current_step_id'], 'Positive'];
      $paramIndex++;
    }

    $limit = "";
    if (!empty($params['limit'])) {
      $limit = "LIMIT " . (int)$params['limit'];
      if (!empty($params['offset'])) {
        $limit = "LIMIT " . (int)$params['offset'] . ", " . (int)$params['limit'];
      }
    }

    $participants = [];
    $dao = CRM_Core_DAO::executeQuery("
      SELECT
        jp.*,
        c.display_name,
        c.email,
        js.name as current_step_name,
        js.step_type as current_step_type
      FROM civicrm_journey_participants jp
      INNER JOIN civicrm_contact c ON jp.contact_id = c.id
      LEFT JOIN civicrm_journey_steps js ON jp.current_step_id = js.id
      {$whereClause}
      ORDER BY jp.entered_date DESC
      {$limit}
    ", $sqlParams);

    while ($dao->fetch()) {
      $participants[] = [
        'id' => $dao->id,
        'contact_id' => $dao->contact_id,
        'display_name' => $dao->display_name,
        'email' => $dao->email,
        'status' => $dao->status,
        'current_step_name' => $dao->current_step_name,
        'current_step_type' => $dao->current_step_type,
        'entered_date' => $dao->entered_date,
        'completed_date' => $dao->completed_date,
        'last_action_date' => $dao->last_action_date
      ];
    }

    return $participants;
  }

  /**
   * Get participant journey history
   */
  public static function getParticipantHistory($participantId) {
    $participant = new CRM_Journeybuilder_DAO_JourneyParticipant();
    $participant->id = $participantId;
    if (!$participant->find(TRUE)) {
      return [];
    }

    $history = [];
    $dao = CRM_Core_DAO::executeQuery("
      SELECT
        ja.*,
        js.name as step_name,
        js.step_type
      FROM civicrm_journey_analytics ja
      LEFT JOIN civicrm_journey_steps js ON ja.step_id = js.id
      WHERE ja.journey_id = %1 AND ja.contact_id = %2
      ORDER BY ja.event_date ASC
    ", [
      1 => [$participant->journey_id, 'Positive'],
      2 => [$participant->contact_id, 'Positive']
    ]);

    while ($dao->fetch()) {
      $history[] = [
        'id' => $dao->id,
        'step_id' => $dao->step_id,
        'step_name' => $dao->step_name,
        'step_type' => $dao->step_type,
        'event_type' => $dao->event_type,
        'event_data' => json_decode($dao->event_data, TRUE),
        'event_date' => $dao->event_date
      ];
    }

    return $history;
  }

  /**
   * Get participant statistics
   */
  public static function getParticipantStats($journeyId) {
    $stats = [];

    $dao = CRM_Core_DAO::executeQuery("
      SELECT
        status,
        COUNT(*) as count,
        AVG(TIMESTAMPDIFF(HOUR, entered_date, COALESCE(completed_date, NOW()))) as avg_duration_hours
      FROM civicrm_journey_participants
      WHERE journey_id = %1
      GROUP BY status
    ", [1 => [$journeyId, 'Positive']]);

    while ($dao->fetch()) {
      $stats[$dao->status] = [
        'count' => (int)$dao->count,
        'avg_duration_hours' => round($dao->avg_duration_hours, 2)
      ];
    }

    return $stats;
  }

  /**
   * Remove participant from journey
   */
  public static function removeFromJourney($participantId, $reason = 'manual') {
    $participant = new CRM_Journeybuilder_DAO_JourneyParticipant();
    $participant->id = $participantId;
    if ($participant->find(TRUE)) {
      $participant->status = 'exited';
      $participant->completed_date = date('YmdHis');
      $participant->last_action_date = date('YmdHis');
      $participant->save();

      // Log analytics event
      CRM_Journeybuilder_BAO_JourneyAnalytics::logEvent(
        $participant->journey_id, $participant->current_step_id,
        $participant->contact_id, 'exited', ['reason' => $reason]
      );
    }
  }

  /**
   * Process active participants for step execution
   */
  public static function processActiveParticipants($journeyId = NULL) {
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

    $processedCount = 0;
    while ($dao->fetch()) {
      try {
        CRM_Journeybuilder_API_Journey::processParticipantStep($dao);
        $processedCount++;
      }
      catch (Exception $e) {
        CRM_Core_Error::debug_log_message(
          "Error processing participant {$dao->id}: " . $e->getMessage()
        );
        self::markParticipantError($dao->id, $e->getMessage());
      }
    }

    return $processedCount;
  }

}
