<?php

/**
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Database access object for the JourneyCampaign entity.
 */
class CRM_Journeybuilder_DAO_JourneyCampaign extends CRM_Core_DAO {
  const EXT = E::LONG_NAME;
  const TABLE_ADDED = '';

  /**
   * Static instance to hold the table name.
   *
   * @var string
   */
  public static $_tableName = 'civicrm_journey_campaigns';

  /**
   * Icon associated with this entity.
   *
   * @var string
   */
  public static $_icon = 'fa-map-o';

  /**
   * Should CiviCRM log any modifications to this table in the civicrm_log table.
   *
   * @var bool
   */
  public static $_log = TRUE;

  /**
   * Unique Journey Campaign ID
   *
   * @var int
   */
  public $id;

  /**
   * Name of the Journey Campaign
   *
   * @var string
   */
  public $name;

  /**
   * Description of the Journey Campaign
   *
   * @var text
   */
  public $description;

  /**
   * JSON configuration for the journey
   *
   * @var longtext
   */
  public $configuration;

  /**
   * Status of the journey campaign
   *
   * @var string
   */
  public $status;

  /**
   * Date and time when the journey was created
   *
   * @var datetime
   */
  public $created_date;

  /**
   * Date and time when the journey was last modified
   *
   * @var datetime
   */
  public $modified_date;

  /**
   * Date and time when the journey was activated
   *
   * @var datetime
   */
  public $activated_date;

