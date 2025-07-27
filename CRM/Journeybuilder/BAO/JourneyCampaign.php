<?php

use CRM_Journeybuilder_ExtensionUtil as E;

/**
 * Business Access Object for Journey Campaign entity.
 */
class CRM_Journeybuilder_BAO_JourneyCampaign extends CRM_Journeybuilder_DAO_JourneyCampaign {

  /**
   * Create a new JourneyCampaign based on array-data
   *
   * @param array $params key-value pairs
   * @return CRM_Journeybuilder_DAO_JourneyCampaign|NULL
   */
  public static function create($params) {
    $className = 'CRM_Journeybuilder_DAO_JourneyCampaign';
    $entityName = 'JourneyCampaign';
    $hook = empty($params['id']) ? 'create' : 'edit';

    // Set defaults
    if (empty($params['id'])) {
      $params['created_date'] = date('YmdHis');
      $params['created_id'] = CRM_Core_Session::getLoggedInContactID();
    }
    $params['modified_date'] = date('YmdHis');

    CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
    $instance = new $className();
    $instance->copyValues($params);
    $instance->save();
    CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

    return $instance;
  }

  /**
   * Get status options for journey campaigns
   *
   * @return array
   */
  public static function getStatusOptions() {
    return [
      'draft' => E::ts('Draft'),
      'active' => E::ts('Active'), 
      'paused' => E::ts('Paused'),
      'completed' => E::ts('Completed'),
      'archived' => E::ts('Archived'),
    ];
  }

  /**
   * Activate a journey campaign
   *
   * @param int $journeyId
   * @return array
   * @throws Exception
   */
  public static function activate($journeyId) {
    $journey = new CRM_Journeybuilder_DAO_JourneyCampaign();
    $journey->id = $journeyId;
    if (!$journey->find(TRUE)) {
      throw new Exception('Journey not found');
    }

    // Validate journey before activation
    $validation = self::validateJourney($journeyId);
    if (!$validation['valid']) {
      return ['error' => $validation['errors']];
    }

    // Update status to active
    $journey->status = 'active';
    $journey->activated_date = date('YmdHis');
    $journey->modified_date = date('YmdHis');
    $journey->save();

    // Initialize journey participants based on entry criteria
    self::initializeJourneyParticipants($journeyId);

    // Log the activation
    CRM_Core_Error::debug_log_message("Journey {$journeyId} activated successfully");

    return ['success' => TRUE];
  }

