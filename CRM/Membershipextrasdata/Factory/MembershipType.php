<?php

class CRM_Membershipextrasdata_Factory_MembershipType {

  /**
   * @param $params
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  public static function create($params) {
    $existingRecordResponse = civicrm_api3("MembershipType", "get", [
      "sequential" => 1,
      "options" => ["limit" => 1],
      "name" => $params["name"],
    ]);

    if (!empty($existingRecordResponse["id"])) {
      return $existingRecordResponse;
    }

    $createdRecordResponse = civicrm_api3("MembershipType", "create", $params);
    return $createdRecordResponse;
  }

}
