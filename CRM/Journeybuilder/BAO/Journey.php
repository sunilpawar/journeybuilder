<?php

class CRM_Journeybuilder_BAO_Journey extends CRM_Core_DAO {

  /**
   * Get journey analytics data
   */
  public static function getJourneyAnalytics($journeyId, $dateRange = NULL) {
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
  
  /**
   * Get journey list with basic stats
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
   * Duplicate an existing journey
   */
  public static function duplicateJourney($journeyId, $newName = NULL) {
    // Get original journey
    $originalJourney = CRM_Core_DAO::executeQuery("
      SELECT * FROM civicrm_journey_campaigns WHERE id = %1
    ", [1 => [$journeyId, 'Positive']]);
    
    if (!$originalJourney->fetch()) {
      throw new Exception('Journey not found');
    }
    
    $newJourneyName = $newName ?: ($originalJourney->name . ' (Copy)');
    
    // Create new journey
    CRM_Core_DAO::executeQuery("
      INSERT INTO civicrm_journey_campaigns
      (name, description, configuration, status, created_date, created_id)
      VALUES (%1, %2, %3, 'draft', NOW(), %4)
    ", [
      1 => [$newJourneyName, 'String'],
      2 => [$originalJourney->description, 'String'],
      3 => [$originalJourney->configuration, 'String'],
      4 => [CRM_Core_Session::getLoggedInContactID(), 'Positive']
    ]);
    
    $newJourneyId = CRM_Core_DAO::singleValueQuery("SELECT LAST_INSERT_ID()");
    
    // Copy steps
    $stepMapping = [];
    $stepsDao = CRM_Core_DAO::executeQuery("
      SELECT * FROM civicrm_journey_steps WHERE journey_id = %1 ORDER BY sort_order
    ", [1 => [$journeyId, 'Positive']]);
    
    while ($stepsDao->fetch()) {
      CRM_Core_DAO::executeQuery("
        INSERT INTO civicrm_journey_steps
        (journey_id, step_type, name, configuration, position_x, position_y, sort_order)
        VALUES (%1, %2, %3, %4, %5, %6, %7)
      ", [
        1 => [$newJourneyId, 'Positive'],
        2 => [$stepsDao->step_type, 'String'],
        3 => [$stepsDao->name, 'String'],
        4 => [$stepsDao->configuration, 'String'],
        5 => [$stepsDao->position_x, 'Float'],
        6 => [$stepsDao->position_y, 'Float'],
        7 => [$stepsDao->sort_order, 'Positive']
      ]);
      
      $newStepId = CRM_Core_DAO::singleValueQuery("SELECT LAST_INSERT_ID()");
      $stepMapping[$stepsDao->id] = $newStepId;
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
        CRM_Core_DAO::executeQuery("
          INSERT INTO civicrm_journey_conditions
          (step_id, condition_type, field_name, operator, value, logic_operator, sort_order)
          VALUES (%1, %2, %3, %4, %5, %6, %7)
        ", [
          1 => [$stepMapping[$conditionsDao->step_id], 'Positive'],
          2 => [$conditionsDao->condition_type, 'String'],
          3 => [$conditionsDao->field_name, 'String'],
          4 => [$conditionsDao->operator, 'String'],
          5 => [$conditionsDao->value, 'String'],
          6 => [$conditionsDao->logic_operator, 'String'],
          7 => [$conditionsDao->sort_order, 'Positive']
        ]);
      }
    }
    
    return $newJourneyId;
  }
  
  /**
   * Archive journey and all its data
   */
  public static function archiveJourney($journeyId) {
    // Update journey status
    CRM_Core_DAO::executeQuery("
      UPDATE civicrm_journey_campaigns
      SET status = 'archived', modified_date = NOW()
      WHERE id = %1
    ", [1 => [$journeyId, 'Positive']]);
    
    // Exit all active participants
    CRM_Core_DAO::executeQuery("
      UPDATE civicrm_journey_participants
      SET status = 'exited', completed_date = NOW()
      WHERE journey_id = %1 AND status IN ('active', 'paused')
    ", [1 => [$journeyId, 'Positive']]);
    
    return TRUE;
  }
  
  /**
   * Get participant details for a journey
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
      $limit = "LIMIT " . (int) $params['limit'];
      if (!empty($params['offset'])) {
        $limit = "LIMIT " . (int) $params['offset'] . ", " . (int) $params['limit'];
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
}