<?php
// This file declares a managed database record of type "Job".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed
return [
  [
    'name' => 'Cron:Journey.Process',
    'entity' => 'Job',
    'params' => [
      'version' => 3,
      'name' => 'Process Journey Steps',
      'description' => 'Processes active journey participants and executes their next steps',
      'run_frequency' => 'Always',
      'api_entity' => 'Journey',
      'api_action' => 'Process',
    ],
  ],
];
