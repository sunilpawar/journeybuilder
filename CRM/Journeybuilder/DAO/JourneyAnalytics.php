<?php

/**
 * Database access object for the JourneyAnalytics entity.
 */
class CRM_Journeybuilder_DAO_JourneyAnalytics extends CRM_Core_DAO {

  public static $_tableName = 'civicrm_journey_analytics';
  public static $_log = TRUE;

  public $id;
  public $journey_id;
  public $step_id;
  public $contact_id;
  public $event_type;
  public $event_data;
  public $event_date;

  public function __construct() {
    $this->__table = 'civicrm_journey_analytics';
    parent::__construct();
  }

  public static function getEntityTitle($plural = FALSE) {
    return $plural ? E::ts('Journey Analytics') : E::ts('Journey Analytics');
  }

  public static function getTableName() {
    return self::$_tableName;
  }

  public function getLog() {
    return self::$_log;
  }

}