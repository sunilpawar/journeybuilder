<?php

/**
 * Database access object for the JourneyParticipant entity.
 */
class CRM_Journeybuilder_DAO_JourneyParticipant extends CRM_Core_DAO {

  public static $_tableName = 'civicrm_journey_participants';
  public static $_log = TRUE;

  public $id;
  public $journey_id;
  public $contact_id;
  public $current_step_id;
  public $status;
  public $entered_date;
  public $completed_date;
  public $last_action_date;

  public function __construct() {
    $this->__table = 'civicrm_journey_participants';
    parent::__construct();
  }

  public static function getEntityTitle($plural = FALSE) {
    return $plural ? E::ts('Journey Participants') : E::ts('Journey Participant');
  }

  public static function &fields() {
    if (!isset(Civi::$statics[__CLASS__]['fields'])) {
      Civi::$statics[__CLASS__]['fields'] = [
        'id' => [
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => E::ts('Participant ID'),
          'required' => TRUE,
          'where' => 'civicrm_journey_participants.id',
          'table_name' => 'civicrm_journey_participants',
          'entity' => 'JourneyParticipant',
          'bao' => 'CRM_Journeybuilder_DAO_JourneyParticipant',
          'localizable' => 0,
          'readonly' => TRUE,
        ],
        'journey_id' => [
          'name' => 'journey_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => E::ts('Journey ID'),
          'required' => TRUE,
          'where' => 'civicrm_journey_participants.journey_id',
          'table_name' => 'civicrm_journey_participants',
          'entity' => 'JourneyParticipant',
          'bao' => 'CRM_Journeybuilder_DAO_JourneyParticipant',
          'localizable' => 0,
          'FKClassName' => 'CRM_Journeybuilder_DAO_JourneyCampaign',
        ],
        'contact_id' => [
          'name' => 'contact_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => E::ts('Contact ID'),
          'required' => TRUE,
          'where' => 'civicrm_journey_participants.contact_id',
          'table_name' => 'civicrm_journey_participants',
          'entity' => 'JourneyParticipant',
          'bao' => 'CRM_Journeybuilder_DAO_JourneyParticipant',
          'localizable' => 0,
          'FKClassName' => 'CRM_Contact_DAO_Contact',
        ],
        'current_step_id' => [
          'name' => 'current_step_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => E::ts('Current Step ID'),
          'where' => 'civicrm_journey_participants.current_step_id',
          'table_name' => 'civicrm_journey_participants',
          'entity' => 'JourneyParticipant',
          'bao' => 'CRM_Journeybuilder_DAO_JourneyParticipant',
          'localizable' => 0,
          'FKClassName' => 'CRM_Journeybuilder_DAO_JourneyStep',
        ],
        'status' => [
          'name' => 'status',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => E::ts('Status'),
          'maxlength' => 16,
          'where' => 'civicrm_journey_participants.status',
          'default' => 'active',
          'table_name' => 'civicrm_journey_participants',
          'entity' => 'JourneyParticipant',
          'bao' => 'CRM_Journeybuilder_DAO_JourneyParticipant',
          'localizable' => 0,
          'pseudoconstant' => [
            'callback' => 'CRM_Journeybuilder_BAO_JourneyParticipant::getStatusOptions',
          ],
        ],
        'entered_date' => [
          'name' => 'entered_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => E::ts('Entered Date'),
          'required' => TRUE,
          'where' => 'civicrm_journey_participants.entered_date',
          'table_name' => 'civicrm_journey_participants',
          'entity' => 'JourneyParticipant',
          'bao' => 'CRM_Journeybuilder_DAO_JourneyParticipant',
          'localizable' => 0,
        ],
        'completed_date' => [
          'name' => 'completed_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => E::ts('Completed Date'),
          'where' => 'civicrm_journey_participants.completed_date',
          'table_name' => 'civicrm_journey_participants',
          'entity' => 'JourneyParticipant',
          'bao' => 'CRM_Journeybuilder_DAO_JourneyParticipant',
          'localizable' => 0,
        ],
        'last_action_date' => [
          'name' => 'last_action_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => E::ts('Last Action Date'),
          'where' => 'civicrm_journey_participants.last_action_date',
          'table_name' => 'civicrm_journey_participants',
          'entity' => 'JourneyParticipant',
          'bao' => 'CRM_Journeybuilder_DAO_JourneyParticipant',
          'localizable' => 0,
        ],
      ];
    }
    return Civi::$statics[__CLASS__]['fields'];
  }

  public static function &fieldKeys() {
    if (!isset(Civi::$statics[__CLASS__]['fieldKeys'])) {
      Civi::$statics[__CLASS__]['fieldKeys'] = array_flip(CRM_Utils_Array::collect('name', self::fields()));
    }
    return Civi::$statics[__CLASS__]['fieldKeys'];
  }

  public static function getTableName() {
    return self::$_tableName;
  }

  public function getLog() {
    return self::$_log;
  }

}