<?php

/**
 * CountryActionPlan.Request API
 * This API retrieves all active relationships Country Coordinator and
 * creates a new activity New Country Action Plan Request for each
 * Country Coordinator, scheduled for 31 December of the run year
 * 
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 */
function civicrm_api3_country_action_plan_request($params) {
  /*
   * retrieve or create activity type and activity status
   */
  $capRequestActivityType = new CRM_Yearlycaprequest_ActivityType();
  $capRequestActivityTypeId = (int) $capRequestActivityType->getActivityTypeId();
  $activityStatusId = getActivityStatusScheduled();

  $countryCoordinators = getActiveCountryCoordinators();
  foreach ($countryCoordinators as $countryCoordinator) {
    createCapActivity($capRequestActivityTypeId, $countryCoordinator['contact_id_b'],
        $countryCoordinator['contact_id_a'], $activityStatusId);
    $returnValues[] = 'Activity created for country coordinator '.$countryCoordinator['contact_id_b'];
  }
  return civicrm_api3_create_success($returnValues, $params, 'CountryActionPlan', 'Request');
}

/**
 * Function to create cap_activity
 *
 * @param int $capRequestActivityTypeId
 * @param int $contactId
 * @param int $countryId
 * @param int $activityStatusId
 */
function createCapActivity($capRequestActivityTypeId, $contactId, $countryId, $activityStatusId) {
  if (CRM_Threepeas_Utils::contactIsCountry($countryId) == TRUE) {
    $newYear = (int) date('Y') + 1;
    $params = array(
      'activity_type_id' => $capRequestActivityTypeId,
      'activity_subject' => 'New Country Action Plan required for '.$newYear,
      'target_id' => $countryId,
      'assignee_id' => $contactId,
      'activity_date_time' => date('Y').'-12-31',
      'activity_status_id' => $activityStatusId
    );
    if (checkCapActivityExists($params) == FALSE) {
      civicrm_api3('Activity', 'Create', $params);
    }
  }
}

/**
 * Function to check if the activity to be created already exists
 * 
 * @param array $activityParams
 * @return boolean
 */
function checkCapActivityExists($activityParams) {
  $assigneeCheck = FALSE;
  $targetCheck = FALSE;
  $query = getCheckQuery();
  $queryParams = getCheckParams($activityParams);
  $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
  while ($dao->fetch()) {
    if ($dao->record_type_id == 1 && $dao->contact_id == $activityParams['assignee_id']) {
      $assigneeCheck = TRUE;
    }
    if ($dao->record_type_id == 3 && $dao->contact_id == $activityParams['target_id']) {
      $targetCheck = TRUE;
    }
  }
  if ($assigneeCheck == TRUE && $targetCheck == TRUE) {
    return TRUE;
  } else {
    return FALSE;
  }
}

/**
 * Function to build check query
 * 
 * @return string $query
 */
function getCheckQuery()
{
  $query = 'SELECT a.id, b.contact_id, b.record_type_id FROM civicrm_activity a
    JOIN civicrm_activity_contact b ON a.id = b.activity_id AND record_type_id IN(%1,%2)
    WHERE activity_type_id = %3 AND is_current_revision = %4 AND subject = %5 
    AND status_id = %6 AND activity_date_time = %7 AND (b.contact_id = %8 OR b.contact_id = %9)';
  return $query;
}

/**
 * Function to build check query params
 * 
 * @param array $activityParams
 * @return array $queryParams
 */
function getCheckParams($activityParams) {
    $queryParams = array(
    1 => array(1, 'Integer'),
    2 => array(3, 'Integer'),
    3 => array($activityParams['activity_type_id'], 'Integer'),
    4 => array(1, 'Integer'),
    5 => array($activityParams['activity_subject'], 'String'),
    6 => array($activityParams['activity_status_id'], 'Integer'),
    7 => array($activityParams['activity_date_time'], 'String'),
    8 => array($activityParams['assignee_id'], 'Integer'),
    9 => array($activityParams['target_id'], 'Integer'));
    return $queryParams;
}

/**
 * Function to get activityStatusId for scheduled
 * 
 * @return int $activityStatusId
 * @throws API_Exception when no option group activity_status found
 * @throws API_Exception when no option value Scheduled found
 */
function getActivityStatusScheduled() {
  try {
    $optionGroupId = civicrm_api3('OptionGroup', 'Getvalue',
      array('name' => 'activity_status', 'return' => 'id'));
  } catch (CiviCRM_API3_Exception $ex) {
    throw new API_Exception('Could not find option group with name activity_status, '
      . 'error from API OptionGroup Getvalue: '.$ex->getMessage());
  }
  $params = array(
    'option_group_id' => $optionGroupId,
    'name' => 'Scheduled',
    'return' => 'value');
  try {
    $activityStatusId = civicrm_api3('OptionValue', 'Getvalue', $params);
  } catch (CiviCRM_API3_Exception $ex) {
    throw new API_Exception('Could not find option value with name Scheduled, '
      . 'in group activity_status, error from API OptionValue Getvalue: '.$ex->getMessage());
  }
  return $activityStatusId;
}

/**
 * Function to get active country coordinators
 * 
 * @return array countryCoordinators['values']
 * @throws API_Exception when api Relationship Get throws an error
 * @throws API_Exception when class CRM_Threepeas_CaseRelationConfig (to retrieve relationship
 *         type id for Country Coordinator)
 */
function getActiveCountryCoordinators() {
  if (!class_exists('CRM_Threepeas_CaseRelationConfig')) {
    throw new API_Exception('Could not find class CRM_Threepeas_CaseRelationConfig, check if '
      . 'required extension nl.pum.threepeas is installed and enabled');
  }
  $caseRelationConfig = CRM_Threepeas_CaseRelationConfig::singleton();
  $params = array(
    'is_active' => 1,
    'options' => array('limit' => 99999),
    'relationship_type_id' => $caseRelationConfig->get_relationship_type_id('country_coordinator'));
  try {
    $countryCoordinators = civicrm_api3('Relationship', 'Get', $params);
  } catch (CiviCRM_API3_Exception $ex) {
    throw new API_Exception('Error retrieving Country Coordinators, error from '
      . 'API Relationship Get: '.$ex->getMessage());
  }
  return $countryCoordinators['values'];
}