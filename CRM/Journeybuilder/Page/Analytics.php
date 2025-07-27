<?php
use CRM_Journeybuilder_ExtensionUtil as E;

class CRM_Journeybuilder_Page_Analytics extends CRM_Core_Page {

  public function run() {
    // Add required resources
    CRM_Core_Resources::singleton()
      ->addStyleFile('com.skvare.journeybuilder', 'css/analytics.css')
      ->addScriptFile('com.skvare.journeybuilder', 'js/analytics.js')
      ->addScriptFile('com.skvare.journeybuilder', 'js/chart.min.js');

    // Get journey ID if viewing specific journey
    $journeyId = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE);

    if ($journeyId) {
      $this->loadJourneyAnalytics($journeyId);
    }
    else {
      $this->loadOverviewAnalytics();
    }

    parent::run();
  }

  private function loadJourneyAnalytics($journeyId) {
    // Get journey details
    $journey = $this->getJourneyDetails($journeyId);
    $this->assign('journey', $journey);
    $this->assign('journeyId', $journeyId);

    // Get analytics data
    $analytics = $this->getJourneyAnalyticsData($journeyId);
    $this->assign('analytics', $analytics);

    // Get step performance
    $stepPerformance = $this->getStepPerformance($journeyId);
    $this->assign('stepPerformance', $stepPerformance);

    $this->assign('viewType', 'journey');
  }

  private function loadOverviewAnalytics() {
    // Get all journeys summary
    $journeys = $this->getAllJourneysSummary();
    $this->assign('journeys', $journeys);

    // Get overall metrics
    $overallMetrics = $this->getOverallMetrics();
    $this->assign('overallMetrics', $overallMetrics);

    $this->assign('viewType', 'overview');
  }

  private function getJourneyDetails($journeyId) {
    $dao = CRM_Core_DAO::executeQuery("
      SELECT jc.*,
             COUNT(DISTINCT jp.contact_id) as total_participants,
             COUNT(DISTINCT CASE WHEN jp.status = 'active' THEN jp.contact_id END) as active_participants,
             COUNT(DISTINCT CASE WHEN jp.status = 'completed' THEN jp.contact_id END) as completed_participants
      FROM civicrm_journey_campaigns jc
      LEFT JOIN civicrm_journey_participants jp ON jc.id = jp.journey_id
      WHERE jc.id = %1
      GROUP BY jc.id
    ", [1 => [$journeyId, 'Positive']]);

    if ($dao->fetch()) {
      return [
        'id' => $dao->id,
        'name' => $dao->name,
        'description' => $dao->description,
        'status' => $dao->status,
        'created_date' => $dao->created_date,
        'activated_date' => $dao->activated_date,
        'total_participants' => $dao->total_participants,
        'active_participants' => $dao->active_participants,
        'completed_participants' => $dao->completed_participants
      ];
    }
    return NULL;
  }

  private function getJourneyAnalyticsData($journeyId) {
    // Get email performance
    $emailMetrics = $this->getEmailMetrics($journeyId);

    // Get conversion data
    $conversions = $this->getConversionData($journeyId);

    // Get timeline data
    $timeline = $this->getTimelineData($journeyId);

    return [
      'email_metrics' => $emailMetrics,
      'conversions' => $conversions,
      'timeline' => $timeline
    ];
  }

  private function getEmailMetrics($journeyId) {
    $dao = CRM_Core_DAO::executeQuery("
      SELECT
        SUM(CASE WHEN event_type = 'email_sent' THEN 1 ELSE 0 END) as emails_sent,
        SUM(CASE WHEN event_type = 'email_opened' THEN 1 ELSE 0 END) as emails_opened,
        SUM(CASE WHEN event_type = 'email_clicked' THEN 1 ELSE 0 END) as emails_clicked,
        SUM(CASE WHEN event_type = 'bounced' THEN 1 ELSE 0 END) as emails_bounced,
        SUM(CASE WHEN event_type = 'unsubscribed' THEN 1 ELSE 0 END) as unsubscribes
      FROM civicrm_journey_analytics
      WHERE journey_id = %1
    ", [1 => [$journeyId, 'Positive']]);

    if ($dao->fetch()) {
      $sent = $dao->emails_sent ?: 1; // Prevent division by zero
      return [
        'sent' => $dao->emails_sent,
        'opened' => $dao->emails_opened,
        'clicked' => $dao->emails_clicked,
        'bounced' => $dao->emails_bounced,
        'unsubscribed' => $dao->unsubscribes,
        'open_rate' => round(($dao->emails_opened / $sent) * 100, 2),
        'click_rate' => round(($dao->emails_clicked / $sent) * 100, 2),
        'bounce_rate' => round(($dao->emails_bounced / $sent) * 100, 2)
      ];
    }

    return [
      'sent' => 0, 'opened' => 0, 'clicked' => 0, 'bounced' => 0, 'unsubscribed' => 0,
      'open_rate' => 0, 'click_rate' => 0, 'bounce_rate' => 0
    ];
  }

  private function getConversionData($journeyId) {
    // This would track conversions based on journey goals
    return [
      'total_conversions' => 0,
      'conversion_rate' => 0,
      'conversion_value' => 0
    ];
  }

  private function getTimelineData($journeyId) {
    $dao = CRM_Core_DAO::executeQuery("
      SELECT
        DATE(event_date) as date,
        event_type,
        COUNT(*) as count
      FROM civicrm_journey_analytics
      WHERE journey_id = %1
        AND event_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
      GROUP BY DATE(event_date), event_type
      ORDER BY date ASC
    ", [1 => [$journeyId, 'Positive']]);

    $timeline = [];
    while ($dao->fetch()) {
      $timeline[] = [
        'date' => $dao->date,
        'event_type' => $dao->event_type,
        'count' => $dao->count
      ];
    }

    return $timeline;
  }

  private function getStepPerformance($journeyId) {
    $dao = CRM_Core_DAO::executeQuery("
      SELECT
        js.id,
        js.name as step_name,
        js.step_type,
        COUNT(DISTINCT jp.contact_id) as participants,
        AVG(TIMESTAMPDIFF(HOUR, jp.entered_date, jp.last_action_date)) as avg_time_hours
      FROM civicrm_journey_steps js
      LEFT JOIN civicrm_journey_participants jp ON js.id = jp.current_step_id
      WHERE js.journey_id = %1
      GROUP BY js.id
      ORDER BY js.sort_order
    ", [1 => [$journeyId, 'Positive']]);

    $steps = [];
    while ($dao->fetch()) {
      $steps[] = [
        'id' => $dao->id,
        'name' => $dao->step_name,
        'type' => $dao->step_type,
        'participants' => $dao->participants,
        'avg_time_hours' => round($dao->avg_time_hours, 2)
      ];
    }

    return $steps;
  }

  private function getAllJourneysSummary() {
    $dao = CRM_Core_DAO::executeQuery("
      SELECT
        jc.id,
        jc.name,
        jc.status,
        jc.activated_date,
        COUNT(DISTINCT jp.contact_id) as total_participants,
        COUNT(DISTINCT CASE WHEN jp.status = 'completed' THEN jp.contact_id END) as completed_participants
      FROM civicrm_journey_campaigns jc
      LEFT JOIN civicrm_journey_participants jp ON jc.id = jp.journey_id
      GROUP BY jc.id
      ORDER BY jc.created_date DESC
    ");

    $journeys = [];
    while ($dao->fetch()) {
      $completion_rate = $dao->total_participants > 0 ?
        round(($dao->completed_participants / $dao->total_participants) * 100, 2) : 0;

      $journeys[] = [
        'id' => $dao->id,
        'name' => $dao->name,
        'status' => $dao->status,
        'activated_date' => $dao->activated_date,
        'total_participants' => $dao->total_participants,
        'completed_participants' => $dao->completed_participants,
        'completion_rate' => $completion_rate
      ];
    }

    return $journeys;
  }

  private function getOverallMetrics() {
    $dao = CRM_Core_DAO::executeQuery("
      SELECT
        COUNT(DISTINCT jc.id) as total_journeys,
        COUNT(DISTINCT CASE WHEN jc.status = 'active' THEN jc.id END) as active_journeys,
        COUNT(DISTINCT jp.contact_id) as total_participants,
        SUM(CASE WHEN ja.event_type = 'email_sent' THEN 1 ELSE 0 END) as total_emails_sent
      FROM civicrm_journey_campaigns jc
      LEFT JOIN civicrm_journey_participants jp ON jc.id = jp.journey_id
      LEFT JOIN civicrm_journey_analytics ja ON jc.id = ja.journey_id
    ");

    if ($dao->fetch()) {
      return [
        'total_journeys' => $dao->total_journeys,
        'active_journeys' => $dao->active_journeys,
        'total_participants' => $dao->total_participants,
        'total_emails_sent' => $dao->total_emails_sent
      ];
    }

    return [
      'total_journeys' => 0,
      'active_journeys' => 0,
      'total_participants' => 0,
      'total_emails_sent' => 0
    ];
  }
}
