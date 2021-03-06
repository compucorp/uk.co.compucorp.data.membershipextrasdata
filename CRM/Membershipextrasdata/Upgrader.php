<?php

use CRM_Membershipextrasdata_ExtensionUtil as ExtensionUtil;

class CRM_Membershipextrasdata_Upgrader extends CRM_Membershipextrasdata_Upgrader_Base {

  /**
   * @var array
   */
  private $membershipData = [];

  /**
   * @var array
   */
  private $priceSetData = [];

  /**
   * @var array
   */
  private $orgsIdsMap = [];

  /**
   * @var array
   */
  private $membershipTypesIdsMap = [];

  /**
   * @var int
   */
  private $memberDuesFinancialTypeId = NULL;

  public function install() {
    $this->setMemberDuesFinancialTypeId();
    $this->enableTaxAndInvoiceSettings();
    $this->createSalesTaxFinancialAccount();
    $this->createDDOriginatorNumber();
    $this->setDDPaymentMethodFinancialAccount();
    $this->importMembershipTypesAndPriceSets();
    $this->createMembershipOrgs();
    $this->createMembershipTypes();
    $this->createPriceSetsAndFields();
    $this->createDiscountCodes();
    $this->createTestingWebforms();
    $this->setDefaultManualDirectDebitConfigurations();
  }

  /**
   * Obtains value for member dues financial type option value.
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function setMemberDuesFinancialTypeId() {
    $existingRecordResponse = civicrm_api3('FinancialType', 'get', [
      'return' => 'id',
      'name' => 'Member Dues',
    ]);

    if (!empty($existingRecordResponse["id"])) {
      $this->memberDuesFinancialTypeId = $existingRecordResponse['id'];
      return;
    }

    $createdRecordResponse = civicrm_api3("FinancialType", "create", [
      'name' => 'Member Dues',
      'is_active' => 1,
    ]);

    $this->memberDuesFinancialTypeId = $createdRecordResponse['id'];
  }

  /**
   * Enable tax and invoice
   */
  private function enableTaxAndInvoiceSettings() {
    $invoiceParams = [
      "invoicing" => ["invoicing" => 1],
      "invoice_prefix" => "INV_",
      "credit_notes_prefix" => "CN_",
      "due_date" => "10",
      "due_date_period" => "days",
      "notes" => "",
      "tax_term" => "Sales Tax",
      "tax_display_settings" => "Inclusive",
    ];
    Civi::settings()->set("contribution_invoice_settings", $invoiceParams);
  }

  /**
   * Create sales tax financial account
   */
  private function createSalesTaxFinancialAccount() {
    $params = [
      "name" => "Sales Tax",
      "contact_id" => 1,
      "financial_account_type_id" => "Liability",
      "accounting_code" => 5500,
      "is_header_account" => 0,
      "is_deductible" => 1,
      "is_tax" => 1,
      "tax_rate" => 20,
      "is_active" => 1,
      "is_default" => 0,
      "entity" => [
        "financial_account_id" => "Sales Tax",
        "entity_table" => "civicrm_financial_type",
        "entity_id" => $this->memberDuesFinancialTypeId,
        "account_relationship" => "Sales Tax Account is",
      ],
    ];

    CRM_Membershipextrasdata_Factory_FinancialAccount::create($params);
  }

  /**
   * Create originator number for direct debit
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function createDDOriginatorNumber() {
    $ddNumbersToCreate = ["01", "02"];

    foreach ($ddNumbersToCreate as $ddNumberName) {
      civicrm_api3("OptionValue", "create", [
        "option_group_id" => "direct_debit_originator_number",
        "label" => $ddNumberName,
      ]);
    }
  }

  /**
   * Set related financial account with direct debit
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function setDDPaymentMethodFinancialAccount() {
    $directDebitPaymentMethodOptionValueId = civicrm_api3("OptionValue", "getvalue", [
      "return" => "id",
      "option_group_id" => "payment_instrument",
      "name" => "direct_debit",
    ]);
    civicrm_api3("EntityFinancialAccount", "create", [
      "entity_table" => "civicrm_option_value",
      "entity_id" => $directDebitPaymentMethodOptionValueId,
      "account_relationship" => "Asset Account is",
      "financial_account_id" => "Deposit Bank Account",
    ]);
  }

  /**
   *  Import Membership Type and PriceSet data
   */
  private function importMembershipTypesAndPriceSets() {
    $jsonData = file_get_contents(ExtensionUtil::path("MembershipTypesAndPriceSetsData.json"), "r");
    $data = json_decode($jsonData, TRUE);
    $this->membershipData = $data["membershipData"];
    $this->priceSetData = $data["priceSetData"];
    unset($jsonData);
    unset($data);
  }

