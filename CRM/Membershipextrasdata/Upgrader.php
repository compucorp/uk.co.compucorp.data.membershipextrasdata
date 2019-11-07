<?php

class CRM_Membershipextrasdata_Upgrader extends CRM_Membershipextrasdata_Upgrader_Base {

  private $createdMembershipTypesIdsMap;

  public function install() {
    $this->createMembershipTypes();
    $this->createSalesTaxFinancialAccount();
    $this->createPriceSetsAndFields();
    $this->createDDOriginatorNumber();
    $this->enableTaxAndInvoicingSetting();
  }

  private function createMembershipTypes() {
    $defaultOrgID = 1;
    $membershipOrgIds= $this->createMembershipOrgs();

    $sampleMembershipTypes = [
      [
        'name' => 'Standard Membership',
        'member_of_contact_id' => $defaultOrgID,
        'financial_type_id' => 'Member Dues',
        'duration_unit' => 'year',
        'duration_interval' => 1,
        'period_type' => 'rolling',
        'minimum_fee' => 50,
        'auto_renew' => 1,
        'visibility' => 'Public',
      ],
      [
        'name' => 'Advanced Membership',
        'member_of_contact_id' => $defaultOrgID,
        'financial_type_id' => 'Member Dues',
        'duration_unit' => 'year',
        'duration_interval' => 1,
        'period_type' => 'rolling',
        'minimum_fee' => 100,
        'auto_renew' => 1,
        'visibility' => 'Public',
      ],
      [
        'name' => 'Fixed Subscription',
        'member_of_contact_id' => $membershipOrgIds['Fixed Org'],
        'financial_type_id' => 'Member Dues',
        'duration_unit' => 'year',
        'duration_interval' => 1,
        'period_type' => 'fixed',
        'minimum_fee' => 100,
        'auto_renew' => 1,
        'visibility' => 'Public',
        'fixed_period_start_day' => 701,
        'fixed_period_rollover_day' => 630,
      ],
      [
        'name' => 'Membership Plus',
        'member_of_contact_id' => $membershipOrgIds['Addon Department'],
        'financial_type_id' => 'Member Dues',
        'duration_unit' => 'year',
        'duration_interval' => 1,
        'period_type' => 'rolling',
        'minimum_fee' => 24,
        'auto_renew' => 1,
        'visibility' => 'Public',
      ],
      [
        'name' => 'Journal - British Journal of Social Work',
        'member_of_contact_id' => $membershipOrgIds['Journal - British Journal of Social Work'],
        'financial_type_id' => 'Member Dues',
        'duration_unit' => 'month',
        'duration_interval' => 12,
        'period_type' => 'rolling',
        'minimum_fee' => 41,
        'auto_renew' => 1,
        'visibility' => 'Public',
      ],
      [
        'name' => 'Social Workers Union',
        'member_of_contact_id' => $membershipOrgIds['Social Workers Union'],
        'financial_type_id' => 'Member Dues',
        'duration_unit' => 'month',
        'duration_interval' => 12,
        'period_type' => 'rolling',
        'minimum_fee' => 20,
        'auto_renew' => 1,
        'visibility' => 'Public',
      ],
      [
        'name' => 'Lifetime',
        'member_of_contact_id' => $defaultOrgID,
        'financial_type_id' => 'Member Dues',
        'duration_unit' => 'lifetime',
        'duration_interval' => 1,
        'period_type' => 'rolling',
        'minimum_fee' => 1200,
        'auto_renew' => 0,
        'visibility' => 'Public',
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
      'Fixed Org',
      'Addon Department',
      'Journal - British Journal of Social Work',
      'Social Workers Union',
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
    $existingRecordResponse = civicrm_api3('PriceSet', 'get', [
      'sequential' => 1,
      'title' => 'Secondary Membership',
      'options' => ['limit' => 1],
    ]);

    if (empty($existingRecordResponse['id'])) {
      $createdRecordResponse = civicrm_api3('PriceSet', 'create', [
        'title' => 'Secondary Membership',
        'name' => 'secondary_membership',
        'extends' => 'CiviMember',
        'min_amount' => 100,
        'financial_type_id' => 'Member Dues',
        'is_active' => 1,
      ]);

      $priceSetId = $createdRecordResponse['id'];
    } else {
      $priceSetId = $existingRecordResponse['id'];
    }

    $membershipPriceFieldParams = [
      'price_set_id'=> $priceSetId,
      'label'=> 'Secondary Membership',
      'html_type'=> 'Radio',
      'is_enter_qty'=> 0,
      'weight'=> 1,
      'is_display_amounts'=> 1,
      'options_per_line'=> 1,
      'is_active'=> 1,
      'is_required'=> 1,
      'visibility_id'=> 1
    ];
    $createdMembershipPriceFieldResponse = civicrm_api3('PriceField', 'create', $membershipPriceFieldParams);
    if (!empty($createdMembershipPriceFieldResponse['id'])) {
      civicrm_api3('PriceFieldValue', 'create', [
        'price_field_id' => $createdMembershipPriceFieldResponse['id'],
        'label' => 'Fixed Subscription',
        'amount' => 100,
        'membership_type_id' => $this->createdMembershipTypesIdsMap['Fixed Subscription'],
        'financial_type_id' => 'Member Dues',
        'membership_num_terms' => 1,
        'non_deductible_amount' => 0,
      ]);
    }

    $addOnPriceFieldParams = [
      'price_set_id'=> $priceSetId,
      'label'=> 'Secondary Add ons',
      'html_type'=> 'CheckBox',
      'is_enter_qty'=> 0,
      'weight'=> 2,
      'is_display_amounts'=> 1,
      'options_per_line'=> 1,
      'is_active'=> 1,
      'is_required'=> 0,
      'visibility_id'=> 1
    ];
    $createdAddOnPriceFieldResponse = civicrm_api3('PriceField', 'create', $addOnPriceFieldParams);
    if (!empty($createdAddOnPriceFieldResponse['id'])) {
      civicrm_api3('PriceFieldValue', 'create', [
        'price_field_id' => $createdAddOnPriceFieldResponse['id'],
        'label' => 'Standard Membership',
        'amount' => 10,
        'membership_type_id' => $this->createdMembershipTypesIdsMap['Standard Membership'],
        'financial_type_id' => 'Member Dues',
        'membership_num_terms' => 1,
        'non_deductible_amount' => 0,
        'is_default' => 1,
      ]);

      civicrm_api3('PriceFieldValue', 'create', [
        'price_field_id' => $createdAddOnPriceFieldResponse['id'],
        'label' => 'Advanced Membership',
        'amount' => 120,
        'membership_type_id' => $this->createdMembershipTypesIdsMap['Advanced Membership'],
        'financial_type_id' => 'Member Dues',
        'membership_num_terms' => 1,
        'non_deductible_amount' => 0,
      ]);
    }
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

  private function enableTaxAndInvoicingSetting() {
    civicrm_api3('Setting', 'create', [
      'invoicing' => 1,
    ]);
  }
}
