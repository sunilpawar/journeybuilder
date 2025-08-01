<?php

/**
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 *
 * Generated from com.skvare.journeybuilder/xml/schema/CRM/Journeybuilder/JourneyParticipant.xml
 * DO NOT EDIT.  Generated by CRM_Core_CodeGen
 * (GenCodeChecksum:95f29edd98dac4c5898022dc8089dda3)
 */
use CRM_Journeybuilder_ExtensionUtil as E;

/**
 * Database access object for the JourneyParticipant entity.
 */
class CRM_Journeybuilder_DAO_JourneyParticipant extends CRM_Core_DAO {
  const EXT = E::LONG_NAME;
  const TABLE_ADDED = '';

  /**
   * Static instance to hold the table name.
   *
   * @var string
   */
  public static $_tableName = 'civicrm_journey_participants';

  /**
   * Should CiviCRM log any modifications to this table in the civicrm_log table.
   *
   * @var bool
   */
  public static $_log = FALSE;

  /**
   * Unique JourneyParticipant ID
   *
   * @var int|string|null
   *   (SQL type: int unsigned)
   *   Note that values will be retrieved from the database as a string.
   */
  public $id;

  /**
   * FK to journey
   *
   * @var int|string|null
   *   (SQL type: int unsigned)
   *   Note that values will be retrieved from the database as a string.
   */
  public $journey_id;

  /**
   * FK to Contact
   *
   * @var int|string|null
   *   (SQL type: int unsigned)
   *   Note that values will be retrieved from the database as a string.
   */
  public $contact_id;

  /**
   * FK to Steps
   *
   * @var int|string|null
   *   (SQL type: int unsigned)
   *   Note that values will be retrieved from the database as a string.
   */
  public $current_step_id;

  /**
   * Status Operator
   *
   * @var string|null
   *   (SQL type: varchar(20))
   *   Note that values will be retrieved from the database as a string.
   */
  public $status;

  /**
   * @var string|null
   *   (SQL type: datetime)
   *   Note that values will be retrieved from the database as a string.
   */
  public $entered_date;

  /**
   * @var string|null
   *   (SQL type: datetime)
   *   Note that values will be retrieved from the database as a string.
   */
  public $completed_date;

