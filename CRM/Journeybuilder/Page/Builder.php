<?php

use CRM_Journeybuilder_ExtensionUtil as E;

class CRM_Journeybuilder_Page_Builder extends CRM_Core_Page {

  public function run() {
    // Add CSS and JS resources
    CRM_Core_Resources::singleton()
      ->addStyleFile('com.skvare.journeybuilder', 'css/journey-builder.css')
      ->addScriptFile('com.skvare.journeybuilder', 'js/journey-builder.js')
      ->addScriptFile('com.skvare.journeybuilder', 'js/d3.min.js')
      ->addScriptFile('com.skvare.journeybuilder', 'js/fabric.min.js');

    // Get journey ID if editing existing journey
    $journeyId = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE);

    if ($journeyId) {
      $journey = $this->getJourneyData($journeyId);
      $this->assign('journey', $journey);
      $this->assign('journeyId', $journeyId);
    }

    // Load journey templates
    $templates = $this->getJourneyTemplates();
    $this->assign('templates', $templates);

    // Load available email templates from Mosaico
    $emailTemplates = $this->getMosaicoTemplates();
    $this->assign('emailTemplates', $emailTemplates);

    parent::run();
  }

  private function getJourneyData($journeyId) {
    $dao = CRM_Core_DAO::executeQuery("
      SELECT jc.*,
             GROUP_CONCAT(js.id) as step_ids,
             GROUP_CONCAT(js.step_type) as step_types,
             GROUP_CONCAT(js.configuration) as step_configs
      FROM civicrm_journey_campaigns jc
      LEFT JOIN civicrm_journey_steps js ON jc.id = js.journey_id
      WHERE jc.id = %1
      GROUP BY jc.id
    ", [1 => [$journeyId, 'Positive']]);

    if ($dao->fetch()) {
      return [
        'id' => $dao->id,
        'name' => $dao->name,
        'description' => $dao->description,
        'status' => $dao->status,
        'configuration' => json_decode($dao->configuration, TRUE),
        'steps' => $this->getJourneySteps($journeyId)
      ];
    }
    return NULL;
  }

  private function getJourneySteps($journeyId) {
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
        'configuration' => json_decode($dao->configuration, TRUE),
        'position_x' => $dao->position_x,
        'position_y' => $dao->position_y,
        'sort_order' => $dao->sort_order
      ];
    }
    return $steps;
  }

  private function getJourneyTemplates() {
    return [
      [
        'id' => 'welcome_series',
        'name' => 'Welcome Series',
        'description' => 'Multi-step welcome journey for new contacts',
        'category' => 'onboarding'
      ],
      [
        'id' => 'donor_nurture',
        'name' => 'Donor Nurture Campaign',
        'description' => 'Engage and retain donors with targeted content',
        'category' => 'fundraising'
      ],
      [
        'id' => 'event_promotion',
        'name' => 'Event Promotion',
        'description' => 'Drive event registration and attendance',
        'category' => 'events'
      ],
      [
        'id' => 'member_renewal',
        'name' => 'Membership Renewal',
        'description' => 'Automated membership renewal campaign',
        'category' => 'membership'
      ]
    ];
  }

  private function getMosaicoTemplates() {
    $templates = [];
    try {
      $result = civicrm_api3('MosaicoTemplate', 'get', [
        'sequential' => 1,
        'options' => ['limit' => 100]
      ]);
      foreach ($result['values'] as $template) {
        $templates[] = [
          'id' => $template['id'],
          'title' => $template['title'],
          'thumbnail' => $template['thumbnail'] ?? '',
          'category' => $template['category'] ?? 'general'
        ];
      }
    } catch (CiviCRM_API3_Exception $e) {
      // Handle error - Mosaico might not be installed
      CRM_Core_Session::setStatus('Mosaico templates could not be loaded', 'Warning', 'alert');
    }
    return $templates;
  }
}
