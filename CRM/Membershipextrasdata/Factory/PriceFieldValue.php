<?php

class CRM_Membershipextrasdata_Factory_PriceFieldValue {

  /**
   * @param $params
   *
   * @throws \CiviCRM_API3_Exception
   */
  public static function create($params) {
    $defaultFieldValueParams = [
      "financial_type_id" => "Member Dues",
      "membership_num_terms" => 1,
      "non_deductible_amount" => 0,
    ];

    $params = array_merge($defaultFieldValueParams, $params);
    civicrm_api3("PriceFieldValue", "create", $params);
  }

}
