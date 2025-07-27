<?php

use CRM_Journeybuilder_ExtensionUtil as E;

class CRM_Journeybuilder_Page_JourneyList extends CRM_Core_Page {

  public function run() {
    // Add CSS and JS resources
    CRM_Core_Resources::singleton()
      ->addStyleFile('com.skvare.journeybuilder', 'css/journey-list.css')
      ->addScriptFile('com.skvare.journeybuilder', 'js/journey-list.js');

    // Handle actions
    $action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE);
    $journeyId = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE);

    if ($action && $journeyId) {
      $this->handleAction($action, $journeyId);
      return;
    }

    // Get filter parameters
    $status = CRM_Utils_Request::retrieve('status', 'String', $this, FALSE);
    $search = CRM_Utils_Request::retrieve('search', 'String', $this, FALSE);

    // Build filter parameters
    $filters = [];
    if ($status) {
      $filters['status'] = $status;
    }

    // Get journeys list
    $journeys = CRM_Journeybuilder_BAO_Journey::getJourneyList($filters);

    // Filter by search term if provided
    if ($search) {
      $journeys = array_filter($journeys, function($journey) use ($search) {
        return stripos($journey['name'], $search) !== FALSE || 
               stripos($journey['description'], $search) !== FALSE;
      });
    }

    // Get summary statistics
    $stats = $this->getJourneyStats();

    $this->assign('journeys', $journeys);
    $this->assign('stats', $stats);
    $this->assign('currentStatus', $status);
    $this->assign('currentSearch', $search);

    // Set page title
    CRM_Utils_System::setTitle(E::ts('Journey Builder - Journey List'));

    parent::run();
  }

  private function handleAction($action, $journeyId) {
    try {
      switch ($action) {
        case 'activate':
          $result = CRM_Journeybuilder_API_Journey::activate($journeyId);
          if (!empty($result['error'])) {
            CRM_Core_Session::setStatus(
              'Validation errors: ' . implode(', ', $result['error']), 
              'Activation Failed', 
              'error'
            );
          } else {
            CRM_Core_Session::setStatus('Journey activated successfully', 'Success', 'success');
          }
          break;

        case 'pause':
          CRM_Journeybuilder_API_Journey::pause($journeyId);
          CRM_Core_Session::setStatus('Journey paused successfully', 'Success', 'success');
          break;

        case 'archive':
          CRM_Journeybuilder_BAO_Journey::archiveJourney($journeyId);
          CRM_Core_Session::setStatus('Journey archived successfully', 'Success', 'success');
          break;

        case 'duplicate':
          $newJourneyId = CRM_Journeybuilder_BAO_Journey::duplicateJourney($journeyId);
          CRM_Core_Session::setStatus(
            'Journey duplicated successfully. New journey ID: ' . $newJourneyId, 
            'Success', 
            'success'
          );
          break;

        case 'delete':
          // This would implement soft delete
          CRM_Core_Session::setStatus('Journey deletion not implemented yet', 'Info', 'info');
          break;
      }
    } catch (Exception $e) {
      CRM_Core_Session::setStatus('Action failed: ' . $e->getMessage(), 'Error', 'error');
    }

    // Redirect back to list
    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/journey/list'));
  }

  private function getJourneyStats() {
    $stats = [];
    
    // Get total counts by status
    $dao = CRM_Core_DAO::executeQuery("
      SELECT 
        status,
        COUNT(*) as count,
        SUM(CASE WHEN created_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as recent_count
      FROM civicrm_journey_campaigns 
      GROUP BY status
    ");

    while ($dao->fetch()) {
      $stats[$dao->status] = [
        'count' => (int) $dao->count,
        'recent_count' => (int) $dao->recent_count
      ];
    }

    // Get total participants across all journeys
    $totalParticipants = CRM_Core_DAO::singleValueQuery("
      SELECT COUNT(DISTINCT contact_id) 
      FROM civicrm_journey_participants
    ");

    // Get active participants
    $activeParticipants = CRM_Core_DAO::singleValueQuery("
      SELECT COUNT(DISTINCT jp.contact_id) 
      FROM civicrm_journey_participants jp
      INNER JOIN civicrm_journey_campaigns jc ON jp.journey_id = jc.id
      WHERE jp.status = 'active' AND jc.status = 'active'
    ");

    $stats['overall'] = [
      'total_journeys' => array_sum(array_column($stats, 'count')),
      'total_participants' => (int) $totalParticipants,
      'active_participants' => (int) $activeParticipants
    ];

    return $stats;
  }
}