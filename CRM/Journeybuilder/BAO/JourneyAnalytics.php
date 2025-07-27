<?php

use CRM_Journeybuilder_ExtensionUtil as E;

/**
 * Business Access Object for Journey Analytics entity.
 */
class CRM_Journeybuilder_BAO_JourneyAnalytics extends CRM_Journeybuilder_DAO_JourneyAnalytics {

  /**
   * Create a new JourneyAnalytics based on array-data
   */
  public static function create($params) {
    $className = 'CRM_Journeybuilder_DAO_JourneyAnalytics';
    $entityName = 'JourneyAnalytics';
    $hook = empty($params['id']) ? 'create' : 'edit';

    // Set defaults
    if (empty($params['id'])) {
      $params['event_date'] = date('YmdHis');
    }

    CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
    $instance = new $className();
    $instance->copyValues($params);
    $instance->save();
    CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

    return $instance;
  }

  /**
   * Log an analytics event
   */
  public static function logEvent($journeyId, $stepId, $contactId, $eventType, $eventData = []) {
    $analytics = new CRM_Journeybuilder_DAO_JourneyAnalytics();
    $analytics->journey_id = $journeyId;
    $analytics->step_id = $stepId;
    $analytics->contact_id = $contactId;
    $analytics->event_type = $eventType;
    $analytics->event_data = json_encode($eventData);
    $analytics->event_date = date('YmdHis');
    $analytics->save();

    return $analytics->id;
  }

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
        COUNT(CASE WHEN ja.event_type = 'email_clicked' THEN 1 END) as emails_clicked,
        COUNT(CASE WHEN ja.event_type = 'bounced' THEN 1 END) as emails_bounced
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
        'emails_bounced' => (int) $dao->emails_bounced,
        'open_rate' => $dao->emails_sent > 0 ? round(($dao->emails_opened / $dao->emails_sent) * 100, 2) : 0,
        'click_rate' => $dao->emails_opened > 0 ? round(($dao->emails_clicked / $dao->emails_opened) * 100, 2) : 0,
        'bounce_rate' => $dao->emails_sent > 0 ? round(($dao->emails_bounced / $dao->emails_sent) * 100, 2) : 0
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
   * Get email performance metrics
   */
  public static function getEmailMetrics($journeyId, $stepId = NULL, $dateRange = NULL) {
    $whereClause = "WHERE journey_id = %1";
    $params = [1 => [$journeyId, 'Positive']];
    $paramIndex = 2;

    if ($stepId) {
      $whereClause .= " AND step_id = %{$paramIndex}";
      $params[$paramIndex] = [$stepId, 'Positive'];
      $paramIndex++;
    }

    if ($dateRange) {
      $whereClause .= " AND event_date BETWEEN %{$paramIndex} AND %" . ($paramIndex + 1);
      $params[$paramIndex] = [$dateRange['start'], 'String'];
      $params[$paramIndex + 1] = [$dateRange['end'], 'String'];
    }

    $dao = CRM_Core_DAO::executeQuery("
      SELECT
        SUM(CASE WHEN event_type = 'email_sent' THEN 1 ELSE 0 END) as emails_sent,
        SUM(CASE WHEN event_type = 'email_opened' THEN 1 ELSE 0 END) as emails_opened,
        SUM(CASE WHEN event_type = 'email_clicked' THEN 1 ELSE 0 END) as emails_clicked,
        SUM(CASE WHEN event_type = 'bounced' THEN 1 ELSE 0 END) as emails_bounced,
        SUM(CASE WHEN event_type = 'unsubscribed' THEN 1 ELSE 0 END) as unsubscribes,
        COUNT(DISTINCT contact_id) as unique_recipients
      FROM civicrm_journey_analytics
      {$whereClause}
      AND event_type IN ('email_sent', 'email_opened', 'email_clicked', 'bounced', 'unsubscribed')
    ", $params);

    if ($dao->fetch()) {
      $sent = $dao->emails_sent ?: 1; // Prevent division by zero
      return [
        'sent' => (int) $dao->emails_sent,
        'opened' => (int) $dao->emails_opened,
        'clicked' => (int) $dao->emails_clicked,
        'bounced' => (int) $dao->emails_bounced,
        'unsubscribed' => (int) $dao->unsubscribes,
        'unique_recipients' => (int) $dao->unique_recipients,
        'open_rate' => round(($dao->emails_opened / $sent) * 100, 2),
        'click_rate' => round(($dao->emails_clicked / $sent) * 100, 2),
        'bounce_rate' => round(($dao->emails_bounced / $sent) * 100, 2),
        'unsubscribe_rate' => round(($dao->unsubscribes / $sent) * 100, 2)
      ];
    }

    return [
      'sent' => 0, 'opened' => 0, 'clicked' => 0, 'bounced' => 0, 'unsubscribed' => 0,
      'unique_recipients' => 0, 'open_rate' => 0, 'click_rate' => 0, 'bounce_rate' => 0, 'unsubscribe_rate' => 0
    ];
  }

  /**
   * Get timeline data for charts
   */
  public static function getTimelineData($journeyId, $days = 30) {
    $dao = CRM_Core_DAO::executeQuery("
      SELECT
        DATE(event_date) as date,
        event_type,
        COUNT(*) as count
      FROM civicrm_journey_analytics
      WHERE journey_id = %1
        AND event_date >= DATE_SUB(NOW(), INTERVAL %2 DAY)
      GROUP BY DATE(event_date), event_type
      ORDER BY date ASC
    ", [
      1 => [$journeyId, 'Positive'],
      2 => [$days, 'Integer']
    ]);

    $timeline = [];
    while ($dao->fetch()) {
      $timeline[] = [
        'date' => $dao->date,
        'event_type' => $dao->event_type,
        'count' => (int) $dao->count
      ];
    }

    return $timeline;
  }

