<?php

/**
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Database access object for the JourneyStep entity.
 */
class CRM_Journeybuilder_DAO_JourneyStep extends CRM_Core_DAO {
  const EXT = E::LONG_NAME;
  const TABLE_ADDED = '';

  /**
   * Static instance to hold the table name.
   *
   * @var string
   */
  public static $_tableName = 'civicrm_journey_steps';

  /**
   * Should CiviCRM log any modifications to this table in the civicrm_log table.
   *
   * @var bool
   */
  public static $_log = TRUE;

  /**
   * Unique Journey Step ID
   *
   * @var int
   */
  public $id;

  /**
   * FK to Journey Campaign ID
   *
   * @var int
   */
  public $journey_id;

  /**
   * Type of the journey step
   *
   * @var string
   */
  public $step_type;

  /**
   * Name of the journey step
   *
   * @var string
   */
  public $name;

  /**
   * JSON configuration for the step
   *
   * @var longtext
   */
  public $configuration;

  /**
   * X position in the canvas
   *
   * @var float
   */
  public $position_x;

  /**
   * Y position in the canvas
   *
   * @var float
   */
  public $position_y;

  /**
   * Sort order for the step
   *
   * @var int
   */
  public $sort_order;

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->__table = 'civicrm_journey_steps';
    parent::__construct();
  }

  /**
   * Returns localized title of this entity.
   */
  public static function getEntityTitle($plural = FALSE) {
    return $plural ? E::ts('Journey Steps') : E::ts('Journey Step');
  }

  /**
   * Returns all the column names of this table
   */
  public static function &fields() {
    if (!isset(Civi::$statics[__CLASS__]['fields'])) {
      Civi::$statics[__CLASS__]['fields'] = [
        'id' => [
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => E::ts('Journey Step ID'),
          'description' => E::ts('Unique Journey Step ID'),
          'required' => TRUE,
          'where' => 'civicrm_journey_steps.id',
          'table_name' => 'civicrm_journey_steps',
          'entity' => 'JourneyStep',
          'bao' => 'CRM_Journeybuilder_DAO_JourneyStep',
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
          'title' => E::ts('Journey ID'),
          'description' => E::ts('FK to Journey Campaign ID'),
          'required' => TRUE,
          'where' => 'civicrm_journey_steps.journey_id',
          'table_name' => 'civicrm_journey_steps',
          'entity' => 'JourneyStep',
          'bao' => 'CRM_Journeybuilder_DAO_JourneyStep',
          'localizable' => 0,
          'FKClassName' => 'CRM_Journeybuilder_DAO_JourneyCampaign',
          'html' => [
            'type' => 'EntityRef',
            'label' => E::ts("Journey"),
          ],
          'add' => NULL,
        ],
        'step_type' => [
          'name' => 'step_type',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => E::ts('Step Type'),
          'description' => E::ts('Type of the journey step'),
          'required' => TRUE,
          'maxlength' => 16,
          'size' => CRM_Utils_Type::TWELVE,
          'where' => 'civicrm_journey_steps.step_type',
          'table_name' => 'civicrm_journey_steps',
          'entity' => 'JourneyStep',
          'bao' => 'CRM_Journeybuilder_DAO_JourneyStep',
          'localizable' => 0,
          'html' => [
            'type' => 'Select',
          ],
          'pseudoconstant' => [
            'callback' => 'CRM_Journeybuilder_BAO_JourneyStep::getStepTypeOptions',
          ],
          'add' => NULL,
        ],
        'name' => [
          'name' => 'name',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => E::ts('Step Name'),
          'description' => E::ts('Name of the journey step'),
          'required' => TRUE,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
          'where' => 'civicrm_journey_steps.name',
          'table_name' => 'civicrm_journey_steps',
          'entity' => 'JourneyStep',
          'bao' => 'CRM_Journeybuilder_DAO_JourneyStep',
          'localizable' => 0,
          'html' => [
            'type' => 'Text',
            'maxlength' => 255,
            'size' => CRM_Utils_Type::HUGE,
          ],
          'add' => NULL,
        ],
        'configuration' => [
          'name' => 'configuration',
          'type' => CRM_Utils_Type::T_LONGTEXT,
          'title' => E::ts('Configuration'),
          'description' => E::ts('JSON configuration for the step'),
          'where' => 'civicrm_journey_steps.configuration',
          'table_name' => 'civicrm_journey_steps',
          'entity' => 'JourneyStep',
          'bao' => 'CRM_Journeybuilder_DAO_JourneyStep',
          'localizable' => 0,
          'html' => [
            'type' => 'TextArea',
            'rows' => 20,
            'cols' => 80,
          ],
          'add' => NULL,
        ],
        'position_x' => [
          'name' => 'position_x',
          'type' => CRM_Utils_Type::T_MONEY,
          'title' => E::ts('Position X'),
          'description' => E::ts('X position in the canvas'),
          'precision' => [10, 2],
          'where' => 'civicrm_journey_steps.position_x',
          'default' => '0',
          'table_name' => 'civicrm_journey_steps',
          'entity' => 'JourneyStep',
          'bao' => 'CRM_Journeybuilder_DAO_JourneyStep',
          'localizable' => 0,
          'html' => [
            'type' => 'Text',
          ],
          'add' => NULL,
        ],
        'position_y' => [
          'name' => 'position_y',
          'type' => CRM_Utils_Type::T_MONEY,
          'title' => E::ts('Position Y'),
          'description' => E::ts('Y position in the canvas'),
          'precision' => [10, 2],
          'where' => 'civicrm_journey_steps.position_y',
          'default' => '0',
          'table_name' => 'civicrm_journey_steps',
          'entity' => 'JourneyStep',
          'bao' => 'CRM_Journeybuilder_DAO_JourneyStep',
          'localizable' => 0,
          'html' => [
            'type' => 'Text',
          ],
          'add' => NULL,
        ],
        'sort_order' => [
          'name' => 'sort_order',
          'type' => CRM_Utils_Type::T_INT,
          'title' => E::ts('Sort Order'),
          'description' => E::ts('Sort order for the step'),
          'where' => 'civicrm_journey_steps.sort_order',
          'default' => '0',
          'table_name' => 'civicrm_journey_steps',
          'entity' => 'JourneyStep',
          'bao' => 'CRM_Journeybuilder_DAO_JourneyStep',
          'localizable' => 0,
          'html' => [
            'type' => 'Text',
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
   */
  public static function &fieldKeys() {
    if (!isset(Civi::$statics[__CLASS__]['fieldKeys'])) {
      Civi::$statics[__CLASS__]['fieldKeys'] = array_flip(CRM_Utils_Array::collect('name', self::fields()));
    }
    return Civi::$statics[__CLASS__]['fieldKeys'];
  }

  /**
   * Returns the names of this table
   */
  public static function getTableName() {
    return self::$_tableName;
  }

  /**
   * Returns if this table needs to be logged
   */
  public function getLog() {
    return self::$_log;
  }

  /**
   * Returns the list of fields that can be imported
   */
  public static function &import($prefix = FALSE) {
    $r = CRM_Core_DAO_AllCoreTables::getImports(__CLASS__, 'journey_steps', $prefix, []);
    return $r;
  }

  /**
   * Returns the list of fields that can be exported
   */
  public static function &export($prefix = FALSE) {
    $r = CRM_Core_DAO_AllCoreTables::getExports(__CLASS__, 'journey_steps', $prefix, []);
    return $r;
  }

  /**
   * Returns the list of indices
   */
  public static function indices($localize = TRUE) {
    $indices = [
      'FK_journey_steps_journey_id' => [
        'name' => 'FK_journey_steps_journey_id',
        'field' => [
          0 => 'journey_id',
        ],
        'localizable' => FALSE,
        'sig' => 'civicrm_journey_steps::0::journey_id',
      ],
      'idx_step_type' => [
        'name' => 'idx_step_type',
        'field' => [
          0 => 'step_type',
        ],
        'localizable' => FALSE,
        'sig' => 'civicrm_journey_steps::0::step_type',
      ],
    ];
    return ($localize && !empty($indices)) ? CRM_Core_DAO_AllCoreTables::multilingualize(__CLASS__, $indices) : $indices;
  }

}