  /**
   * Create Organizations
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function createMembershipOrgs() {
    foreach ($this->membershipData as $orgName => $orgData) {
      $org = CRM_Membershipextrasdata_Factory_Contact::createOrg($orgName);
      $this->orgsIdsMap[$orgName] = $org["id"];
    }
  }

  /**
   * Create Membership Types
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function createMembershipTypes() {
    // Add relationship_type_id to membership types
    foreach ($this->membershipData as $orgName => $orgData) {
      if (empty($this->membershipData[$orgName]["membership_type"]["relationship_name"])) {
        continue;
      }

      $relationshipName = $this->membershipData[$orgName]["membership_type"]["relationship_name"];
      $this->membershipData[$orgName]["membership_type"]["relationship_type_id"] = $this->getRelationshipTypeIdByName($relationshipName);
    }

    $membershipTypeDefault = [
      "visibility" => "Public",
      "period_type" => "rolling",
      "financial_type_id" => "Member Dues",
      "duration_unit" => "year",
      "duration_interval" => 1,
      "auto_renew" => 1,
    ];
    foreach ($this->membershipData as $orgName => $orgData) {
      $params = array_merge($membershipTypeDefault, $orgData["membership_type"]);
      $params["member_of_contact_id"] = $this->orgsIdsMap[$orgName];

      $membershipType = CRM_Membershipextrasdata_Factory_MembershipType::create($params);
      $this->membershipTypesIdsMap[$params["name"]] = $membershipType["id"];
    }
  }

  /**
   * @param $name
   *
   * @return mixed|null
   * @throws \CiviCRM_API3_Exception
   */
  private function getRelationshipTypeIdByName($name) {
    $result = civicrm_api3("RelationshipType", "get", [
      "sequential" => 1,
      "name_b_a" => $name,
    ]);

    // @note It is a reserved relation type
    // should we remove this check
    if (!empty($result["id"])) {
      return $result["id"];
    }

    return NULL;
  }

  /**
   * Create priceSet
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function createPriceSetsAndFields() {
    // Add membership_type_id to price set field values
    $membershipTypesIdsMap = $this->membershipTypesIdsMap;
    foreach ($this->priceSetData as $key => $priceSetItem) {
      $this->priceSetData[$key]["fields"] = array_map(function ($field) use ($membershipTypesIdsMap) {
        $field["values"] = array_map(function ($value) use ($membershipTypesIdsMap) {
          $value["membership_type_id"] = $membershipTypesIdsMap[$value["label"]];
          return $value;
        }, $field["values"]);
        return $field;
      }, $priceSetItem["fields"]);
    }

    foreach ($this->priceSetData as $priceSet) {
      CRM_Membershipextrasdata_Factory_PriceSet::create($priceSet);
    }
  }

  /**
   * Create Discount codes
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function createDiscountCodes() {
    $allMembershipTypesIds = $this->getAllMembershipTypesIds();

    $response = civicrm_api3("DiscountCode", "get", [
      "sequential" => 1,
      "return" => ["id"],
      "code" => "50Percent",
    ]);
    if (empty($response["id"])) {
      civicrm_api3("DiscountCode", "create", [
        "code" => "50Percent",
        "amount_type" => 1,
        "amount" => 50,
        "count_max" => 0,
        "description" => "50 Percent Discount",
        "memberships" => $allMembershipTypesIds,
        "is_active" => 1,
      ]);
    }

    $response = civicrm_api3("DiscountCode", "get", [
      "sequential" => 1,
      "return" => ["id"],
      "code" => "50Fixed",
    ]);
    if (empty($response["id"])) {
      civicrm_api3("DiscountCode", "create", [
        "code" => "50Fixed",
        "amount_type" => 2,
        "amount" => 50,
        "count_max" => 0,
        "description" => "50 Pound Discount",
        "memberships" => $allMembershipTypesIds,
        "is_active" => 1,
      ]);
    }
  }

  /**
   * Get membership types ids
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  private function getAllMembershipTypesIds() {
    $apiResponse = civicrm_api3("MembershipType", "get", [
      "sequential" => 1,
      "return" => ["id"],
      "options" => ["limit" => 0],
    ]);

    $membershipTypes = [];
    if ($apiResponse["count"] > 0) {
      foreach ($apiResponse["values"] as $membership) {
        $membershipTypes[] = $membership["id"];
      }
    }

    return $membershipTypes;
  }

  /**
   * Start importing webforms
   */
  private function createTestingWebforms() {
    $this->importWebforms();
    $this->enableDiscountFieldsOnWebforms();
  }

  /**
   * Import webforms from exported nodes data
   */
  private function importWebforms() {
    $webformExportsDirectoryName = ExtensionUtil::path("WebformExport");
    $exportFiles = array_diff(scandir($webformExportsDirectoryName), [
      ".",
      "..",
    ]);
    usort($exportFiles, "strnatcmp");
    foreach ($exportFiles as $fileName) {
      $filePath = $webformExportsDirectoryName . "/" . $fileName;
      $this->importWebformByPath($filePath);
    }
  }

  /**
   * @param $webformExportPath
   */
  private function importWebformByPath($webformExportPath) {
    $webformExportCode = file_get_contents($webformExportPath, "r");
    node_export_import($webformExportCode);
  }

  /**
   * Enable the discount fields on webforms
   */
  private function enableDiscountFieldsOnWebforms() {
    $allWebformsNodedIds = db_select("node", "n")
      ->fields("n", ["nid"])
      ->condition("type", "webform", "=")
      ->execute()
      ->fetchCol();

    foreach ($allWebformsNodedIds as $webformsNid) {
      $wf_me_discount_settings = new wf_me_discount_settings();
      $wf_me_discount_settings->save($webformsNid, 1);
    }
  }

  /**
   * Set default manual direct debit configurations
   */
  private function setDefaultManualDirectDebitConfigurations() {
    $configFields = [
      'manualdirectdebit_default_reference_prefix' => 'DD',
      'manualdirectdebit_minimum_reference_prefix_length' => 6,
      'manualdirectdebit_new_instruction_run_dates' => [1],
      'manualdirectdebit_payment_collection_run_dates' => [1],
      'manualdirectdebit_minimum_days_to_first_payment' => 2,
      'manualdirectdebit_days_in_advance_for_collection_reminder' => 3,
      'manualdirectdebit_batch_submission_queue_limit' => 50,
    ];
    Civi::settings()->add($configFields);
  }

}
