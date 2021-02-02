<?php

use CRM_Membershipextrasdata_Utils_String as StringUtils;

class CRM_Membershipextrasdata_Factory_PriceSet {

  /**
   * @param $params
   *
   * @throws \CiviCRM_API3_Exception
   */
  public static function create($params) {
    $existingRecordResponse = civicrm_api3("PriceSet", "get", [
      "return" => ["id"],
      "sequential" => 1,
      "name" => StringUtils::slugify($params["title"]),
      "options" => ["limit" => 1],
    ]);

    if (!empty($existingRecordResponse["id"])) {
      return;
    }

    $createdRecordResponse = civicrm_api3("PriceSet", "create", [
      "title" => $params["title"],
      "name" => StringUtils::slugify($params["title"]),
      "extends" => "CiviMember",
      "min_amount" => $params["min_amount"],
      "financial_type_id" => $params["financial_type_id"],
      "is_active" => 1,
    ]);

    $priceSetId = $createdRecordResponse["id"];

    foreach ($params["fields"] as $key => $field) {
      $field["price_set_id"] = $priceSetId;
      $field["weight"] = $key + 1;

      $field["name"] = StringUtils::slugify("{$params["title"]} {$field["label"]}");

      $createdField = CRM_Membershipextrasdata_Factory_PriceField::create($field);

      foreach ($field["values"] as $value) {
        $value["price_field_id"] = $createdField["id"];
        $value["name"] = StringUtils::slugify($value["label"]);
        CRM_Membershipextrasdata_Factory_PriceFieldValue::create($value);
      }
    }
  }

}
