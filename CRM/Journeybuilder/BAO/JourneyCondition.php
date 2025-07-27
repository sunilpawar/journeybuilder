<?php

use CRM_Journeybuilder_ExtensionUtil as E;

/**
 * Business Access Object for Journey Condition entity.
 */
class CRM_Journeybuilder_BAO_JourneyCondition extends CRM_Journeybuilder_DAO_JourneyCondition {

  /**
   * Create a new JourneyCondition based on array-data
   */
  public static function create($params) {
    $className = 'CRM_Journeybuilder_DAO_JourneyCondition';
    $entityName = 'JourneyCondition';
    $hook = empty($params['id']) ? 'create' : 'edit';

    CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
    $instance = new $className();
    $instance->copyValues($params);
    $instance->save();
    CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

    return $instance;
  }

  /**
   * Get conditions for a step
   */
  public static function getStepConditions($stepId) {
    $conditions = [];
    $dao = CRM_Core_DAO::executeQuery("
      SELECT * FROM civicrm_journey_conditions
      WHERE step_id = %1
      ORDER BY sort_order
    ", [1 => [$stepId, 'Positive']]);

    while ($dao->fetch()) {
      $conditions[] = [
        'id' => $dao->id,
        'step_id' => $dao->step_id,
        'condition_type' => $dao->condition_type,
        'field_name' => $dao->field_name,
        'operator' => $dao->operator,
        'value' => $dao->value,
        'logic_operator' => $dao->logic_operator,
        'sort_order' => (int)$dao->sort_order,
      ];
    }

    return $conditions;
  }

  /**
   * Evaluate conditions for a contact
   */
  public static function evaluateConditions($stepId, $contactId) {
    $conditions = self::getStepConditions($stepId);

    if (empty($conditions)) {
      return TRUE; // No conditions = always true
    }

    $results = [];
    foreach ($conditions as $condition) {
      $results[] = [
        'result' => self::evaluateSingleCondition($condition, $contactId),
        'logic' => $condition['logic_operator'],
      ];
    }

    // Evaluate combined logic
    return self::evaluateLogicChain($results);
  }

  /**
   * Evaluate a single condition
   */
  private static function evaluateSingleCondition($condition, $contactId) {
    try {
      $fieldValue = self::getContactFieldValue($contactId, $condition);
      return self::compareValues($fieldValue, $condition['operator'], $condition['value']);
    }
    catch (Exception $e) {
      CRM_Core_Error::debug_log_message('Condition evaluation failed: ' . $e->getMessage());
      return FALSE;
    }
  }

  /**
   * Get contact field value based on condition type
   */
  private static function getContactFieldValue($contactId, $condition) {
    switch ($condition['condition_type']) {
      case 'contact_field':
        return self::getContactField($contactId, $condition['field_name']);

      case 'activity':
        return self::getActivityValue($contactId, $condition);

      case 'contribution':
        return self::getContributionValue($contactId, $condition);

      case 'membership':
        return self::getMembershipValue($contactId, $condition);

      case 'event':
        return self::getEventValue($contactId, $condition);

      case 'custom':
        return self::getCustomFieldValue($contactId, $condition['field_name']);

      default:
        return NULL;
    }
  }

  /**
   * Get contact field value
   */
  private static function getContactField($contactId, $fieldName) {
    $contact = civicrm_api3('Contact', 'get', [
      'id' => $contactId,
      'return' => [$fieldName],
    ]);

    return $contact['values'][$contactId][$fieldName] ?? NULL;
  }

  /**
   * Get activity-related value
   */
  private static function getActivityValue($contactId, $condition) {
    $fieldName = $condition['field_name'];

    // Handle activity count
    if ($fieldName === 'activity_count') {
      return CRM_Core_DAO::singleValueQuery("
        SELECT COUNT(*) FROM civicrm_activity_contact ac
        INNER JOIN civicrm_activity a ON ac.activity_id = a.id
        WHERE ac.contact_id = %1 AND a.is_deleted = 0
      ", [1 => [$contactId, 'Positive']]);
    }

    // Handle last activity date
    if ($fieldName === 'last_activity_date') {
      return CRM_Core_DAO::singleValueQuery("
        SELECT MAX(a.activity_date_time) FROM civicrm_activity_contact ac
        INNER JOIN civicrm_activity a ON ac.activity_id = a.id
        WHERE ac.contact_id = %1 AND a.is_deleted = 0
      ", [1 => [$contactId, 'Positive']]);
    }

    return NULL;
  }

  /**
   * Get contribution-related value
   */
  private static function getContributionValue($contactId, $condition) {
    $fieldName = $condition['field_name'];

    switch ($fieldName) {
      case 'total_amount':
        return CRM_Core_DAO::singleValueQuery("
          SELECT SUM(total_amount) FROM civicrm_contribution
          WHERE contact_id = %1 AND contribution_status_id = 1
        ", [1 => [$contactId, 'Positive']]);

      case 'contribution_count':
        return CRM_Core_DAO::singleValueQuery("
          SELECT COUNT(*) FROM civicrm_contribution
          WHERE contact_id = %1 AND contribution_status_id = 1
        ", [1 => [$contactId, 'Positive']]);

      case 'last_contribution_date':
        return CRM_Core_DAO::singleValueQuery("
          SELECT MAX(receive_date) FROM civicrm_contribution
          WHERE contact_id = %1 AND contribution_status_id = 1
        ", [1 => [$contactId, 'Positive']]);

      default:
        return NULL;
    }
  }

  /**
   * Get membership-related value
   */
  private static function getMembershipValue($contactId, $condition) {
    $fieldName = $condition['field_name'];

    switch ($fieldName) {
      case 'membership_status':
        return CRM_Core_DAO::singleValueQuery("
          SELECT ms.name FROM civicrm_membership m
          INNER JOIN civicrm_membership_status ms ON m.status_id = ms.id
          WHERE m.contact_id = %1 AND m.is_deleted = 0
          ORDER BY m.end_date DESC LIMIT 1
        ", [1 => [$contactId, 'Positive']]);

      case 'membership_type':
        return CRM_Core_DAO::singleValueQuery("
          SELECT mt.name FROM civicrm_membership m
          INNER JOIN civicrm_membership_type mt ON m.membership_type_id = mt.id
          WHERE m.contact_id = %1 AND m.is_deleted = 0
          ORDER BY m.end_date DESC LIMIT 1
        ", [1 => [$contactId, 'Positive']]);

      default:
        return NULL;
    }
  }

  /**
   * Get event-related value
   */
  private static function getEventValue($contactId, $condition) {
    $fieldName = $condition['field_name'];

    if ($fieldName === 'event_participation_count') {
      return CRM_Core_DAO::singleValueQuery("
        SELECT COUNT(*) FROM civicrm_participant
        WHERE contact_id = %1 AND is_deleted = 0
      ", [1 => [$contactId, 'Positive']]);
    }

    return NULL;
  }

  /**
   * Get custom field value
   */
  private static function getCustomFieldValue($contactId, $fieldName) {
    // This is a simplified implementation
    // In a real implementation, you'd need to handle custom field table lookups
    try {
      $result = civicrm_api3('Contact', 'get', [
        'id' => $contactId,
        'return' => [$fieldName],
      ]);

      return $result['values'][$contactId][$fieldName] ?? NULL;
    }
    catch (Exception $e) {
      return NULL;
    }
  }

  /**
   * Compare values based on operator
   */
  private static function compareValues($fieldValue, $operator, $compareValue) {
    switch ($operator) {
      case 'equals':
        return $fieldValue == $compareValue;

      case 'not_equals':
        return $fieldValue != $compareValue;

      case 'contains':
        return strpos($fieldValue, $compareValue) !== FALSE;

      case 'not_contains':
        return strpos($fieldValue, $compareValue) === FALSE;

      case 'greater_than':
        return $fieldValue > $compareValue;

      case 'less_than':
        return $fieldValue < $compareValue;

      case 'is_null':
        return empty($fieldValue);

      case 'is_not_null':
        return !empty($fieldValue);

      default:
        return FALSE;
    }
  }

  /**
   * Evaluate logic chain (AND/OR operations)
   */
  private static function evaluateLogicChain($results) {
    if (empty($results)) {
      return TRUE;
    }

    $finalResult = $results[0]['result'];

    for ($i = 1; $i < count($results); $i++) {
      $logic = $results[$i - 1]['logic'] ?? 'AND';
      $currentResult = $results[$i]['result'];

      if ($logic === 'OR') {
        $finalResult = $finalResult || $currentResult;
      }
      else { // AND
        $finalResult = $finalResult && $currentResult;
      }
    }

    return $finalResult;
  }

  /**
   * Save conditions for a step
   */
  public static function saveStepConditions($stepId, $conditions) {
    // Delete existing conditions
    CRM_Core_DAO::executeQuery("DELETE FROM civicrm_journey_conditions WHERE step_id = %1", [
      1 => [$stepId, 'Positive'],
    ]);

    // Insert new conditions
    foreach ($conditions as $index => $conditionData) {
      $condition = new CRM_Journeybuilder_DAO_JourneyCondition();
      $condition->step_id = $stepId;
      $condition->condition_type = $conditionData['condition_type'];
      $condition->field_name = $conditionData['field_name'];
      $condition->operator = $conditionData['operator'];
      $condition->value = $conditionData['value'];
      $condition->logic_operator = $conditionData['logic_operator'] ?? 'AND';
      $condition->sort_order = $index;
      $condition->save();
    }

    return TRUE;
  }

  /**
   * Get available fields for condition type
   */
  public static function getAvailableFields($conditionType) {
    switch ($conditionType) {
      case 'contact_field':
        return [
          'first_name' => E::ts('First Name'),
          'last_name' => E::ts('Last Name'),
          'email' => E::ts('Email'),
          'phone' => E::ts('Phone'),
          'contact_type' => E::ts('Contact Type'),
          'contact_sub_type' => E::ts('Contact Sub Type'),
          'gender_id' => E::ts('Gender'),
          'birth_date' => E::ts('Birth Date'),
          'city' => E::ts('City'),
          'state_province_id' => E::ts('State/Province'),
          'country_id' => E::ts('Country'),
        ];

      case 'activity':
        return [
          'activity_count' => E::ts('Activity Count'),
          'last_activity_date' => E::ts('Last Activity Date'),
        ];

      case 'contribution':
        return [
          'total_amount' => E::ts('Total Contribution Amount'),
          'contribution_count' => E::ts('Contribution Count'),
          'last_contribution_date' => E::ts('Last Contribution Date'),
        ];

      case 'membership':
        return [
          'membership_status' => E::ts('Membership Status'),
          'membership_type' => E::ts('Membership Type'),
        ];

      case 'event':
        return [
          'event_participation_count' => E::ts('Event Participation Count'),
        ];

      default:
        return [];
    }
  }

  /**
   * Get available operators
   */
  public static function getAvailableOperators() {
    return [
      'equals' => E::ts('Equals'),
      'not_equals' => E::ts('Not Equals'),
      'contains' => E::ts('Contains'),
      'not_contains' => E::ts('Does Not Contain'),
      'greater_than' => E::ts('Greater Than'),
      'less_than' => E::ts('Less Than'),
      'is_null' => E::ts('Is Empty'),
      'is_not_null' => E::ts('Is Not Empty'),
    ];
  }

  /**
   * Get available condition types
   */
  public static function conditionType() {
    return [
      'contact_field' => 'Contact Field',
      'activity' => 'Activity',
      'contribution' => 'Contribution',
      'membership' => 'Membership',
      'event' => 'Event',
      'custom' => 'Custom',
    ];
  }

  public static function getLogicOperators() {
    return ['AND' => E::ts('AND'), 'OR' => E::ts('OR')];
  }

}
