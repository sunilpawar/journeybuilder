<?php

/**
 * Database access object for the JourneyEmailTemplate entity.
 */
class CRM_Journeybuilder_DAO_JourneyEmailTemplate extends CRM_Core_DAO {

  public static $_tableName = 'civicrm_journey_email_templates';
  public static $_log = TRUE;

  public $id;
  public $step_id;
  public $mosaico_template_id;
  public $subject;
  public $html_content;
  public $text_content;
  public $personalization_rules;
  public $ab_test_config;

  public function __construct() {
    $this->__table = 'civicrm_journey_email_templates';
    parent::__construct();
  }

  public static function getEntityTitle($plural = FALSE) {
    return $plural ? E::ts('Journey Email Templates') : E::ts('Journey Email Template');
  }

  public static function getTableName() {
    return self::$_tableName;
  }

  public function getLog() {
    return self::$_log;
  }

}