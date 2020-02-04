<?php

use CRM_Membershipextrasdata_ExtensionUtil as ExtensionUtil;

class CRM_Membershipextrasdata_Upgrader extends CRM_Membershipextrasdata_Upgrader_Base {

  private $createdMembershipTypesIdsMap;

  public function install() {
    $this->createMembershipTypes();
    $this->createSalesTaxFinancialAccount();
    $this->createPriceSetsAndFields();
    $this->createDDOriginatorNumber();
    $this->createDiscountCodes();
    $this->createTestingContributionPages();
    $this->createTestingWebforms();
  }

  private function createMembershipTypes() {
    $membershipOrgIds= $this->createMembershipOrgs();
    $employerOfRelationshipTypeId = $this->getEmployerOfRelationshipTypeId();

    $sampleMembershipTypes = [
      [
        'name' => 'Individual annual rolling membership - Gold - 1 yr',
        'member_of_contact_id' => $membershipOrgIds['Demo organisation 1 - Individual rolling Gold - 1yr'],
        'financial_type_id' => 'Member Dues',
        'duration_unit' => 'year',
        'duration_interval' => 1,
        'period_type' => 'rolling',
        'minimum_fee' => 120,
        'auto_renew' => 1,
        'visibility' => 'Public',
      ],
      [
        'name' => 'Individual annual rolling membership - Silver - 1 yr',
        'member_of_contact_id' => $membershipOrgIds['Demo organisation 2 - Individual rolling Silver - 1yr'],
        'financial_type_id' => 'Member Dues',
        'duration_unit' => 'year',
        'duration_interval' => 1,
        'period_type' => 'rolling',
        'minimum_fee' => 90.45,
        'auto_renew' => 1,
        'visibility' => 'Public',
      ],
      [
        'name' => 'Individual annual rolling membership - Add-on1 - 1 yr',
        'member_of_contact_id' => $membershipOrgIds['Demo organisation 3 - Individual rolling Add-on1 - 1yr'],
        'financial_type_id' => 'Member Dues',
        'duration_unit' => 'year',
        'duration_interval' => 1,
        'period_type' => 'rolling',
        'minimum_fee' => 90,
        'auto_renew' => 1,
        'visibility' => 'Public',
      ],
      [
        'name' => 'Individual annual rolling membership - Add-on2 - 1 yr',
        'member_of_contact_id' => $membershipOrgIds['Demo organisation 4 - Individual rolling Add-on2 - 1yr'],
        'financial_type_id' => 'Member Dues',
        'duration_unit' => 'year',
        'duration_interval' => 1,
        'period_type' => 'rolling',
        'minimum_fee' => 90,
        'auto_renew' => 1,
        'visibility' => 'Public',
      ],
      [
        'name' => 'Individual annual rolling membership - Add-on3 - 1 yr',
        'member_of_contact_id' => $membershipOrgIds['Demo organisation 5 - Individual rolling Add-on3 - 1yr'],
        'financial_type_id' => 'Member Dues',
        'duration_unit' => 'year',
        'duration_interval' => 1,
        'period_type' => 'rolling',
        'minimum_fee' => 90,
        'auto_renew' => 1,
        'visibility' => 'Public',
      ],
      [
        'name' => 'Corporate annual rolling membership - Gold - 1 yr',
        'member_of_contact_id' => $membershipOrgIds['Demo organisation 6 - Corporate rolling - Gold - 1yr'],
        'financial_type_id' => 'Member Dues',
        'duration_unit' => 'year',
        'duration_interval' => 1,
        'period_type' => 'rolling',
        'minimum_fee' => 1200,
        'auto_renew' => 1,
        'visibility' => 'Public',
        'relationship_type_id' => $employerOfRelationshipTypeId,
        'relationship_direction' => 'b_a',
      ],
      [
        'name' => 'Corporate annual rolling membership - Silver - 1 yr',
        'member_of_contact_id' => $membershipOrgIds['Demo organisation 7 - Corporate rolling - Silver - 1yr'],
        'financial_type_id' => 'Member Dues',
        'duration_unit' => 'year',
        'duration_interval' => 1,
        'period_type' => 'rolling',
        'minimum_fee' => 900.45,
        'auto_renew' => 1,
        'visibility' => 'Public',
        'relationship_type_id' => $employerOfRelationshipTypeId,
        'relationship_direction' => 'b_a',
      ],
      [
        'name' => 'Corporate annual rolling membership - Add-on1 - 1 yr',
        'member_of_contact_id' => $membershipOrgIds['Demo organisation 8 - Corporate rolling - Add-on1 - 1yr'],
        'financial_type_id' => 'Member Dues',
        'duration_unit' => 'year',
        'duration_interval' => 1,
        'period_type' => 'rolling',
        'minimum_fee' => 900,
        'auto_renew' => 1,
        'visibility' => 'Public',
        'relationship_type_id' => $employerOfRelationshipTypeId,
        'relationship_direction' => 'b_a',
      ],
      [
        'name' => 'Corporate annual rolling membership - Add-on2 - 1 yr',
        'member_of_contact_id' => $membershipOrgIds['Demo organisation 9 - Corporate rolling - Add-on2 - 1yr'],
        'financial_type_id' => 'Member Dues',
        'duration_unit' => 'year',
        'duration_interval' => 1,
        'period_type' => 'rolling',
        'minimum_fee' => 900,
        'auto_renew' => 1,
        'visibility' => 'Public',
        'relationship_type_id' => $employerOfRelationshipTypeId,
        'relationship_direction' => 'b_a',
      ],
      [
        'name' => 'Corporate annual rolling membership - Add-on3 - 1 yr',
        'member_of_contact_id' => $membershipOrgIds['Demo organisation 10 - Corporate rolling - Add-on3 - 1yr'],
        'financial_type_id' => 'Member Dues',
        'duration_unit' => 'year',
        'duration_interval' => 1,
        'period_type' => 'rolling',
        'minimum_fee' => 900,
        'auto_renew' => 1,
        'visibility' => 'Public',
        'relationship_type_id' => $employerOfRelationshipTypeId,
        'relationship_direction' => 'b_a',
      ],
    ];

    foreach ($sampleMembershipTypes as $membershipTypeParams) {
      $existingRecordResponse = civicrm_api3('MembershipType', 'get', [
        'sequential' => 1,
        'options' => ['limit' => 1],
        'name' => $membershipTypeParams['name'],
      ]);

      if (empty($existingRecordResponse['id'])) {
        $response = civicrm_api3('MembershipType', 'create', $membershipTypeParams);
        $membershipTypeId = $response['id'];
      } else {
        $membershipTypeId = $existingRecordResponse['id'];
      }

      $this->createdMembershipTypesIdsMap[$membershipTypeParams['name']] = $membershipTypeId;
    }
  }

