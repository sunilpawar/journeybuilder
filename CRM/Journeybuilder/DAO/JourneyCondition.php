<?php

/**
 * Database access object for the JourneyCondition entity.
 */
class CRM_Journeybuilder_DAO_JourneyCondition extends CRM_Core_DAO {

  public static $_tableName = 'civicrm_journey_conditions';
  public static $_log = TRUE;

  public $id;
  public $step_id;
  public $condition_type;
  public $field_name;
  public $operator;
  public $value;
  public $logic_operator;
  public $sort_order;

  public function __construct() {
    $this->__table = 'civicrm_journey_conditions';
    parent::__construct();
  }

  public static function getEntityTitle($plural = FALSE) {
    return $plural ? E::ts('Journey Conditions') : E::ts('Journey Condition');
  }

  public static function getTableName() {
    return self::$_tableName;
  }

  public function getLog() {
    return self::$_log;
  }

}