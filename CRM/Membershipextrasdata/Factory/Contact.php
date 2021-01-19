<?php

class CRM_Membershipextrasdata_Factory_Contact {

  public static function createOrg($name) {
    $existingRecordResponse = civicrm_api3("Contact", "get", [
      "sequential" => 1,
      "options" => ["limit" => 1],
      "contact_type" => "Organization",
      "organization_name" => $name,
    ]);

    if (!empty($existingRecordResponse["id"])) {
      return $existingRecordResponse;
    }

    $createdRecordResponse = civicrm_api3("Contact", "create", [
      "contact_type" => "Organization",
      "organization_name" => $name,
    ]);

    return $createdRecordResponse;
  }

}