  /**
   * Pause a journey campaign
   *
   * @param int $journeyId
   * @return array
   * @throws Exception
   */
  public static function pause($journeyId) {
    $journey = new CRM_Journeybuilder_DAO_JourneyCampaign();
    $journey->id = $journeyId;
    if (!$journey->find(TRUE)) {
      throw new Exception('Journey not found');
    }

    $journey->status = 'paused';
    $journey->modified_date = date('YmdHis');
    $journey->save();

    // Pause all active participants
    CRM_Core_DAO::executeQuery("
      UPDATE civicrm_journey_participants
      SET status = 'paused'
      WHERE journey_id = %1 AND status = 'active'
    ", [1 => [$journeyId, 'Positive']]);

    return ['success' => TRUE];
  }

  /**
   * Archive a journey campaign
   *
   * @param int $journeyId
   * @return bool
   * @throws Exception
   */
  public static function archive($journeyId) {
    $journey = new CRM_Journeybuilder_DAO_JourneyCampaign();
    $journey->id = $journeyId;
    if (!$journey->find(TRUE)) {
      throw new Exception('Journey not found');
    }

    $journey->status = 'archived';
    $journey->modified_date = date('YmdHis');
    $journey->save();

    // Exit all active participants
    CRM_Core_DAO::executeQuery("
      UPDATE civicrm_journey_participants
      SET status = 'exited', completed_date = NOW()
      WHERE journey_id = %1 AND status IN ('active', 'paused')
    ", [1 => [$journeyId, 'Positive']]);

    return TRUE;
  }

  /**
   * Duplicate a journey campaign
   *
   * @param int $journeyId
   * @param string $newName
   * @return int New journey ID
   * @throws Exception
   */
  public static function duplicate($journeyId, $newName = NULL) {
    $originalJourney = new CRM_Journeybuilder_DAO_JourneyCampaign();
    $originalJourney->id = $journeyId;
    if (!$originalJourney->find(TRUE)) {
      throw new Exception('Journey not found');
    }

    // Create new journey
    $newJourney = new CRM_Journeybuilder_DAO_JourneyCampaign();
    $newJourney->name = $newName ?: ($originalJourney->name . ' (Copy)');
    $newJourney->description = $originalJourney->description;
    $newJourney->configuration = $originalJourney->configuration;
    $newJourney->status = 'draft';
    $newJourney->created_date = date('YmdHis');
    $newJourney->created_id = CRM_Core_Session::getLoggedInContactID();
    $newJourney->save();

    $newJourneyId = $newJourney->id;

    // Copy steps
    $stepMapping = [];
    $stepsDao = CRM_Core_DAO::executeQuery("
      SELECT * FROM civicrm_journey_steps WHERE journey_id = %1 ORDER BY sort_order
    ", [1 => [$journeyId, 'Positive']]);

    while ($stepsDao->fetch()) {
      $newStep = new CRM_Journeybuilder_DAO_JourneyStep();
      $newStep->journey_id = $newJourneyId;
      $newStep->step_type = $stepsDao->step_type;
      $newStep->name = $stepsDao->name;
      $newStep->configuration = $stepsDao->configuration;
      $newStep->position_x = $stepsDao->position_x;
      $newStep->position_y = $stepsDao->position_y;
      $newStep->sort_order = $stepsDao->sort_order;
      $newStep->save();
      
      $stepMapping[$stepsDao->id] = $newStep->id;
    }

    // Copy conditions
    $conditionsDao = CRM_Core_DAO::executeQuery("
      SELECT jc.*, js.journey_id as original_journey_id 
      FROM civicrm_journey_conditions jc
      INNER JOIN civicrm_journey_steps js ON jc.step_id = js.id
      WHERE js.journey_id = %1
    ", [1 => [$journeyId, 'Positive']]);

    while ($conditionsDao->fetch()) {
      if (isset($stepMapping[$conditionsDao->step_id])) {
        $newCondition = new CRM_Journeybuilder_DAO_JourneyCondition();
        $newCondition->step_id = $stepMapping[$conditionsDao->step_id];
        $newCondition->condition_type = $conditionsDao->condition_type;
        $newCondition->field_name = $conditionsDao->field_name;
        $newCondition->operator = $conditionsDao->operator;
        $newCondition->value = $conditionsDao->value;
        $newCondition->logic_operator = $conditionsDao->logic_operator;
        $newCondition->sort_order = $conditionsDao->sort_order;
        $newCondition->save();
      }
    }

    // Copy email templates
    $templatesDao = CRM_Core_DAO::executeQuery("
      SELECT jet.*, js.journey_id as original_journey_id 
      FROM civicrm_journey_email_templates jet
      INNER JOIN civicrm_journey_steps js ON jet.step_id = js.id
      WHERE js.journey_id = %1
    ", [1 => [$journeyId, 'Positive']]);

    while ($templatesDao->fetch()) {
      if (isset($stepMapping[$templatesDao->step_id])) {
        $newTemplate = new CRM_Journeybuilder_DAO_JourneyEmailTemplate();
        $newTemplate->step_id = $stepMapping[$templatesDao->step_id];
        $newTemplate->mosaico_template_id = $templatesDao->mosaico_template_id;
        $newTemplate->subject = $templatesDao->subject;
        $newTemplate->html_content = $templatesDao->html_content;
        $newTemplate->text_content = $templatesDao->text_content;
        $newTemplate->personalization_rules = $templatesDao->personalization_rules;
        $newTemplate->ab_test_config = $templatesDao->ab_test_config;
        $newTemplate->save();
      }
    }

    return $newJourneyId;
  }

  /**
   * Get journey campaigns list with statistics
   *
   * @param array $params
   * @return array
   */
  public static function getJourneyList($params = []) {
    $whereClause = "WHERE 1=1";
    $sqlParams = [];
    
    if (!empty($params['status'])) {
      $whereClause .= " AND jc.status = %1";
      $sqlParams[1] = [$params['status'], 'String'];
    }
    
    if (!empty($params['created_id'])) {
      $whereClause .= " AND jc.created_id = %2";
      $sqlParams[2] = [$params['created_id'], 'Positive'];
    }
    
    $journeys = [];
    $dao = CRM_Core_DAO::executeQuery("
      SELECT 
        jc.*,
        COUNT(DISTINCT jp.contact_id) as participant_count,
        COUNT(DISTINCT js.id) as step_count,
        cc.display_name as creator_name
      FROM civicrm_journey_campaigns jc
      LEFT JOIN civicrm_journey_participants jp ON jc.id = jp.journey_id
      LEFT JOIN civicrm_journey_steps js ON jc.id = js.journey_id
      LEFT JOIN civicrm_contact cc ON jc.created_id = cc.id
      {$whereClause}
      GROUP BY jc.id
      ORDER BY jc.modified_date DESC, jc.created_date DESC
    ", $sqlParams);
    
    while ($dao->fetch()) {
      $journeys[] = [
        'id' => $dao->id,
        'name' => $dao->name,
        'description' => $dao->description,
        'status' => $dao->status,
        'created_date' => $dao->created_date,
        'modified_date' => $dao->modified_date,
        'activated_date' => $dao->activated_date,
        'creator_name' => $dao->creator_name,
        'participant_count' => (int) $dao->participant_count,
        'step_count' => (int) $dao->step_count
      ];
    }
    
    return $journeys;
  }

  /**
   * Validate journey configuration before activation
   *
   * @param int $journeyId
   * @return array
   */
  private static function validateJourney($journeyId) {
    $errors = [];
    $valid = TRUE;

    // Check for entry point
    $entrySteps = CRM_Core_DAO::singleValueQuery("
      SELECT COUNT(*) FROM civicrm_journey_steps
      WHERE journey_id = %1 AND step_type LIKE 'entry-%'
    ", [1 => [$journeyId, 'Positive']]);

    if ($entrySteps == 0) {
      $errors[] = 'Journey must have at least one entry point';
      $valid = FALSE;
    }

    // Check for at least one action step
    $actionSteps = CRM_Core_DAO::singleValueQuery("
      SELECT COUNT(*) FROM civicrm_journey_steps
      WHERE journey_id = %1 AND step_type IN ('email', 'sms', 'action')
    ", [1 => [$journeyId, 'Positive']]);

    if ($actionSteps == 0) {
      $errors[] = 'Journey must have at least one action step';
      $valid = FALSE;
    }

    // Check email steps have templates configured
    $emailStepsWithoutTemplate = CRM_Core_DAO::singleValueQuery("
      SELECT COUNT(*) FROM civicrm_journey_steps js
      LEFT JOIN civicrm_journey_email_templates jet ON js.id = jet.step_id
      WHERE js.journey_id = %1 AND js.step_type = 'email' 
      AND (jet.id IS NULL OR (jet.subject IS NULL AND jet.html_content IS NULL))
    ", [1 => [$journeyId, 'Positive']]);

    if ($emailStepsWithoutTemplate > 0) {
      $errors[] = 'All email steps must have templates configured';
      $valid = FALSE;
    }

    return ['valid' => $valid, 'errors' => $errors];
  }

  /**
   * Initialize journey participants based on entry criteria
   *
   * @param int $journeyId
   */
  private static function initializeJourneyParticipants($journeyId) {
    $journey = new CRM_Journeybuilder_DAO_JourneyCampaign();
    $journey->id = $journeyId;
    if (!$journey->find(TRUE)) {
      return;
    }

    $config = json_decode($journey->configuration, TRUE) ?: [];
    $entryCriteria = $config['entry_criteria'] ?? [];

    // Build contact query based on entry criteria
    $contactIds = self::getContactsForEntry($entryCriteria);

    // Add contacts to journey
    foreach ($contactIds as $contactId) {
      CRM_Journeybuilder_BAO_JourneyParticipant::addContactToJourney($journeyId, $contactId);
    }
  }

  /**
   * Get contacts matching entry criteria
   *
   * @param array $criteria
   * @return array
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

        case 'entry-date':
          // For date triggers, get all active contacts or based on specific criteria
          if (!empty($config['contact_group'])) {
            $contactIds = array_merge($contactIds, self::getContactsFromGroup($config));
          }
          break;
      }
    }
    
    return array_unique($contactIds);
  }

  /**
   * Get contacts from form submissions
   *
   * @param array $config
   * @return array
   */
  private static function getContactsFromForm($config) {
    $contactIds = [];
    $formId = $config['form_id'];
    $newContactsOnly = $config['new_contacts_only'] ?? false;
    
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

  /**
   * Get contacts from event registrations
   *
   * @param array $config
   * @return array
   */
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
   * Get contacts from a group
   *
   * @param array $config
   * @return array
   */
  private static function getContactsFromGroup($config) {
    $contactIds = [];
    $groupId = $config['contact_group'];
    
    try {
      $result = civicrm_api3('Contact', 'get', [
        'group' => $groupId,
        'is_deleted' => 0,
        'is_deceased' => 0,
        'return' => ['id'],
        'options' => ['limit' => 0],
      ]);
      
      foreach ($result['values'] as $contact) {
        $contactIds[] = $contact['id'];
      }
    } catch (Exception $e) {
      CRM_Core_Error::debug_log_message('Error getting contacts from group: ' . $e->getMessage());
    }
    
    return $contactIds;
  }

  /**
   * Get journey analytics summary
   *
   * @param int $journeyId
   * @param array $dateRange
   * @return array
   */
  public static function getAnalyticsSummary($journeyId, $dateRange = NULL) {
    $whereClause = "WHERE ja.journey_id = %1";
    $params = [1 => [$journeyId, 'Positive']];
    
    if ($dateRange) {
      $whereClause .= " AND ja.event_date BETWEEN %2 AND %3";
      $params[2] = [$dateRange['start'], 'String'];
      $params[3] = [$dateRange['end'], 'String'];
    }
    
    // Get step performance data
    $stepAnalytics = [];
    $dao = CRM_Core_DAO::executeQuery("
      SELECT 
        js.id as step_id,
        js.name as step_name,
        js.step_type,
        COUNT(CASE WHEN ja.event_type = 'entered' THEN 1 END) as entered_count,
        COUNT(CASE WHEN ja.event_type = 'completed' THEN 1 END) as completed_count,
        COUNT(CASE WHEN ja.event_type = 'email_sent' THEN 1 END) as emails_sent,
        COUNT(CASE WHEN ja.event_type = 'email_opened' THEN 1 END) as emails_opened,
        COUNT(CASE WHEN ja.event_type = 'email_clicked' THEN 1 END) as emails_clicked
      FROM civicrm_journey_steps js
      LEFT JOIN civicrm_journey_analytics ja ON js.id = ja.step_id
      {$whereClause}
      GROUP BY js.id
      ORDER BY js.sort_order
    ", $params);
    
    while ($dao->fetch()) {
      $stepAnalytics[] = [
        'step_id' => $dao->step_id,
        'step_name' => $dao->step_name,
        'step_type' => $dao->step_type,
        'entered_count' => (int) $dao->entered_count,
        'completed_count' => (int) $dao->completed_count,
        'emails_sent' => (int) $dao->emails_sent,
        'emails_opened' => (int) $dao->emails_opened,
        'emails_clicked' => (int) $dao->emails_clicked,
        'open_rate' => $dao->emails_sent > 0 ? round(($dao->emails_opened / $dao->emails_sent) * 100, 2) : 0,
        'click_rate' => $dao->emails_opened > 0 ? round(($dao->emails_clicked / $dao->emails_opened) * 100, 2) : 0
      ];
    }
    
    // Get overall journey stats
    $overallStats = CRM_Core_DAO::executeQuery("
      SELECT 
        COUNT(DISTINCT jp.contact_id) as total_participants,
        COUNT(CASE WHEN jp.status = 'active' THEN 1 END) as active_participants,
        COUNT(CASE WHEN jp.status = 'completed' THEN 1 END) as completed_participants,
        COUNT(CASE WHEN jp.status = 'exited' THEN 1 END) as exited_participants,
        COUNT(CASE WHEN jp.status = 'error' THEN 1 END) as error_participants
      FROM civicrm_journey_participants jp
      WHERE jp.journey_id = %1
    ", [1 => [$journeyId, 'Positive']]);
    
    $overallStats->fetch();
    
    return [
      'step_analytics' => $stepAnalytics,
      'overall_stats' => [
        'total_participants' => (int) $overallStats->total_participants,
        'active_participants' => (int) $overallStats->active_participants,
        'completed_participants' => (int) $overallStats->completed_participants,
        'exited_participants' => (int) $overallStats->exited_participants,
        'error_participants' => (int) $overallStats->error_participants,
        'completion_rate' => $overallStats->total_participants > 0 ? 
          round(($overallStats->completed_participants / $overallStats->total_participants) * 100, 2) : 0
      ]
    ];
  }

}