# Church Contributions and Payment Plan

## Purpose

This document explains a scalable plan for handling church contributions that are connected to services, sacraments, and future external payment integration.

The goal is to support:

- contributions for pastoral services
- contributions for sacraments such as baptism and marriage
- contributions for shared sacrament programs such as communion and confirmation
- manual office payment recording
- future NMB API integration for control number generation

This is a planning document only. It does not mean the feature has been implemented yet.

## Main Design Idea

The safest design is to keep contributions as their own layer beside the service and sacrament workflows.

That means:

- service and sacrament records continue to store church workflow data
- contribution setup defines what should be paid
- contribution obligations store what a real member or family owes
- payment records store what has actually been paid
- future control-number requests can be added without redesigning the whole system

This avoids hard coding fixed amounts inside controllers or frontend pages.

## Why This Approach Fits The Current System

The current project already has separate workflow areas:

- pastoral service requests
- dedicated sacrament flows such as baptism and marriage
- shared sacrament registration flows such as communion and confirmation

Because of that, contributions should not be forced into one single church record table. Instead, the contribution layer should attach to whichever workflow created the payment need.

## Recommended Data Structure

### 1. Contribution Catalogs

This is the master list of contribution types.

Examples:

- Baptism Offering
- Marriage Preparation Fee
- Mass Intention Contribution
- Confirmation Program Fee

Suggested columns:

- `id`
- `uuid`
- `parish_id`
- `name`
- `code`
- `description`
- `is_active`
- `created_by_user_id`
- `updated_by_user_id`

Meaning of the main columns:

- `name`: the contribution name shown to users
- `code`: short internal code such as `BAPT-OFF`
- `description`: explains what the contribution is for
- `is_active`: whether the contribution type is still available for use

### 2. Contribution Rules

This table defines where a contribution applies and how it behaves.

Examples:

- baptism offering required for baptism requests
- marriage file fee required for marriage cases
- confirmation program contribution required for a specific cycle

Suggested columns:

- `id`
- `uuid`
- `parish_id`
- `contribution_catalog_id`
- `pastoral_service_category_id`
- `applies_to_type`
- `applies_to_id`
- `amount`
- `currency_code`
- `is_required`
- `allow_partial_payment`
- `allow_override`
- `waiver_allowed`
- `completion_policy`
- `effective_from`
- `effective_to`
- `sort_order`
- `is_active`
- `created_by_user_id`
- `updated_by_user_id`

Meaning of the main columns:

- `contribution_catalog_id`: which contribution type this rule belongs to
- `pastoral_service_category_id`: used when the contribution belongs to a pastoral service category
- `applies_to_type`: the type of source this rule applies to, for example `baptism`, `marriage`, or `program_cycle`
- `applies_to_id`: the exact related record id if needed
- `amount`: expected amount to be paid
- `currency_code`: currency such as `TZS`
- `is_required`: whether the contribution is mandatory
- `allow_partial_payment`: whether installments are allowed
- `allow_override`: whether staff can change the amount in special cases
- `waiver_allowed`: whether staff can excuse the payment
- `completion_policy`: when payment matters in workflow
- `effective_from`: date when the rule starts applying
- `effective_to`: date when the rule stops applying
- `sort_order`: order to display if more than one contribution appears

Recommended values for `completion_policy`:

- `advisory`: payment is encouraged but does not block workflow
- `required_before_completion`: payment must be settled before the service can be completed
- `required_before_certificate_issue`: payment must be settled before a certificate or official output is issued

### 3. Contribution Obligations

This table stores the real payment obligation created for a real case.

This is important because contribution rules may change later. Old requests should keep the amount and rule details that were active when the obligation was created.

Suggested columns:

- `id`
- `uuid`
- `parish_id`
- `contribution_catalog_id`
- `source_type`
- `source_id`
- `subject_member_id`
- `payer_member_id`
- `family_id`
- `rule_snapshot_name`
- `rule_snapshot_code`
- `rule_snapshot_amount`
- `currency_code`
- `amount_due`
- `amount_paid`
- `balance`
- `status`
- `due_date`
- `notes`
- `created_by_user_id`
- `updated_by_user_id`

Meaning of the main columns:

- `source_type`: what created this obligation, such as `pastoral_service_request_item`, `baptism`, `marriage`, or `sacrament_program_registration`
- `source_id`: the exact source record id
- `subject_member_id`: the member receiving the service
- `payer_member_id`: the member expected to make payment
- `family_id`: optional field if the obligation belongs to a family
- `rule_snapshot_name`: contribution name copied at the time the obligation was created
- `rule_snapshot_code`: contribution code copied at that time
- `rule_snapshot_amount`: original configured amount copied at that time
- `amount_due`: total amount expected
- `amount_paid`: amount already paid so far
- `balance`: remaining amount
- `status`: for example `pending`, `partial`, `paid`, `waived`, or `cancelled`
- `due_date`: payment deadline if the church uses one

## Why Member Linking Matters