  private function createMembershipOrgs() {
    $orgsToCreate = [
      'Demo organisation 1 - Individual rolling Gold - 1yr',
      'Demo organisation 2 - Individual rolling Silver - 1yr',
      'Demo organisation 3 - Individual rolling Add-on1 - 1yr',
      'Demo organisation 4 - Individual rolling Add-on2 - 1yr',
      'Demo organisation 5 - Individual rolling Add-on3 - 1yr',
      'Demo organisation 6 - Corporate rolling - Gold - 1yr',
      'Demo organisation 7 - Corporate rolling - Silver - 1yr',
      'Demo organisation 8 - Corporate rolling - Add-on1 - 1yr',
      'Demo organisation 9 - Corporate rolling - Add-on2 - 1yr',
      'Demo organisation 10 - Corporate rolling - Add-on3 - 1yr'
    ];

    $orgsIds = [];
    foreach ($orgsToCreate as $orgName) {
      $existingRecordResponse = civicrm_api3('Contact', 'get', [
        'sequential' => 1,
        'options' => ['limit' => 1],
        'contact_type' => 'Organization',
        'organization_name' => $orgName,
      ]);

      if (empty($existingRecordResponse['id'])) {
        $createdRecordResponse = civicrm_api3('Contact', 'create', [
          'contact_type' => 'Organization',
          'organization_name' => $orgName,
        ]);

        $orgsIds[$orgName] = $createdRecordResponse['id'];
      } else {
        $orgsIds[$orgName] = $existingRecordResponse['id'];
      }
    }

    return $orgsIds;
  }