  /**
   * Get conversion funnel data
   */
  public static function getConversionFunnel($journeyId) {
    $steps = [];
    $dao = CRM_Core_DAO::executeQuery("
      SELECT 
        js.id,
        js.name,
        js.step_type,
        js.sort_order,
        COUNT(DISTINCT jp.contact_id) as participants_at_step,
        COUNT(DISTINCT CASE WHEN ja.event_type = 'completed' THEN ja.contact_id END) as completed_step
      FROM civicrm_journey_steps js
      LEFT JOIN civicrm_journey_participants jp ON js.id = jp.current_step_id OR 
        (jp.journey_id = js.journey_id AND EXISTS(
          SELECT 1 FROM civicrm_journey_analytics ja2 
          WHERE ja2.journey_id = jp.journey_id AND ja2.contact_id = jp.contact_id AND ja2.step_id = js.id
        ))
      LEFT JOIN civicrm_journey_analytics ja ON js.id = ja.step_id AND jp.contact_id = ja.contact_id
      WHERE js.journey_id = %1
      GROUP BY js.id
      ORDER BY js.sort_order
    ", [1 => [$journeyId, 'Positive']]);

    while ($dao->fetch()) {
      $steps[] = [
        'step_id' => $dao->id,
        'step_name' => $dao->name,
        'step_type' => $dao->step_type,
        'sort_order' => (int) $dao->sort_order,
        'participants' => (int) $dao->participants_at_step,
        'completed' => (int) $dao->completed_step,
        'completion_rate' => $dao->participants_at_step > 0 ? 
          round(($dao->completed_step / $dao->participants_at_step) * 100, 2) : 0
      ];
    }

    return $steps;
  }

  /**
   * Get participant engagement score
   */
  public static function getEngagementScore($journeyId, $contactId) {
    $score = 0;
    $dao = CRM_Core_DAO::executeQuery("
      SELECT event_type, COUNT(*) as count
      FROM civicrm_journey_analytics
      WHERE journey_id = %1 AND contact_id = %2
      GROUP BY event_type
    ", [
      1 => [$journeyId, 'Positive'],
      2 => [$contactId, 'Positive']
    ]);

    $weights = [
      'entered' => 1,
      'email_opened' => 3,
      'email_clicked' => 5,
      'completed' => 10,
      'converted' => 15,
      'bounced' => -2,
      'unsubscribed' => -5
    ];

    while ($dao->fetch()) {
      $weight = $weights[$dao->event_type] ?? 0;
      $score += $weight * $dao->count;
    }

    return max(0, $score); // Don't allow negative scores
  }

  /**
   * Get top performing steps
   */
  public static function getTopPerformingSteps($journeyId, $limit = 5) {
    $dao = CRM_Core_DAO::executeQuery("
      SELECT 
        js.id,
        js.name,
        js.step_type,
        COUNT(CASE WHEN ja.event_type = 'entered' THEN 1 END) as entered,
        COUNT(CASE WHEN ja.event_type = 'completed' THEN 1 END) as completed,
        COUNT(CASE WHEN ja.event_type = 'email_opened' THEN 1 END) as opened,
        COUNT(CASE WHEN ja.event_type = 'email_clicked' THEN 1 END) as clicked,
        (COUNT(CASE WHEN ja.event_type = 'completed' THEN 1 END) / 
         NULLIF(COUNT(CASE WHEN ja.event_type = 'entered' THEN 1 END), 0)) * 100 as performance_score
      FROM civicrm_journey_steps js
      LEFT JOIN civicrm_journey_analytics ja ON js.id = ja.step_id
      WHERE js.journey_id = %1
      GROUP BY js.id
      HAVING entered > 0
      ORDER BY performance_score DESC
      LIMIT %2
    ", [
      1 => [$journeyId, 'Positive'],
      2 => [$limit, 'Integer']
    ]);

    $steps = [];
    while ($dao->fetch()) {
      $steps[] = [
        'step_id' => $dao->id,
        'step_name' => $dao->name,
        'step_type' => $dao->step_type,
        'entered' => (int) $dao->entered,
        'completed' => (int) $dao->completed,
        'opened' => (int) $dao->opened,
        'clicked' => (int) $dao->clicked,
        'performance_score' => round($dao->performance_score, 2)
      ];
    }

    return $steps;
  }

  /**
   * Clean old analytics data
   */
  public static function cleanOldData($daysToKeep = 365) {
    return CRM_Core_DAO::executeQuery("
      DELETE FROM civicrm_journey_analytics
      WHERE event_date < DATE_SUB(NOW(), INTERVAL %1 DAY)
    ", [1 => [$daysToKeep, 'Integer']]);
  }

}