# Invoice Ninja schema cheat sheet for IDE agents

This document summarizes the relational schema defined in `database/schema/mysql-schema.sql` so tooling can reason about entity relationships without scanning the full file.

## Where the schema lives
- Primary dump: `database/schema/mysql-schema.sql` contains the full MySQL DDL. 【F:database/schema/mysql-schema.sql†L1-L56】
- Historical snapshots: `database/schema/db-ninja-01-schema.sql`, `db-ninja-02-schema.sql`, and `db-ninja-03-schema.sql` mirror earlier versions.
- Laravel migrations live under `database/migrations/` for incremental changes.

## Multi-tenant core
- **Accounts** describe the top-level subscription context and default company mapping. Key fields include `plan`, `plan_term`, `default_company_id`, tracking columns, and marketing attribution. 【F:database/schema/mysql-schema.sql†L10-L55】
- **Companies** belong to accounts and drive feature flags, portal settings, SMTP options, and tax behaviour. The table enforces a unique `company_key` and tracks many toggles such as `track_inventory`, `default_auto_bill`, and `enable_modules`. 【F:database/schema/mysql-schema.sql†L468-L586】
- **Users** belong to accounts and store login data, 2FA secrets, OAuth metadata, and custom values. Email must be unique, and each user links back to its account. 【F:database/schema/mysql-schema.sql†L2274-L2325】
- **Company-user link**: `company_user` captures per-company permissions/roles for users and includes foreign keys back to `companies` and `users`. 【F:database/schema/mysql-schema.sql†L686-L713】
- **Tokens**: `company_tokens` provide scoped API credentials tied to a user and company, with throttling metadata. 【F:database/schema/mysql-schema.sql†L660-L685】

## Client domain
- **Clients** represent customers tied to a company/user with billing and shipping addresses, balances, VAT data, and custom fields. Unique per-company `number` values are enforced. 【F:database/schema/mysql-schema.sql†L401-L463】
- **Client contacts** store named email/phone contacts for a client and track portal auth fields like `password`, `contact_key`, and confirmation flags. 【F:database/schema/mysql-schema.sql†L295-L345】
- **Client gateway tokens** persist payment tokens per contact and gateway, allowing recurring or saved payment methods. 【F:database/schema/mysql-schema.sql†L346-L370】
- **Client subscriptions** connect clients to `subscriptions`, `recurring_invoices`, and invoices for billing events. 【F:database/schema/mysql-schema.sql†L371-L395】

## Product catalog
- **Products** are company-owned items/services with `product_key`, `cost`, `price`, inventory quantity, taxes, and custom values. Optional foreign keys connect to projects or vendors. 【F:database/schema/mysql-schema.sql†L1436-L1455】
- **Expense categories** and **expenses** track vendor costs, taxes, currencies, and reimbursable flags that can be invoiced. 【F:database/schema/mysql-schema.sql†L952-L1031】

## Sales documents
- **Invoices** link to clients, companies, users, projects, and optional recurring sources. They hold line items, taxes, due dates, reminders, custom surcharges, and balance/amount tracking; uniqueness is enforced per company/number. 【F:database/schema/mysql-schema.sql†L1143-L1223】
- **Invoice invitations** provide per-contact access keys and delivery metadata for an invoice. They reference invoices, client contacts, users, and companies. 【F:database/schema/mysql-schema.sql†L1106-L1137】
- **Quotes** mirror invoices but use the `quotes` and `quote_invitations` tables for draft/acceptance flows. 【F:database/schema/mysql-schema.sql†L1671-L1719】
- **Credits** represent client credits/overpayments with balances that can be applied to invoices. 【F:database/schema/mysql-schema.sql†L777-L852】
- **Purchase orders** and **purchase_order_invitations** support vendor-facing purchasing documents analogous to invoices. 【F:database/schema/mysql-schema.sql†L1555-L1625】

## Recurring and subscription flows
- **Recurring invoices** and **recurring_invoice_invitations** store schedules (`next_send_date`) and templates used to spawn invoices automatically. 【F:database/schema/mysql-schema.sql†L1850-L1927】
- **Recurring quotes** and **recurring_quote_invitations** provide the same pattern for quotes. 【F:database/schema/mysql-schema.sql†L1963-L2036】
- **Subscriptions** define plan-style offerings with frequencies, pricing, and trial data; clients connect via `client_subscriptions`. 【F:database/schema/mysql-schema.sql†L2070-L2114】