  /**
   * @var string|null
   *   (SQL type: datetime)
   *   Note that values will be retrieved from the database as a string.
   */
  public $last_action_date;

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->__table = 'civicrm_journey_participants';
    parent::__construct();
  }

  /**
   * Returns localized title of this entity.
   *
   * @param bool $plural
   *   Whether to return the plural version of the title.
   */
  public static function getEntityTitle($plural = FALSE) {
    return $plural ? E::ts('Journey Participants') : E::ts('Journey Participant');
  }

  /**
   * Returns all the column names of this table
   *
   * @return array
   */
  public static function &fields() {
    if (!isset(Civi::$statics[__CLASS__]['fields'])) {
      Civi::$statics[__CLASS__]['fields'] = [
        'id' => [
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => E::ts('ID'),
          'description' => E::ts('Unique JourneyParticipant ID'),
          'required' => TRUE,
          'usage' => [
            'import' => FALSE,
            'export' => FALSE,
            'duplicate_matching' => FALSE,
            'token' => FALSE,
          ],
          'where' => 'civicrm_journey_participants.id',
          'table_name' => 'civicrm_journey_participants',
          'entity' => 'JourneyParticipant',
          'bao' => 'CRM_Journeybuilder_DAO_JourneyParticipant',
          'localizable' => 0,
          'html' => [
            'type' => 'Number',
          ],
          'readonly' => TRUE,
          'add' => NULL,
        ],
        'journey_id' => [
          'name' => 'journey_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => E::ts('Journey'),
          'description' => E::ts('FK to journey'),
          'usage' => [
            'import' => FALSE,
            'export' => FALSE,
            'duplicate_matching' => FALSE,
            'token' => FALSE,
          ],
          'where' => 'civicrm_journey_participants.journey_id',
          'table_name' => 'civicrm_journey_participants',
          'entity' => 'JourneyParticipant',
          'bao' => 'CRM_Journeybuilder_DAO_JourneyParticipant',
          'localizable' => 0,
          'FKClassName' => 'CRM_Journeybuilder_DAO_JourneyCampaign',
          'add' => NULL,
        ],
        'contact_id' => [
          'name' => 'contact_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => E::ts('Contact'),
          'description' => E::ts('FK to Contact'),
          'usage' => [
            'import' => FALSE,
            'export' => FALSE,
            'duplicate_matching' => FALSE,
            'token' => FALSE,
          ],
          'where' => 'civicrm_journey_participants.contact_id',
          'table_name' => 'civicrm_journey_participants',
          'entity' => 'JourneyParticipant',
          'bao' => 'CRM_Journeybuilder_DAO_JourneyParticipant',
          'localizable' => 0,
          'FKClassName' => 'CRM_Contact_DAO_Contact',
          'add' => NULL,
        ],
        'current_step_id' => [
          'name' => 'current_step_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => E::ts('Current Step'),
          'description' => E::ts('FK to Steps'),
          'usage' => [
            'import' => FALSE,
            'export' => FALSE,
            'duplicate_matching' => FALSE,
            'token' => FALSE,
          ],
          'where' => 'civicrm_journey_participants.current_step_id',
          'table_name' => 'civicrm_journey_participants',
          'entity' => 'JourneyParticipant',
          'bao' => 'CRM_Journeybuilder_DAO_JourneyParticipant',
          'localizable' => 0,
          'FKClassName' => 'CRM_Journeybuilder_DAO_JourneyStep',
          'add' => NULL,
        ],
        'status' => [
          'name' => 'status',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => E::ts('Status'),
          'description' => E::ts('Status Operator'),
          'maxlength' => 20,
          'size' => CRM_Utils_Type::MEDIUM,
          'usage' => [
            'import' => FALSE,
            'export' => FALSE,
            'duplicate_matching' => FALSE,
            'token' => FALSE,
          ],
          'where' => 'civicrm_journey_participants.status',
          'default' => 'active',
          'table_name' => 'civicrm_journey_participants',
          'entity' => 'JourneyParticipant',
          'bao' => 'CRM_Journeybuilder_DAO_JourneyParticipant',
          'localizable' => 0,
          'html' => [
            'type' => 'Select',
          ],
          'pseudoconstant' => [
            'callback' => 'CRM_Journeybuilder_BAO_JourneyParticipant::getStatusOptions',
          ],
          'add' => '1.0',
        ],
        'entered_date' => [
          'name' => 'entered_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => E::ts('Entered Date'),
          'usage' => [
            'import' => TRUE,
            'export' => TRUE,
            'duplicate_matching' => TRUE,
            'token' => FALSE,
          ],
          'import' => TRUE,
          'where' => 'civicrm_journey_participants.entered_date',
          'headerPattern' => '/entered(.?date)?/i',
          'dataPattern' => '/^\d{4}-?\d{2}-?\d{2} ?(\d{2}:?\d{2}:?(\d{2})?)?$/',
          'export' => TRUE,
          'table_name' => 'civicrm_journey_participants',
          'entity' => 'JourneyParticipant',
          'bao' => 'CRM_Journeybuilder_DAO_JourneyParticipant',
          'localizable' => 0,
          'html' => [
            'type' => 'Select Date',
            'formatType' => 'activityDateTime',
          ],
          'add' => NULL,
        ],
        'completed_date' => [
          'name' => 'completed_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => E::ts('Completed Date'),
          'usage' => [
            'import' => TRUE,
            'export' => TRUE,
            'duplicate_matching' => TRUE,
            'token' => FALSE,
          ],
          'import' => TRUE,
          'where' => 'civicrm_journey_participants.completed_date',
          'headerPattern' => '/completed(.?date)?/i',
          'dataPattern' => '/^\d{4}-?\d{2}-?\d{2} ?(\d{2}:?\d{2}:?(\d{2})?)?$/',
          'export' => TRUE,
          'table_name' => 'civicrm_journey_participants',
          'entity' => 'JourneyParticipant',
          'bao' => 'CRM_Journeybuilder_DAO_JourneyParticipant',
          'localizable' => 0,
          'html' => [
            'type' => 'Select Date',
            'formatType' => 'activityDateTime',
          ],
          'add' => NULL,
        ],
        'last_action_date' => [
          'name' => 'last_action_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => E::ts('Last Action Date'),
          'usage' => [
            'import' => TRUE,
            'export' => TRUE,
            'duplicate_matching' => TRUE,
            'token' => FALSE,
          ],
          'import' => TRUE,
          'where' => 'civicrm_journey_participants.last_action_date',
          'headerPattern' => '/last_action(.?date)?/i',
          'dataPattern' => '/^\d{4}-?\d{2}-?\d{2} ?(\d{2}:?\d{2}:?(\d{2})?)?$/',
          'export' => TRUE,
          'table_name' => 'civicrm_journey_participants',
          'entity' => 'JourneyParticipant',
          'bao' => 'CRM_Journeybuilder_DAO_JourneyParticipant',
          'localizable' => 0,
          'html' => [
            'type' => 'Select Date',
            'formatType' => 'activityDateTime',
          ],
          'add' => NULL,
        ],
      ];
      CRM_Core_DAO_AllCoreTables::invoke(__CLASS__, 'fields_callback', Civi::$statics[__CLASS__]['fields']);
    }
    return Civi::$statics[__CLASS__]['fields'];
  }

  /**
   * Returns the list of fields that can be imported
   *
   * @param bool $prefix
   *
   * @return array
   */
  public static function &import($prefix = FALSE) {
    $r = CRM_Core_DAO_AllCoreTables::getImports(__CLASS__, 'journey_participants', $prefix, []);
    return $r;
  }

  /**
   * Returns the list of fields that can be exported
   *
   * @param bool $prefix
   *
   * @return array
   */
  public static function &export($prefix = FALSE) {
    $r = CRM_Core_DAO_AllCoreTables::getExports(__CLASS__, 'journey_participants', $prefix, []);
    return $r;
  }

  /**
   * Returns the list of indices
   *
   * @param bool $localize
   *
   * @return array
   */
  public static function indices($localize = TRUE) {
    $indices = [
      'idx_participant_status' => [
        'name' => 'idx_participant_status',
        'field' => [
          0 => 'status',
        ],
        'localizable' => FALSE,
        'sig' => 'civicrm_journey_participants::0::status',
      ],
      'unique_journey_contact' => [
        'name' => 'unique_journey_contact',
        'field' => [
          0 => 'journey_id',
          1 => 'contact_id',
        ],
        'localizable' => FALSE,
        'unique' => TRUE,
        'sig' => 'civicrm_journey_participants::1::journey_id::contact_id',
      ],
      'idx_journey_status_date' => [
        'name' => 'idx_journey_status_date',
        'field' => [
          0 => 'journey_id',
          1 => 'status',
          2 => 'last_action_date',
        ],
        'localizable' => FALSE,
        'sig' => 'civicrm_journey_participants::0::journey_id::status::last_action_date',
      ],
    ];
    return ($localize && !empty($indices)) ? CRM_Core_DAO_AllCoreTables::multilingualize(__CLASS__, $indices) : $indices;
  }

}
