<?php

use CRM_Journeybuilder_ExtensionUtil as E;

/**
 * Business Access Object for Journey Step entity.
 */
class CRM_Journeybuilder_BAO_JourneyStep extends CRM_Journeybuilder_DAO_JourneyStep {

  /**
   * Create a new JourneyStep based on array-data
   *
   * @param array $params key-value pairs
   * @return CRM_Journeybuilder_DAO_JourneyStep|NULL
   */
  public static function create($params) {
    $className = 'CRM_Journeybuilder_DAO_JourneyStep';
    $entityName = 'JourneyStep';
    $hook = empty($params['id']) ? 'create' : 'edit';

    CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
    $instance = new $className();
    $instance->copyValues($params);
    $instance->save();
    CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

    return $instance;
  }

  /**
   * Get step type options
   *
   * @return array
   */
  public static function getStepTypeOptions() {
    return [
      'entry' => E::ts('Entry Point'),
      'email' => E::ts('Send Email'),
      'sms' => E::ts('Send SMS'),
      'wait' => E::ts('Wait'),
      'condition' => E::ts('Condition'),
      'action' => E::ts('Action'),
      'exit' => E::ts('Exit'),
    ];
  }

  /**
   * Get steps for a journey
   *
   * @param int $journeyId
   * @return array
   */
  public static function getJourneySteps($journeyId) {
    $steps = [];
    $dao = CRM_Core_DAO::executeQuery("
      SELECT * FROM civicrm_journey_steps
      WHERE journey_id = %1
      ORDER BY sort_order
    ", [1 => [$journeyId, 'Positive']]);

    while ($dao->fetch()) {
      $steps[] = [
        'id' => $dao->id,
        'journey_id' => $dao->journey_id,
        'step_type' => $dao->step_type,
        'name' => $dao->name,
        'configuration' => json_decode($dao->configuration, TRUE) ?: [],
        'position_x' => (float) $dao->position_x,
        'position_y' => (float) $dao->position_y,
        'sort_order' => (int) $dao->sort_order,
      ];
    }
    return $steps;
  }

  /**
   * Save multiple journey steps
   *
   * @param int $journeyId
   * @param array $steps
   * @return bool
   */
  public static function saveJourneySteps($journeyId, $steps) {
    // Delete existing steps
    CRM_Core_DAO::executeQuery("DELETE FROM civicrm_journey_steps WHERE journey_id = %1", [
      1 => [$journeyId, 'Positive'],
    ]);

    // Insert new steps
    foreach ($steps as $index => $stepData) {
      $step = new CRM_Journeybuilder_DAO_JourneyStep();
      $step->journey_id = $journeyId;
      $step->step_type = $stepData['type'] ?? $stepData['step_type'];
      $step->name = $stepData['name'];
      $step->configuration = json_encode($stepData['configuration'] ?? []);
      $step->position_x = $stepData['position']['x'] ?? $stepData['position_x'] ?? 0;
      $step->position_y = $stepData['position']['y'] ?? $stepData['position_y'] ?? 0;
      $step->sort_order = $index;
      $step->save();
    }

    return TRUE;
  }

  /**
   * Get step configuration with defaults
   *
   * @param int $stepId
   * @return array
   */
  public static function getStepConfiguration($stepId) {
    $step = new CRM_Journeybuilder_DAO_JourneyStep();
    $step->id = $stepId;
    if (!$step->find(TRUE)) {
      return [];
    }

    $config = json_decode($step->configuration, TRUE) ?: [];

    // Add default configuration based on step type
    $config = array_merge(self::getDefaultConfiguration($step->step_type), $config);

    return [
      'id' => $step->id,
      'journey_id' => $step->journey_id,
      'step_type' => $step->step_type,
      'name' => $step->name,
      'configuration' => $config,
      'position_x' => (float) $step->position_x,
      'position_y' => (float) $step->position_y,
      'sort_order' => (int) $step->sort_order,
    ];
  }

  /**
   * Get default configuration for step type
   *
   * @param string $stepType
   * @return array
   */
  public static function getDefaultConfiguration($stepType) {
    switch ($stepType) {
      case 'email':
        return [
          'template_id' => NULL,
          'subject' => '',
          'delay' => 'immediate',
          'personalize' => FALSE,
          'ab_test' => FALSE,
        ];

      case 'sms':
        return [
          'message' => '',
          'delay' => 'immediate',
          'provider' => 'default',
        ];

      case 'condition':
        return [
          'condition_type' => 'contact_field',
          'field' => 'email',
          'operator' => 'is_not_null',
          'value' => '',
          'logic_operator' => 'AND',
        ];

      case 'wait':
        return [
          'wait_type' => 'duration',
          'duration' => 1,
          'duration_unit' => 'days',
          'wait_date' => NULL,
        ];

      case 'action':
        return [
          'action_type' => 'update_contact',
          'field_updates' => [],
          'tag_action' => 'add',
          'tag_ids' => [],
        ];

      case 'entry':
        return [
          'entry_type' => 'manual',
          'form_id' => NULL,
          'event_id' => NULL,
          'group_id' => NULL,
          'new_contacts_only' => FALSE,
        ];

      default:
        return [];
    }
  }

  /**
   * Validate step configuration
   *
   * @param array $stepData
   * @return array
   */
  public static function validateStepConfiguration($stepData) {
    $errors = [];
    $stepType = $stepData['step_type'] ?? $stepData['type'];
    $config = $stepData['configuration'] ?? [];

    switch ($stepType) {
      case 'email':
        if (empty($config['template_id']) && empty($config['subject'])) {
          $errors[] = 'Email step must have either a template or subject configured';
        }
        break;

      case 'sms':
        if (empty($config['message'])) {
          $errors[] = 'SMS step must have a message configured';
        }
        break;

      case 'condition':
        if (empty($config['field']) || empty($config['operator'])) {
          $errors[] = 'Condition step must have field and operator configured';
        }
        break;

      case 'wait':
        if ($config['wait_type'] === 'duration') {
          if (empty($config['duration']) || empty($config['duration_unit'])) {
            $errors[] = 'Wait step with duration must have duration and unit configured';
          }
        } elseif ($config['wait_type'] === 'date') {
          if (empty($config['wait_date'])) {
            $errors[] = 'Wait step with date must have date configured';
          }
        }
        break;

      case 'entry':
        $entryType = $config['entry_type'] ?? 'manual';
        if ($entryType === 'form' && empty($config['form_id'])) {
          $errors[] = 'Form entry step must have form ID configured';
        } elseif ($entryType === 'event' && empty($config['event_id'])) {
          $errors[] = 'Event entry step must have event ID configured';
        }
        break;
    }

    return $errors;
  }

  /**
   * Get next step in journey flow
   *
   * @param int $currentStepId
   * @param array $conditionResult For conditional steps
   * @return int|null
   */
  public static function getNextStep($currentStepId, $conditionResult = NULL) {
    // First try to get explicit connections if they exist
    $nextStepId = CRM_Core_DAO::singleValueQuery("
      SELECT to_step_id FROM civicrm_journey_connections
      WHERE from_step_id = %1
      AND (condition_type = 'default' OR
           (condition_type = 'condition_true' AND %2 = 1) OR
           (condition_type = 'condition_false' AND %2 = 0))
      ORDER BY sort_order LIMIT 1
    ", [
      1 => [$currentStepId, 'Positive'],
      2 => [$conditionResult ? 1 : 0, 'Integer'],
    ]);

    if ($nextStepId) {
      return $nextStepId;
    }

    // Fallback to sort order based next step
    return CRM_Core_DAO::singleValueQuery("
      SELECT id FROM civicrm_journey_steps
      WHERE journey_id = (SELECT journey_id FROM civicrm_journey_steps WHERE id = %1)
      AND sort_order > (SELECT sort_order FROM civicrm_journey_steps WHERE id = %1)
      ORDER BY sort_order LIMIT 1
    ", [1 => [$currentStepId, 'Positive']]);
  }

  /**
   * Get step statistics
   *
   * @param int $stepId
   * @return array
   */
  public static function getStepStatistics($stepId) {
    $stats = [];

    // Get participant counts
    $participantStats = CRM_Core_DAO::executeQuery("
      SELECT
        COUNT(CASE WHEN jp.current_step_id = %1 THEN 1 END) as current_participants,
        COUNT(CASE WHEN ja.step_id = %1 AND ja.event_type = 'entered' THEN 1 END) as total_entered,
        COUNT(CASE WHEN ja.step_id = %1 AND ja.event_type = 'completed' THEN 1 END) as total_completed
      FROM civicrm_journey_participants jp
      LEFT JOIN civicrm_journey_analytics ja ON jp.contact_id = ja.contact_id AND jp.journey_id = ja.journey_id
      WHERE jp.journey_id = (SELECT journey_id FROM civicrm_journey_steps WHERE id = %1)
    ", [1 => [$stepId, 'Positive']]);

    if ($participantStats->fetch()) {
      $stats['current_participants'] = (int) $participantStats->current_participants;
      $stats['total_entered'] = (int) $participantStats->total_entered;
      $stats['total_completed'] = (int) $participantStats->total_completed;
      $stats['completion_rate'] = $participantStats->total_entered > 0 ?
        round(($participantStats->total_completed / $participantStats->total_entered) * 100, 2) : 0;
    }

    // Get email specific stats if email step
    $step = new CRM_Journeybuilder_DAO_JourneyStep();
    $step->id = $stepId;
    if ($step->find(TRUE) && $step->step_type === 'email') {
      $emailStats = CRM_Core_DAO::executeQuery("
        SELECT
          COUNT(CASE WHEN event_type = 'email_sent' THEN 1 END) as emails_sent,
          COUNT(CASE WHEN event_type = 'email_opened' THEN 1 END) as emails_opened,
          COUNT(CASE WHEN event_type = 'email_clicked' THEN 1 END) as emails_clicked,
          COUNT(CASE WHEN event_type = 'bounced' THEN 1 END) as emails_bounced
        FROM civicrm_journey_analytics
        WHERE step_id = %1
      ", [1 => [$stepId, 'Positive']]);

      if ($emailStats->fetch()) {
        $stats['emails_sent'] = (int) $emailStats->emails_sent;
        $stats['emails_opened'] = (int) $emailStats->emails_opened;
        $stats['emails_clicked'] = (int) $emailStats->emails_clicked;
        $stats['emails_bounced'] = (int) $emailStats->emails_bounced;
        $stats['open_rate'] = $emailStats->emails_sent > 0 ?
          round(($emailStats->emails_opened / $emailStats->emails_sent) * 100, 2) : 0;
        $stats['click_rate'] = $emailStats->emails_opened > 0 ?
          round(($emailStats->emails_clicked / $emailStats->emails_opened) * 100, 2) : 0;
        $stats['bounce_rate'] = $emailStats->emails_sent > 0 ?
          round(($emailStats->emails_bounced / $emailStats->emails_sent) * 100, 2) : 0;
      }
    }

    return $stats;
  }

  /**
   * Delete step and all related data
   *
   * @param int $stepId
   * @return bool
   * @throws Exception
   */
  public static function deleteStep($stepId) {
    $step = new CRM_Journeybuilder_DAO_JourneyStep();
    $step->id = $stepId;
    if (!$step->find(TRUE)) {
      throw new Exception('Step not found');
    }

    // Check if step is in use by active participants
    $activeParticipants = CRM_Core_DAO::singleValueQuery("
      SELECT COUNT(*) FROM civicrm_journey_participants
      WHERE current_step_id = %1 AND status = 'active'
    ", [1 => [$stepId, 'Positive']]);

    if ($activeParticipants > 0) {
      throw new Exception('Cannot delete step with active participants');
    }

    // Delete related data
    CRM_Core_DAO::executeQuery("DELETE FROM civicrm_journey_conditions WHERE step_id = %1",
      [1 => [$stepId, 'Positive']]);
    CRM_Core_DAO::executeQuery("DELETE FROM civicrm_journey_email_templates WHERE step_id = %1",
      [1 => [$stepId, 'Positive']]);
    CRM_Core_DAO::executeQuery("DELETE FROM civicrm_journey_connections WHERE from_step_id = %1 OR to_step_id = %1",
      [1 => [$stepId, 'Positive']]);

    // Delete the step
    $step->delete();

    return TRUE;
  }

  /**
   * Reorder steps in a journey
   *
   * @param int $journeyId
   * @param array $stepOrder Array of step IDs in new order
   * @return bool
   */
  public static function reorderSteps($journeyId, $stepOrder) {
    foreach ($stepOrder as $index => $stepId) {
      CRM_Core_DAO::executeQuery("
        UPDATE civicrm_journey_steps
        SET sort_order = %1
        WHERE id = %2 AND journey_id = %3
      ", [
        1 => [$index, 'Integer'],
        2 => [$stepId, 'Positive'],
        3 => [$journeyId, 'Positive'],
      ]);
    }

    return TRUE;
  }

  /**
   * Clone step configuration
   *
   * @param int $stepId
   * @param int $newJourneyId
   * @return int New step ID
   */
  public static function cloneStep($stepId, $newJourneyId) {
    $originalStep = new CRM_Journeybuilder_DAO_JourneyStep();
    $originalStep->id = $stepId;
    if (!$originalStep->find(TRUE)) {
      throw new Exception('Step not found');
    }

    $newStep = new CRM_Journeybuilder_DAO_JourneyStep();
    $newStep->journey_id = $newJourneyId;
    $newStep->step_type = $originalStep->step_type;
    $newStep->name = $originalStep->name . ' (Copy)';
    $newStep->configuration = $originalStep->configuration;
    $newStep->position_x = $originalStep->position_x + 50; // Offset position
    $newStep->position_y = $originalStep->position_y + 50;
    $newStep->sort_order = $originalStep->sort_order;
    $newStep->save();

    return $newStep->id;
  }

  public static function stepsType() {
    return [
      'entry' => 'entry',
      'email' => 'email',
      'sms' => 'sms',
      'wait' => 'wait',
      'condition' => 'condition',
      'action' => 'action',
      'exit' => 'exit',
    ];
  }

}