## Payments
- **Payments** tie companies, clients, gateways, and invitations together while tracking status, amounts (`amount`, `refunded`, `applied`), currency, and metadata. Company-level uniqueness is enforced on `number` and `idempotency_key`. 【F:database/schema/mysql-schema.sql†L1375-L1431】
- **Paymentables** link a payment to target entities (e.g., invoices or credits) with amounts and refunds, enabling partial allocations. 【F:database/schema/mysql-schema.sql†L1353-L1370】
- **Payment hashes**, **payment libraries**, **payment terms**, and **payment types** supply supporting metadata for gateways and scheduling. 【F:database/schema/mysql-schema.sql†L1295-L1351】

## Projects and tasks
- **Projects** associate with clients and companies, track budgets, rates, and identifiers, and drive task grouping. 【F:database/schema/mysql-schema.sql†L1483-L1517】
- **Tasks** carry time logs, descriptions, billable flags, and rate calculations; tasks can link to projects, clients, and invoices. 【F:database/schema/mysql-schema.sql†L2161-L2207】
- **Task statuses** define per-company status presets for tasks. 【F:database/schema/mysql-schema.sql†L2139-L2160】

## Vendors and purchasing
- **Vendors** store supplier details, addresses, and tax data for a company. 【F:database/schema/mysql-schema.sql†L2382-L2430】
- **Vendor contacts** mirror client contacts for vendor logins/notifications. 【F:database/schema/mysql-schema.sql†L2330-L2365】

## Banking
- **Bank integrations** model individual external bank accounts connected to a company. Each record links the account to the owning account/company/user, captures provider identifiers (`provider_name`, `provider_id`, `nordigen_*`), and stores account metadata such as `bank_account_id`, `bank_account_name`, masked `bank_account_number`, status/type, currency, balance, nickname, sync window (`from_date`), and flags for upstream disablement or auto-sync. Soft deletes and an `is_deleted` flag mirror other entities. 【F:database/schema/mysql-schema.sql†L151-L183】
- **Bank transactions** store imported ledger rows for a specific `bank_integration_id`. Rows carry amount and currency data, categorization (`category_id`, `ninja_category_id`, `category_type`, `base_type`), account type, vendor linkage, invoice/expense matching fields (`invoice_ids`, `expense_id`), participant strings, and an optional `bank_transaction_rule_id` or matched `payment_id`. A foreign-keyed `bank_account_id` mirrors the upstream account identifier, while `transaction_id` represents the provider's transaction key. 【F:database/schema/mysql-schema.sql†L238-L278】
- **Bank transaction rules** define matching logic and auto-actions for imported transactions. Rules include serialized criteria (`rules`), matching mode (`matches_on_all`), target type (`applies_to`), optional client/vendor/category targets, and action preference (`on_credit_match` = `create_payment` or `link_payment`). 【F:database/schema/mysql-schema.sql†L211-L233】
- **Banks** provide static provider metadata, while **bank companies** and **bank subcompanies** associate company/user credentials and institution-specific details such as account names or numbers. These records anchor integrations to known banking institutions. 【F:database/schema/mysql-schema.sql†L127-L206】【F:database/schema/mysql-schema.sql†L283-L290】
- **Bank integrations** relate to payments through downstream matching: when a transaction is linked to an expense or payment, the corresponding `payment_id`/`expense_id` columns and `status_id` track reconciliation state. Use `bank_transactions.bank_integration_id` to join back to the bank account-level metadata. 【F:database/schema/mysql-schema.sql†L238-L278】

## Supporting tables
- **Documents** attach uploads to various entities via polymorphic IDs and include hash metadata for integrity. 【F:database/schema/mysql-schema.sql†L916-L951】
- **Group settings** allow configuration bundles that clients or companies can inherit. 【F:database/schema/mysql-schema.sql†L1078-L1096】
- **Designs** hold PDF/email templates referenced by invoices/quotes. 【F:database/schema/mysql-schema.sql†L893-L915】
- **Localization** tables such as `countries`, `currencies`, `languages`, `date_formats`, `datetime_formats`, and `timezones` drive formatting and selection lists. 【F:database/schema/mysql-schema.sql†L715-L890】【F:database/schema/mysql-schema.sql†L855-L899】【F:database/schema/mysql-schema.sql†L1240-L1245】【F:database/schema/mysql-schema.sql†L2229-L2237】
- **System logs** and **activities** capture audit trails with polymorphic references to many entities. 【F:database/schema/mysql-schema.sql†L61-L80】【F:database/schema/mysql-schema.sql†L2117-L2137】

## Relationship map highlights
- Most tables carry `company_id`, `user_id`, and soft-delete timestamps, reflecting multi-tenancy and audit needs.
- Business documents (invoices, quotes, credits, purchase orders) reference `clients` or `vendors` plus optional `project_id`/`recurring_id` links.
- Payment lifecycle: `payments` connect to `company_gateways` and distribute value through `paymentables` into invoices or credits; invitations tie client contacts to documents for portal access.
- Recurring flows rely on `next_send_date` and link back to originating entities to spawn child records.
