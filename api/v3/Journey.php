<?php

/**
 * Journey.Save API specification
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 */
function _civicrm_api3_journey_save_spec(&$spec) {
  $spec['id'] = [
    'name' => 'id',
    'title' => 'Journey ID',
    'description' => 'Unique Journey ID',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 0,
  ];
  $spec['name'] = [
    'name' => 'name',
    'title' => 'Journey Name',
    'description' => 'Name of the Journey',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
  ];
  $spec['description'] = [
    'name' => 'description',
    'title' => 'Journey Description',
    'description' => 'Description of the Journey',
    'type' => CRM_Utils_Type::T_TEXT,
    'api.required' => 0,
  ];
  $spec['steps'] = [
    'name' => 'steps',
    'title' => 'Journey Steps',
    'description' => 'Array of journey steps',
    'type' => CRM_Utils_Type::T_TEXT,
    'api.required' => 0,
  ];
  $spec['configuration'] = [
    'name' => 'configuration',
    'title' => 'Journey Configuration',
    'description' => 'JSON configuration for the journey',
    'type' => CRM_Utils_Type::T_TEXT,
    'api.required' => 0,
  ];
}

/**
 * Journey.Save API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_journey_save($params) {
  try {
    $result = CRM_Journeybuilder_API_Journey::save($params);
    return civicrm_api3_create_success($result, $params, 'Journey', 'save');
  }
  catch (Exception $e) {
    throw new API_Exception($e->getMessage(), $e->getCode());
  }
}

/**
 * Journey.Activate API specification
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 */
function _civicrm_api3_journey_activate_spec(&$spec) {
  $spec['id'] = [
    'name' => 'id',
    'title' => 'Journey ID',
    'description' => 'Unique Journey ID',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
  ];
}

/**
 * Journey.Activate API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_journey_activate($params) {
  try {
    $result = CRM_Journeybuilder_API_Journey::activate($params['id']);
    return civicrm_api3_create_success($result, $params, 'Journey', 'activate');
  }
  catch (Exception $e) {
    throw new API_Exception($e->getMessage(), $e->getCode());
  }
}

/**
 * Journey.Pause API specification
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 */
function _civicrm_api3_journey_pause_spec(&$spec) {
  $spec['id'] = [
    'name' => 'id',
    'title' => 'Journey ID',
    'description' => 'Unique Journey ID',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
  ];
}

/**
 * Journey.Pause API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_journey_pause($params) {
  try {
    $result = CRM_Journeybuilder_API_Journey::pause($params['id']);
    return civicrm_api3_create_success($result, $params, 'Journey', 'pause');
  }
  catch (Exception $e) {
    throw new API_Exception($e->getMessage(), $e->getCode());
  }
}

/**
 * Journey.Test API specification
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 */
function _civicrm_api3_journey_test_spec(&$spec) {
  $spec['id'] = [
    'name' => 'id',
    'title' => 'Journey ID',
    'description' => 'Unique Journey ID',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
  ];
  $spec['contact_id'] = [
    'name' => 'contact_id',
    'title' => 'Contact ID',
    'description' => 'Contact ID to test with',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
  ];
  $spec['mode'] = [
    'name' => 'mode',
    'title' => 'Test Mode',
    'description' => 'Test mode: simulation or live',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'api.default' => 'simulation',
  ];
}

/**
 * Journey.Test API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_journey_test($params) {
  try {
    $result = CRM_Journeybuilder_API_Journey::test($params);
    return civicrm_api3_create_success($result, $params, 'Journey', 'test');
  }
  catch (Exception $e) {
    throw new API_Exception($e->getMessage(), $e->getCode());
  }
}

/**
 * Journey.Get API specification
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 */
function _civicrm_api3_journey_get_spec(&$spec) {
  $spec['id'] = [
    'name' => 'id',
    'title' => 'Journey ID',
    'description' => 'Unique Journey ID',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 0,
  ];
  $spec['status'] = [
    'name' => 'status',
    'title' => 'Journey Status',
    'description' => 'Journey Status',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
  ];
  $spec['created_id'] = [
    'name' => 'created_id',
    'title' => 'Created By',
    'description' => 'Contact ID who created the journey',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 0,
  ];
}

/**
 * Journey.Get API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_journey_get($params) {
  try {
    $result = CRM_Journeybuilder_BAO_Journey::getJourneyList($params);
    return civicrm_api3_create_success($result, $params, 'Journey', 'get');
  }
  catch (Exception $e) {
    throw new API_Exception($e->getMessage(), $e->getCode());
  }
}

/**
 * Journey.Analytics API specification
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 */
function _civicrm_api3_journey_analytics_spec(&$spec) {
  $spec['id'] = [
    'name' => 'id',
    'title' => 'Journey ID',
    'description' => 'Unique Journey ID',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
  ];
  $spec['date_range'] = [
    'name' => 'date_range',
    'title' => 'Date Range',
    'description' => 'Date range for analytics (start and end dates)',
    'type' => CRM_Utils_Type::T_TEXT,
    'api.required' => 0,
  ];
}

/**
 * Journey.Analytics API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_journey_analytics($params) {
  try {
    $dateRange = NULL;
    if (!empty($params['date_range'])) {
      $dateRange = json_decode($params['date_range'], TRUE);
    }

    $result = CRM_Journeybuilder_BAO_Journey::getJourneyAnalytics($params['id'], $dateRange);
    return civicrm_api3_create_success($result, $params, 'Journey', 'analytics');
  }
  catch (Exception $e) {
    throw new API_Exception($e->getMessage(), $e->getCode());
  }
}

/**
 * Journey.Duplicate API specification
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 */
function _civicrm_api3_journey_duplicate_spec(&$spec) {
  $spec['id'] = [
    'name' => 'id',
    'title' => 'Journey ID',
    'description' => 'Journey ID to duplicate',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
  ];
  $spec['new_name'] = [
    'name' => 'new_name',
    'title' => 'New Journey Name',
    'description' => 'Name for the duplicated journey',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
  ];
}

/**
 * Journey.Duplicate API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_journey_duplicate($params) {
  try {
    $newJourneyId = CRM_Journeybuilder_BAO_Journey::duplicateJourney(
      $params['id'],
      $params['new_name'] ?? NULL
    );

    $result = ['new_journey_id' => $newJourneyId];
    return civicrm_api3_create_success($result, $params, 'Journey', 'duplicate');
  }
  catch (Exception $e) {
    throw new API_Exception($e->getMessage(), $e->getCode());
  }
}
