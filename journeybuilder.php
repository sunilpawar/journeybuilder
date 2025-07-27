<?php

require_once 'journeybuilder.civix.php';

use CRM_Journeybuilder_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function journeybuilder_civicrm_config(&$config): void {
  _journeybuilder_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function journeybuilder_civicrm_install(): void {
  _journeybuilder_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function journeybuilder_civicrm_enable(): void {
  _journeybuilder_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_navigationMenu().
 */
function journeybuilder_civicrm_navigationMenu(&$menu) {
  $parentMenu = [[
    'attributes' => [
      'label' => E::ts('Marketing'),
      'name' => 'marketing_main',
      'url' => NULL,
      'operator' => NULL,
      'separator' => 0,
      'active' => 1,
      'icon' => 'crm-i fa-ticket',
      'weight' => 35,
      'permission' => 'administer CiviCRM'
    ]]];
  array_splice($menu, 6, 0, $parentMenu);

  _journeybuilder_civix_insert_navigation_menu($menu, 'marketing_main', [
    'label' => E::ts('Journey Builder'),
    'name' => 'journey_builder',
    'url' => 'civicrm/journey/builder',
    'permission' => 'access CiviMail,create mailings',
    'operator' => 'OR',
    'separator' => 0,
  ]);

  _journeybuilder_civix_insert_navigation_menu($menu, 'marketing_main', [
    'label' => E::ts('Create Journey'),
    'name' => 'create_journey',
    'url' => 'civicrm/journey/create',
    'permission' => 'access CiviMail,create mailings',
    'operator' => 'OR',
    'separator' => 0,
  ]);

  _journeybuilder_civix_insert_navigation_menu($menu, 'marketing_main', [
    'label' => E::ts('Journey Analytics'),
    'name' => 'journey_analytics',
    'url' => 'civicrm/journey/analytics',
    'permission' => 'access CiviMail',
    'operator' => 'OR',
    'separator' => 0,
  ]);

  // Add Journey Builder main menu item
  _journeybuilder_civix_insert_navigation_menu($menu, 'marketing_main', [
    'label' => E::ts('Journey Builder'),
    'name' => 'journey_builder',
    'url' => 'civicrm/journey/list',
    'permission' => 'administer CiviCRM',
    'operator' => 'OR',
    'separator' => 0,
  ]);
}
