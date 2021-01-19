<?php

use CRM_Membershipextrasdata_Utils as Utils;

class CRM_Membershipextrasdata_Factory_PriceSet {

  public static function create($params) {
    $existingRecordResponse = civicrm_api3("PriceSet", "get", [
      "return" => ["id"],
      "sequential" => 1,
      "name" => Utils::slugify($params["title"]),
      "options" => ["limit" => 1],
    ]);

    if (!empty($existingRecordResponse["id"])) {
      return;
    }

    $createdRecordResponse = civicrm_api3("PriceSet", "create", [
      "title" => $params["title"],
      "name" => Utils::slugify($params["title"]),
      "extends" => "CiviMember",
      "min_amount" => $params["min_amount"],
      "financial_type_id" => $params["financial_type_id"],
      "is_active" => 1,
    ]);

    $priceSetId = $createdRecordResponse["id"];

    foreach ($params["fields"] as $key => $field) {
      $field["price_set_id"] = $priceSetId;
      $field["weight"] = $key + 1;

      $field["name"] = Utils::slugify("{$params["title"]} {$field["label"]}");

      $createdField = CRM_Membershipextrasdata_Factory_PriceField::create($field);

      foreach ($field["values"] as $value) {
        $value["price_field_id"] = $createdField["id"];
        $value["name"] = Utils::slugify($value["label"]);
        CRM_Membershipextrasdata_Factory_PriceFieldValue::create($value);
      }
    }
  }

}