  private function getEmployerOfRelationshipTypeId() {
    $result = civicrm_api3('RelationshipType', 'get', [
      'sequential' => 1,
      'name_b_a' => 'Employer of',
    ]);

    if (!empty($result['id'])) {
      return $result['id'];
    }

    return NULL;
  }

  private function createSalesTaxFinancialAccount() {
    $existingRecordResponse = civicrm_api3('FinancialAccount', 'get', [
      'sequential' => 1,
      'options' => ['limit' => 1],
      'name' => 'Sales Tax',
    ]);

    if (empty($existingRecordResponse['id'])) {
      civicrm_api3('FinancialAccount', 'create', [
        'name' => 'Sales Tax',
        'contact_id' => 1,
        'financial_account_type_id' => 'Liability',
        'accounting_code' => 5500,
        'is_header_account' => 0,
        'is_deductible' => 1,
        'is_tax' => 1,
        'tax_rate' => 20,
        'is_active' => 1,
        'is_default' => 0,
      ]);

      $memberDuesFinancialTypeId = 2;
      civicrm_api3('EntityFinancialAccount', 'create', [
        'financial_account_id' => 'Sales Tax',
        'entity_table' => 'civicrm_financial_type',
        'entity_id' => $memberDuesFinancialTypeId,
        'account_relationship' => 'Sales Tax Account is',
      ]);
    }
  }

  private function createPriceSetsAndFields() {
    $this->createIndividualMembershipPriceSet();
    $this->createCorporateMembershipPriceSet();
  }

