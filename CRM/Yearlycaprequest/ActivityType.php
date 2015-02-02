<?php

/**
 * Class to deal with the yearly cap request activity type
 * 
 * @author Erik Hommel - CiviCooP <erik.hommel@civicoop.org>
 * @license http://www.gnu.org/licenses/agpl-3.0.html
 */
class CRM_Yearlycaprequest_ActivityType {
  protected $activityTypeId = 0;
  protected $activityTypeName = NULL;
  protected $activityTypeOptionGroupId = NULL;
  /**
   * Constructor function
   */
  public function __construct() {
    $this->activityTypeName = 'new_cap_request';
    $this->setActivityTypeId();
  }
  /**
   * Function to return the activity type id
   * 
   * @return type
   * @access public
   */
  public function getActivityTypeId() {
    return (int) $this->activityTypeId;
  }
  /**
   * Function to set the activity type id (or create it not found)
   * 
   * @access protected
   */
  protected function setActivityTypeId() {
    $activityTypeOptionGroupId = $this->getActivityTypeOptionGroupId();
    $params = array(
      'option_group_id' => $activityTypeOptionGroupId,
      'return' => 'id',
      'name' => $this->activityTypeName);
    try {
      $this->activityTypeId = (int) civicrm_api3('OptionValue', 'Getvalue', $params);
    } catch (CiviCRM_API3_Exception $ex) {
      $this->createActivityType();
    }
  }
  /**
   * Function to create activity type new_cap_request
   *
   * @access protected
   */
  protected function createActivityType() {
    $params = array(
      'name' => $this->activityTypeName,
      'label' => 'New Yearly CAP Request',
      'description' => 'Activity to remind the Country Coordinator that a yearly Country Action Plan needs to be submitted',
      'is_active' => 1,
      'option_group_id' => $this->activityTypeOptionGroupId);
    try {
      $optionValue = civicrm_api3('OptionValue', 'Create', $params);
      $this->getActivityTypeId() = (int) $optionValue['value'];
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not create the required activity type new_cap_request, error from API OptionValue Create: '
          .$ex->getMessage());
    }
  }
  /**
   * Function to get activity type option group
   *
   * @throws Exception when activity_type option group not found
   * @access protected
   */
  protected function getActivityTypeOptionGroupId() {
    try {
    $this->activityTypeOptionGroupId = civicrm_api3('OptionGroup', 'Getvalue',
      array('name' => 'activity_type', 'return' => 'id'));
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find option group with name activity_type, '
          . 'error from API OptionGroup Getvalue: ' . $ex->getMessage());
    }
  }
}
