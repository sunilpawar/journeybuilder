<?php
use CRM_Journeybuilder_ExtensionUtil as E;

/**
 * Journey.Process API specification
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 */
function _civicrm_api3_journey_process_spec(&$spec) {
  $spec['id'] = [
    'name' => 'id',
    'title' => 'Journey ID',
    'description' => 'Specific Journey ID to process (optional)',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 0,
  ];
}

/**
 * Journey.Process API - Processes journey steps for active participants
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_journey_process($params) {
  try {
    $journeyId = $params['id'] ?? NULL;
    CRM_Journeybuilder_API_Journey::processJourneySteps($journeyId);

    $result = [
      'message' => $journeyId ?
        "Journey {$journeyId} processed successfully" :
        "All active journeys processed successfully"
    ];

    return civicrm_api3_create_success($result, $params, 'Journey', 'process');
  }
  catch (Exception $e) {
    throw new API_Exception($e->getMessage(), $e->getCode());
  }
}