  private function createIndividualMembershipPriceSet() {
    $existingRecordResponse = civicrm_api3('PriceSet', 'get', [
      'return' => ['id'],
      'sequential' => 1,
      'title' => 'Individual Membership',
      'options' => ['limit' => 1],
    ]);

    if (!empty($existingRecordResponse['id'])) {
      return;
    }

    $createdRecordResponse = civicrm_api3('PriceSet', 'create', [
      'title' => 'Individual Membership',
      'name' => 'FIELD2',
      'extends' => 'CiviMember',
      'min_amount' => 100,
      'financial_type_id' => 'Member Dues',
      'is_active' => 1,
    ]);
    $priceSetId = $createdRecordResponse['id'];

    $createdStandardPriceFieldResponse = civicrm_api3('PriceField', 'create', [
      'price_set_id'=> $priceSetId,
      'name' => 'field1',
      'label'=> 'Standard',
      'html_type'=> 'Select',
      'is_enter_qty'=> 0,
      'weight'=> 1,
      'is_display_amounts'=> 1,
      'options_per_line'=> 1,
      'is_active'=> 1,
      'is_required'=> 0,
      'visibility_id'=> 1
    ]);
    civicrm_api3('PriceFieldValue', 'create', [
      'price_field_id' => $createdStandardPriceFieldResponse['id'],
      'label' => 'Individual annual rolling membership - Gold - 1 yr',
      'name' => 'Individual_annual_rolling_membership_Gold_1_yr',
      'amount' => 120,
      'membership_type_id' => $this->createdMembershipTypesIdsMap['Individual annual rolling membership - Gold - 1 yr'],
      'financial_type_id' => 'Member Dues',
      'membership_num_terms' => 1,
      'non_deductible_amount' => 0,
    ]);
    civicrm_api3('PriceFieldValue', 'create', [
      'price_field_id' => $createdStandardPriceFieldResponse['id'],
      'label' => 'Individual annual rolling membership - Silver - 1 yr',
      'name' => 'Individual_annual_rolling_membership_Silver_1_yr',
      'amount' => 90.45,
      'membership_type_id' => $this->createdMembershipTypesIdsMap['Individual annual rolling membership - Silver - 1 yr'],
      'financial_type_id' => 'Member Dues',
      'membership_num_terms' => 1,
      'non_deductible_amount' => 0,
    ]);

    $createdAddOnPriceFieldResponse = civicrm_api3('PriceField', 'create', [
      'price_set_id'=> $priceSetId,
      'label'=> 'Add On',
      'name' => 'add_on',
      'html_type'=> 'CheckBox',
      'is_enter_qty'=> 0,
      'weight'=> 2,
      'is_display_amounts'=> 1,
      'options_per_line'=> 1,
      'is_active'=> 1,
      'is_required'=> 0,
      'visibility_id'=> 1
    ]);

    civicrm_api3('PriceFieldValue', 'create', [
      'price_field_id' => $createdAddOnPriceFieldResponse['id'],
      'label' => 'Individual annual rolling membership - Add-on1 - 1 yr',
      'name' => 'Individual_annual_rolling_membership_Add_on1_1_yr',
      'amount' => 90,
      'membership_type_id' => $this->createdMembershipTypesIdsMap['Individual annual rolling membership - Add-on1 - 1 yr'],
      'financial_type_id' => 'Member Dues',
      'membership_num_terms' => 1,
      'non_deductible_amount' => 0,
      'is_default' => 1,
    ]);
    civicrm_api3('PriceFieldValue', 'create', [
      'price_field_id' => $createdAddOnPriceFieldResponse['id'],
      'label' => 'Individual annual rolling membership - Add-on2 - 1 yr',
      'name' => 'Individual_annual_rolling_membership_Add_on2_1_yr',
      'amount' => 90,
      'membership_type_id' => $this->createdMembershipTypesIdsMap['Individual annual rolling membership - Add-on2 - 1 yr'],
      'financial_type_id' => 'Member Dues',
      'membership_num_terms' => 1,
      'non_deductible_amount' => 0,
      'is_default' => 1,
    ]);
    civicrm_api3('PriceFieldValue', 'create', [
      'price_field_id' => $createdAddOnPriceFieldResponse['id'],
      'label' => 'Individual annual rolling membership - Add-on3 - 1 yr',
      'name' => 'individual_annual_rolling_membership_add_on3_1_yr',
      'amount' => 90,
      'membership_type_id' => $this->createdMembershipTypesIdsMap['Individual annual rolling membership - Add-on3 - 1 yr'],
      'financial_type_id' => 'Member Dues',
      'membership_num_terms' => 1,
      'non_deductible_amount' => 0,
      'is_default' => 1,
    ]);
  }

