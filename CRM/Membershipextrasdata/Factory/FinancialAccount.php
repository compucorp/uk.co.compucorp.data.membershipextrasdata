<?php

class CRM_Membershipextrasdata_Factory_FinancialAccount {

  /**
   * @param $params
   *
   * @throws \CiviCRM_API3_Exception
   */
  public static function create($params) {
    $existingRecordResponse = civicrm_api3('FinancialAccount', 'get', [
      'sequential' => 1,
      'options' => ['limit' => 1],
      'name' => $params['name'],
    ]);

    if (empty($existingRecordResponse['id'])) {
      civicrm_api3('FinancialAccount', 'create', $params);
      civicrm_api3('EntityFinancialAccount', 'create', $params['entity']);
    }

  }

}
