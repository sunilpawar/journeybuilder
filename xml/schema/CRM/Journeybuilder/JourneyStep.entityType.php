<?php
// This file declares a new entity type. For more details, see "hook_civicrm_entityTypes" at:
// https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
return [
  [
    'name' => 'JourneyStep',
    'class' => 'CRM_Journeybuilder_DAO_JourneyStep',
    'table' => 'civicrm_journey_steps',
  ],
];