  private function createCorporateMembershipPriceSet() {
    $existingRecordResponse = civicrm_api3('PriceSet', 'get', [
      'return' => ['id'],
      'sequential' => 1,
      'title' => 'Corporate Membership',
      'options' => ['limit' => 1],
    ]);

    if (!empty($existingRecordResponse['id'])) {
      return;
    }

    $createdRecordResponse = civicrm_api3('PriceSet', 'create', [
      'title' => 'Corporate Membership',
      'name' => 'Corporate_Membership',
      'extends' => 'CiviMember',
      'min_amount' => 100,
      'financial_type_id' => 'Member Dues',
      'is_active' => 1,
    ]);
    $priceSetId = $createdRecordResponse['id'];

    $createdMainPriceFieldResponse = civicrm_api3('PriceField', 'create', [
      'price_set_id'=> $priceSetId,
      'name' => 'main',
      'label'=> 'Main',
      'html_type'=> 'Select',
      'is_enter_qty'=> 0,
      'weight'=> 1,
      'is_display_amounts'=> 1,
      'options_per_line'=> 1,
      'is_active'=> 1,
      'is_required'=> 0,
      'visibility_id'=> 1
    ]);
    civicrm_api3('PriceFieldValue', 'create', [
      'price_field_id' => $createdMainPriceFieldResponse['id'],
      'label' => 'Corporate annual rolling membership - Gold - 1 yr',
      'name' => 'Corporate_annual_rolling_membership_Gold_1_yr',
      'amount' => 1200,
      'membership_type_id' => $this->createdMembershipTypesIdsMap['Corporate annual rolling membership - Gold - 1 yr'],
      'financial_type_id' => 'Member Dues',
      'membership_num_terms' => 1,
      'non_deductible_amount' => 0,
    ]);
    civicrm_api3('PriceFieldValue', 'create', [
      'price_field_id' => $createdMainPriceFieldResponse['id'],
      'label' => 'Corporate annual rolling membership - Silver - 1 yr',
      'name' => 'Corporate_annual_rolling_membership_Silver_1_yr',
      'amount' => 900.45,
      'membership_type_id' => $this->createdMembershipTypesIdsMap['Corporate annual rolling membership - Silver - 1 yr'],
      'financial_type_id' => 'Member Dues',
      'membership_num_terms' => 1,
      'non_deductible_amount' => 0,
    ]);

    $createdAddOnPriceFieldResponse = civicrm_api3('PriceField', 'create', [
      'price_set_id'=> $priceSetId,
      'label'=> 'Add-On',
      'name' => 'add_on',
      'html_type'=> 'CheckBox',
      'is_enter_qty'=> 0,
      'weight'=> 2,
      'is_display_amounts'=> 1,
      'options_per_line'=> 1,
      'is_active'=> 1,
      'is_required'=> 0,
      'visibility_id'=> 1
    ]);

    civicrm_api3('PriceFieldValue', 'create', [
      'price_field_id' => $createdAddOnPriceFieldResponse['id'],
      'label' => 'Corporate annual rolling membership - Add-on1 - 1 yr',
      'name' => 'Corporate_annual_rolling_membership_Add_on1_1_yr',
      'amount' => 900,
      'membership_type_id' => $this->createdMembershipTypesIdsMap['Corporate annual rolling membership - Add-on1 - 1 yr'],
      'financial_type_id' => 'Member Dues',
      'membership_num_terms' => 1,
      'non_deductible_amount' => 0,
      'is_default' => 1,
    ]);
    civicrm_api3('PriceFieldValue', 'create', [
      'price_field_id' => $createdAddOnPriceFieldResponse['id'],
      'label' => 'Corporate annual rolling membership - Add-on2 - 1 yr',
      'name' => 'Corporate_annual_rolling_membership_Add_on2_1_yr',
      'amount' => 900,
      'membership_type_id' => $this->createdMembershipTypesIdsMap['Corporate annual rolling membership - Add-on2 - 1 yr'],
      'financial_type_id' => 'Member Dues',
      'membership_num_terms' => 1,
      'non_deductible_amount' => 0,
      'is_default' => 1,
    ]);
    civicrm_api3('PriceFieldValue', 'create', [
      'price_field_id' => $createdAddOnPriceFieldResponse['id'],
      'label' => 'Corporate annual rolling membership - Add-on3 - 1 yr',
      'name' => 'corporate_annual_rolling_membership_add_on3_1_yr',
      'amount' => 900,
      'membership_type_id' => $this->createdMembershipTypesIdsMap['Corporate annual rolling membership - Add-on3 - 1 yr'],
      'financial_type_id' => 'Member Dues',
      'membership_num_terms' => 1,
      'non_deductible_amount' => 0,
      'is_default' => 1,
    ]);
  }

  private function createDDOriginatorNumber() {
    $ddNumbersToCreate = [
      '01',
      '02',
    ];

    foreach ($ddNumbersToCreate as $ddNumberName) {
      civicrm_api3('OptionValue', 'create', [
        'option_group_id' => 'direct_debit_originator_number',
        'label' => $ddNumberName,
      ]);
    }
  }

