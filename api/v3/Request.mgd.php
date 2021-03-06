<?php
// This file declares a managed database record of type "Job".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// http://wiki.civicrm.org/confluence/display/CRMDOC42/Hook+Reference
return array (
  0 => 
  array (
    'name' => 'Cron:CountryActionPlan.Request',
    'entity' => 'Job',
    'params' => 
    array (
      'version' => 3,
      'name' => 'Yearly Request New Country Action Plan',
      'description' => 'Generate yearly request for new Country Action Plan to all active Country Coordinators',
      'run_frequency' => 'Daily',
      'api_entity' => 'CountryActionPlan',
      'api_action' => 'Request',
      'parameters' => '',
      'is_active' => 0
    ),
  ),
);