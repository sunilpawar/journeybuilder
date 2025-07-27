<?php

namespace Civi\Api4;

use Civi\Api4\Generic\BasicGetFieldsAction;

/**
 * Journey entity for API v4
 *
 * This is a custom entity for managing email marketing journeys.
 *
 * @package Civi\Api4
 */
class Journey extends Generic\AbstractEntity {

  /**
   * @return \Civi\Api4\Action\Journey\Save
   */
  public static function save($checkPermissions = TRUE) {
    return (new \Civi\Api4\Action\Journey\Save(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @return \Civi\Api4\Action\Journey\Get
   */
  public static function get($checkPermissions = TRUE) {
    return (new \Civi\Api4\Action\Journey\Get(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @return \Civi\Api4\Action\Journey\Activate
   */
  public static function activate($checkPermissions = TRUE) {
    return (new \Civi\Api4\Action\Journey\Activate(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @return \Civi\Api4\Action\Journey\Pause
   */
  public static function pause($checkPermissions = TRUE) {
    return (new \Civi\Api4\Action\Journey\Pause(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @return \Civi\Api4\Action\Journey\Process
   */
  public static function process($checkPermissions = TRUE) {
    return (new \Civi\Api4\Action\Journey\Process(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @return \Civi\Api4\Action\Journey\Test
   */
  public static function test($checkPermissions = TRUE) {
    return (new \Civi\Api4\Action\Journey\Test(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @return \Civi\Api4\Action\Journey\Analytics
   */
  public static function analytics($checkPermissions = TRUE) {
    return (new \Civi\Api4\Action\Journey\Analytics(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @return \Civi\Api4\Generic\BasicGetFieldsAction
   */
  public static function getFields($checkPermissions = TRUE) {
    return (new BasicGetFieldsAction(__CLASS__, __FUNCTION__, function() {
      return [
        [
          'name' => 'id',
          'title' => 'Journey ID',
          'description' => 'Unique Journey ID',
          'data_type' => 'Integer',
          'input_type' => 'Number',
          'required' => FALSE,
          'readonly' => TRUE,
        ],
        [
          'name' => 'name',
          'title' => 'Journey Name',
          'description' => 'Name of the Journey',
          'data_type' => 'String',
          'input_type' => 'Text',
          'required' => TRUE,
        ],
        [
          'name' => 'description',
          'title' => 'Journey Description',
          'description' => 'Description of the Journey',
          'data_type' => 'Text',
          'input_type' => 'TextArea',
          'required' => FALSE,
        ],
        [
          'name' => 'status',
          'title' => 'Journey Status',
          'description' => 'Current status of the journey',
          'data_type' => 'String',
          'input_type' => 'Select',
          'required' => TRUE,
          'options' => [
            'draft' => 'Draft',
            'active' => 'Active',
            'paused' => 'Paused',
            'completed' => 'Completed',
            'archived' => 'Archived',
          ],
        ],
        [
          'name' => 'configuration',
          'title' => 'Journey Configuration',
          'description' => 'JSON configuration for the journey',
          'data_type' => 'Text',
          'input_type' => 'TextArea',
          'required' => FALSE,
        ],
        [
          'name' => 'created_date',
          'title' => 'Created Date',
          'description' => 'Date the journey was created',
          'data_type' => 'Timestamp',
          'input_type' => 'Date',
          'required' => FALSE,
          'readonly' => TRUE,
        ],
        [
          'name' => 'modified_date',
          'title' => 'Modified Date',
          'description' => 'Date the journey was last modified',
          'data_type' => 'Timestamp',
          'input_type' => 'Date',
          'required' => FALSE,
          'readonly' => TRUE,
        ],
        [
          'name' => 'activated_date',
          'title' => 'Activated Date',
          'description' => 'Date the journey was activated',
          'data_type' => 'Timestamp',
          'input_type' => 'Date',
          'required' => FALSE,
          'readonly' => TRUE,
        ],
        [
          'name' => 'created_id',
          'title' => 'Created By',
          'description' => 'Contact ID who created the journey',
          'data_type' => 'Integer',
          'input_type' => 'EntityRef',
          'required' => FALSE,
          'fk_entity' => 'Contact',
        ],
      ];
    }))->setCheckPermissions($checkPermissions);
  }

  /**
   * @return array
   */
  public static function permissions() {
    return [
      'meta' => ['access CiviCRM'],
      'default' => ['administer CiviCRM'],
      'get' => ['access CiviCRM', 'view all contacts'],
      'save' => ['administer CiviCRM'],
      'activate' => ['administer CiviCRM'],
      'pause' => ['administer CiviCRM'],
      'process' => ['administer CiviCRM'],
      'test' => ['administer CiviCRM'],
      'analytics' => ['access CiviCRM', 'view all contacts'],
    ];
  }

}