  private function createDiscountCodes() {
    $response = civicrm_api3('DiscountCode', 'get', [
      'sequential' => 1,
      'return' => ['id'],
      'code' => '50Percent',
    ]);
    if(empty($response['id'])) {
      civicrm_api3('DiscountCode', 'create', [
        'code' => '50Percent',
        'amount_type' => 1,
        'amount' => 50,
        'count_max' => 0,
        'description' => '50 Percent Discount',
        'is_active' => 1
      ]);
    }

    $response = civicrm_api3('DiscountCode', 'get', [
      'sequential' => 1,
      'return' => ['id'],
      'code' => '50Fixed',
    ]);
    if(empty($response['id'])) {
      civicrm_api3('DiscountCode', 'create', [
        'code' => '50Fixed',
        'amount_type' => 2,
        'amount' => 50,
        'count_max' => 0,
        'description' => '50 Pound Discount',
        'is_active' => 1,
      ]);
    }
  }

  private function createTestingContributionPages() {
    $this->createOfflinePaymentProcessorMembershipSignupContributionPage();
    $this->createDDProcessorMembershipSignupContributionPage();
  }

  private function createOfflinePaymentProcessorMembershipSignupContributionPage() {
    civicrm_api3('ContributionPage', 'create', [
      'title' => 'Membership Signup and renewal-Offline PaymentProcessor',
      'financial_type_id' => 2,
      'payment_processor' => $this->getActivePaymentProcessors(),
      'is_credit_card_only' => 0,
      'is_monetary' => 1,
      'is_active' => 1,
      'is_recur' => 0,
      'is_confirm_enabled' => 1,
      'is_recur_interval' => 0,
      'is_recur_installments' => 0,
      'adjust_recur_start_date' => 0,
      'is_pay_later' => 0,
      'pay_later_text' => 'I will send payment by check',
      'is_partial_payment' => 0,
      'is_allow_other_amount' => 1,
      'is_email_receipt' => 1,
      'receipt_from_name' => 'Compuclient example receipt',
      'receipt_from_email' => 'email@example.com',
      'amount_block_is_active' => 0,
      'currency' => 'GBP',
      'is_share' => 0,
      'is_billing_required' => 0,
      'start_date' => date('Y-m-d'),
    ]);
  }

  private function createDDProcessorMembershipSignupContributionPage() {
    civicrm_api3('ContributionPage', 'create', [
      'title' => 'Membership signup and renewal-Direct Debit',
      'financial_type_id' => 2,
      'payment_processor' => $this->getActivePaymentProcessors(),
      'is_credit_card_only' => 0,
      'is_monetary' => 1,
      'is_active' => 1,
      'is_recur' => 0,
      'is_confirm_enabled' => 1,
      'is_recur_interval' => 0,
      'is_recur_installments' => 0,
      'adjust_recur_start_date' => 0,
      'is_pay_later' => 0,
      'pay_later_text' => 'I will send payment by check',
      'is_partial_payment' => 0,
      'is_allow_other_amount' => 1,
      'is_email_receipt' => 1,
      'receipt_from_name' => 'Compuclient example receipt',
      'receipt_from_email' => 'email@example.com',
      'amount_block_is_active' => 0,
      'currency' => 'GBP',
      'is_share' => 0,
      'is_billing_required' => 0,
      'start_date' => date('Y-m-d'),
    ]);
  }

  /**
   * Return all active payment processor names.
   */
  private function getActivePaymentProcessors() {
    $paymentProcessorNames = [];

    $activePaymentProcessors = civicrm_api3('PaymentProcessor', 'get', [
      'sequential' => 1,
      'return' => ['name'],
      'is_test' => 0,
      'is_active' => 1,
    ]);

    if(!$activePaymentProcessors['is_error']) {
      foreach ($activePaymentProcessors['values'] as $paymentProcessor) {
        $paymentProcessorNames[] = $paymentProcessor['name'];
      }
    }

    return $paymentProcessorNames;
  }

  private function createTestingWebforms() {
    $webformExportsDirectoryName = ExtensionUtil::path('WebformExport');
    $exportFiles = array_diff(scandir($webformExportsDirectoryName), ['.', '..']);
    foreach ($exportFiles as $fileName) {
      $filePath = $webformExportsDirectoryName . '/' . $fileName;
      $this->importWebformByPath($filePath);
    }
  }

  private function importWebformByPath($webformExportPath) {
    $webformExportCode = file_get_contents($webformExportPath, 'r');
    node_export_import($webformExportCode);
  }

}