  /**
   * FK to Contact ID who created this journey
   *
   * @var int
   */
  public $created_id;

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->__table = 'civicrm_journey_campaigns';
    parent::__construct();
  }

  /**
   * Returns localized title of this entity.
   *
   * @param bool $plural
   *   Whether to return the plural version of the title.
   */
  public static function getEntityTitle($plural = FALSE) {
    return $plural ? E::ts('Journey Campaigns') : E::ts('Journey Campaign');
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
          'title' => E::ts('Journey Campaign ID'),
          'description' => E::ts('Unique Journey Campaign ID'),
          'required' => TRUE,
          'where' => 'civicrm_journey_campaigns.id',
          'table_name' => 'civicrm_journey_campaigns',
          'entity' => 'JourneyCampaign',
          'bao' => 'CRM_Journeybuilder_DAO_JourneyCampaign',
          'localizable' => 0,
          'html' => [
            'type' => 'Number',
          ],
          'readonly' => TRUE,
          'add' => NULL,
        ],
        'name' => [
          'name' => 'name',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => E::ts('Journey Name'),
          'description' => E::ts('Name of the Journey Campaign'),
          'required' => TRUE,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
          'where' => 'civicrm_journey_campaigns.name',
          'table_name' => 'civicrm_journey_campaigns',
          'entity' => 'JourneyCampaign',
          'bao' => 'CRM_Journeybuilder_DAO_JourneyCampaign',
          'localizable' => 0,
          'html' => [
            'type' => 'Text',
            'maxlength' => 255,
            'size' => CRM_Utils_Type::HUGE,
          ],
          'add' => NULL,
        ],
        'description' => [
          'name' => 'description',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => E::ts('Description'),
          'description' => E::ts('Description of the Journey Campaign'),
          'where' => 'civicrm_journey_campaigns.description',
          'table_name' => 'civicrm_journey_campaigns',
          'entity' => 'JourneyCampaign',
          'bao' => 'CRM_Journeybuilder_DAO_JourneyCampaign',
          'localizable' => 0,
          'html' => [
            'type' => 'TextArea',
            'rows' => 6,
            'cols' => 60,
          ],
          'add' => NULL,
        ],
        'configuration' => [
          'name' => 'configuration',
          'type' => CRM_Utils_Type::T_LONGTEXT,
          'title' => E::ts('Configuration'),
          'description' => E::ts('JSON configuration for the journey'),
          'where' => 'civicrm_journey_campaigns.configuration',
          'table_name' => 'civicrm_journey_campaigns',
          'entity' => 'JourneyCampaign',
          'bao' => 'CRM_Journeybuilder_DAO_JourneyCampaign',
          'localizable' => 0,
          'html' => [
            'type' => 'TextArea',
            'rows' => 20,
            'cols' => 80,
          ],
          'add' => NULL,
        ],
        'status' => [
          'name' => 'status',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => E::ts('Status'),
          'description' => E::ts('Status of the journey campaign'),
          'maxlength' => 16,
          'size' => CRM_Utils_Type::TWELVE,
          'where' => 'civicrm_journey_campaigns.status',
          'default' => 'draft',
          'table_name' => 'civicrm_journey_campaigns',
          'entity' => 'JourneyCampaign',
          'bao' => 'CRM_Journeybuilder_DAO_JourneyCampaign',
          'localizable' => 0,
          'html' => [
            'type' => 'Select',
          ],
          'pseudoconstant' => [
            'callback' => 'CRM_Journeybuilder_BAO_JourneyCampaign::getStatusOptions',
          ],
          'add' => NULL,
        ],
        'created_date' => [
          'name' => 'created_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => E::ts('Created Date'),
          'description' => E::ts('Date and time when the journey was created'),
          'required' => TRUE,
          'where' => 'civicrm_journey_campaigns.created_date',
          'table_name' => 'civicrm_journey_campaigns',
          'entity' => 'JourneyCampaign',
          'bao' => 'CRM_Journeybuilder_DAO_JourneyCampaign',
          'localizable' => 0,
          'html' => [
            'type' => 'Select Date',
            'formatType' => 'activityDateTime',
          ],
          'add' => NULL,
        ],
        'modified_date' => [
          'name' => 'modified_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => E::ts('Modified Date'),
          'description' => E::ts('Date and time when the journey was last modified'),
          'where' => 'civicrm_journey_campaigns.modified_date',
          'table_name' => 'civicrm_journey_campaigns',
          'entity' => 'JourneyCampaign',
          'bao' => 'CRM_Journeybuilder_DAO_JourneyCampaign',
          'localizable' => 0,
          'html' => [
            'type' => 'Select Date',
            'formatType' => 'activityDateTime',
          ],
          'add' => NULL,
        ],
        'activated_date' => [
          'name' => 'activated_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => E::ts('Activated Date'),
          'description' => E::ts('Date and time when the journey was activated'),
          'where' => 'civicrm_journey_campaigns.activated_date',
          'table_name' => 'civicrm_journey_campaigns',
          'entity' => 'JourneyCampaign',
          'bao' => 'CRM_Journeybuilder_DAO_JourneyCampaign',
          'localizable' => 0,
          'html' => [
            'type' => 'Select Date',
            'formatType' => 'activityDateTime',
          ],
          'add' => NULL,
        ],
        'created_id' => [
          'name' => 'created_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => E::ts('Created By'),
          'description' => E::ts('FK to Contact ID who created this journey'),
          'where' => 'civicrm_journey_campaigns.created_id',
          'table_name' => 'civicrm_journey_campaigns',
          'entity' => 'JourneyCampaign',
          'bao' => 'CRM_Journeybuilder_DAO_JourneyCampaign',
          'localizable' => 0,
          'FKClassName' => 'CRM_Contact_DAO_Contact',
          'html' => [
            'type' => 'EntityRef',
            'label' => E::ts("Created By"),
          ],
          'add' => NULL,
        ],
      ];
      CRM_Core_DAO_AllCoreTables::invoke(__CLASS__, 'fields_callback', Civi::$statics[__CLASS__]['fields']);
    }
    return Civi::$statics[__CLASS__]['fields'];
  }

  /**
   * Return a mapping from field-name to the corresponding key (as used in fields()).
   *
   * @return array
   *   Array(string $name => string $uniqueName).
   */
  public static function &fieldKeys() {
    if (!isset(Civi::$statics[__CLASS__]['fieldKeys'])) {
      Civi::$statics[__CLASS__]['fieldKeys'] = array_flip(CRM_Utils_Array::collect('name', self::fields()));
    }
    return Civi::$statics[__CLASS__]['fieldKeys'];
  }

  /**
   * Returns the names of this table
   *
   * @return string
   */
  public static function getTableName() {
    return self::$_tableName;
  }

  /**
   * Returns if this table needs to be logged
   *
   * @return bool
   */
  public function getLog() {
    return self::$_log;
  }

  /**
   * Returns the list of fields that can be imported
   *
   * @param bool $prefix
   *
   * @return array
   */
  public static function &import($prefix = FALSE) {
    $r = CRM_Core_DAO_AllCoreTables::getImports(__CLASS__, 'journey_campaigns', $prefix, []);
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
    $r = CRM_Core_DAO_AllCoreTables::getExports(__CLASS__, 'journey_campaigns', $prefix, []);
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
      'FK_journey_campaigns_created_id' => [
        'name' => 'FK_journey_campaigns_created_id',
        'field' => [
          0 => 'created_id',
        ],
        'localizable' => FALSE,
        'sig' => 'civicrm_journey_campaigns::0::created_id',
      ],
      'idx_journey_status' => [
        'name' => 'idx_journey_status',
        'field' => [
          0 => 'status',
        ],
        'localizable' => FALSE,
        'sig' => 'civicrm_journey_campaigns::0::status',
      ],
    ];
    return ($localize && !empty($indices)) ? CRM_Core_DAO_AllCoreTables::multilingualize(__CLASS__, $indices) : $indices;
  }

}