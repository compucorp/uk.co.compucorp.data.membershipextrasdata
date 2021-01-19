<?php

class CRM_Membershipextrasdata_Factory_PriceField {

  public static function create($params) {
    $defaultFieldParams = [
      "financial_type_id" => "Member Dues",
      "is_enter_qty" => 0,
      "weight" => 1,
      "is_display_amounts" => 1,
      "options_per_line" => 1,
      "is_active" => 1,
      "is_required" => 0,
      "visibility_id" => 1,
    ];

    $params = array_merge($defaultFieldParams, $params);
    $createdRecordResponse = civicrm_api3("PriceField", "create", $params);

    return $createdRecordResponse;
  }

}