The system should not assume that one person plays all roles.

In church workflows, these can be different:

- the member receiving the service
- the member requesting the service
- the member paying for the service

Example:

- a child may be the one receiving baptism
- the parent may be the one paying
- a jumuiya leader may be the one who submitted the request

Because of that, the design should keep these roles clear:

- `subject_member_id`
- `payer_member_id`
- `requested_by_member_id`

This will prevent confusion now and make future payment integration much easier.

### 4. Contribution Transactions

This table stores actual money or financial adjustments recorded against an obligation.

Suggested columns:

- `id`
- `uuid`
- `parish_id`
- `contribution_obligation_id`
- `member_id`
- `transaction_type`
- `amount`
- `payment_method`
- `reference_no`
- `paid_at`
- `recorded_by_user_id`
- `notes`

Meaning of the main columns:

- `contribution_obligation_id`: which obligation this transaction belongs to
- `member_id`: who actually made the payment
- `transaction_type`: for example `payment`, `waiver`, `refund`, or `adjustment`
- `amount`: transaction amount
- `payment_method`: for example `cash`, `bank`, `mobile`, or `control_number`
- `reference_no`: receipt number, bank reference, or external payment reference
- `paid_at`: date and time payment happened
- `recorded_by_user_id`: staff user who recorded it

This table allows:

- full payment
- partial payment
- waiver
- refund
- correction or adjustment

## Future NMB API Integration

If the system may later integrate with NMB to generate control numbers, the design should not treat control number generation as the same thing as payment completion.

Those are two different stages.

Recommended future structure:

### 5. Payment Intents or Control Number Requests

This table stores the request sent to the external payment provider.

Suggested columns:

- `id`
- `uuid`
- `parish_id`
- `contribution_obligation_id`
- `payer_member_id`
- `provider_name`
- `requested_amount`
- `currency_code`
- `control_number`
- `provider_reference`
- `provider_payload`
- `status`
- `expires_at`
- `paid_at`
- `cancelled_at`
- `created_by_user_id`
- `updated_by_user_id`

Meaning of the main columns:

- `contribution_obligation_id`: which church obligation this request belongs to
- `payer_member_id`: member expected to pay using the generated control number
- `provider_name`: external provider name such as `NMB`
- `requested_amount`: amount sent to the provider
- `control_number`: generated control number returned by provider
- `provider_reference`: provider transaction or request reference
- `provider_payload`: raw provider response for auditing or troubleshooting
- `status`: for example `pending`, `paid`, `expired`, or `cancelled`
- `expires_at`: when the control number stops being valid

Recommended flow:

1. A contribution obligation is created.
2. The office or system requests a control number from NMB.
3. A payment intent or control number request record is stored.
4. When payment is confirmed, a contribution transaction is created or updated.
5. The obligation totals and status are updated.

## Workflow Recommendations

### Pastoral Services

When a pastoral service request item is created or submitted:

- check for active contribution rules for that service category
- create one or more contribution obligations
- link them to the specific service request item

This is best because one request may contain many families and many service items.

### Baptism and Marriage

These are richer dedicated sacrament records in the current system.

Their contributions should attach directly to the baptism or marriage record, not be forced through the pastoral service request tables.

### Communion and Confirmation

These already use shared registration and cycle logic.

Their contribution rules should be attachable to:

- the program cycle
- the registration

This supports changing contribution amounts from one intake cycle to another.

## Recommended Status Values

For contribution obligations:

- `pending`
- `partial`
- `paid`
- `waived`
- `cancelled`

For payment intents or control number requests:

- `pending`
- `paid`
- `expired`
- `cancelled`
- `failed`

For contribution transactions:

- `payment`
- `waiver`
- `refund`
- `adjustment`

## Benefits Of This Plan

This design is scalable because:

- new church services can be added through rules instead of code rewrites
- one service can have multiple contribution lines
- amounts can change over time using effective dates
- old obligations keep their original amounts through snapshot fields
- manual office payment works immediately
- future control-number and bank integration can be added safely
- reporting becomes easier because obligations and payments are clearly separated

## Recommended Implementation Phases

### Phase 1

Build the core contribution layer:

- contribution catalogs
- contribution rules
- contribution obligations
- contribution transactions

Support:

- manual setup of contribution rules
- automatic obligation creation
- manual office payment recording
- balance and status tracking

### Phase 2

Add workflow control and administration:

- waivers
- partial payment handling
- approval rules
- due dates
- contribution reports by service, member, family, and parish

### Phase 3

Add external payment integration:

- payment intents or control number request records
- NMB API connection
- payment confirmation callback handling
- automatic update of obligation and transaction status

## Final Recommendation

The best long-term design is:

- keep church workflow records separate from payment records
- attach contributions to the real source record that created the obligation
- always link payment to the correct member or family role
- prepare now for external control-number payment without forcing it into the first version

This gives the parish a clean manual process now and a safe upgrade path later when NMB integration is ready.
