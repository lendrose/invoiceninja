# API contract quick reference for AI agents

This guide summarizes how the HTTP API is structured, where to find canonical specs, and shows copy-paste JSON payloads so IDE tooling can avoid recomputing request/response shapes.

## Canonical specifications
- The OpenAPI 3.0 description lives in `openapi/api-docs.yaml`, which composes `components.yaml` and `paths.yaml` plus per-resource fragments under `openapi/components/` and `openapi/paths/`. 【F:openapi/api-docs.yaml†L1-L38】【F:openapi/paths.yaml†L1-L18】
- Routes are registered in `routes/api.php`, grouped under `api/v1` with middleware for throttling, token authentication, JSON validation, and locale selection. 【F:routes/api.php†L134-L170】

## Authentication and versioning
- Public endpoints include signup and OAuth login; everything else sits under the `api/v1` prefix and requires the `token_auth` middleware, so callers must send the API token (typically in the `X-API-TOKEN` header) along with JSON payloads. 【F:routes/api.php†L134-L170】
- Company tokens originate from the `company_tokens` table and tie credentials to specific companies/users. 【F:database/schema/mysql-schema.sql†L660-L685】
- Minimum client versions and rate limits are echoed in `X-MINIMUM-CLIENT-VERSION`, `X-RateLimit-Limit`, and `X-RateLimit-Remaining` headers on most responses. 【F:openapi/api-docs.yaml†L343-L406】

### Request header snippet
```http
POST /api/v1/bank_integrations HTTP/1.1
Host: app.invoiceninja.com
Content-Type: application/json
X-API-TOKEN: <company_token>
X-Requested-With: XMLHttpRequest
```

## Resource patterns
- Most resources use `Route::resource`, exposing `index`, `show`, `store`, `update`, and `destroy` verbs; many also expose bulk operations via `/bulk` POST endpoints and upload helpers via `/upload`. Examples include invoices, expenses, payments, products, projects, purchase orders, and quotes. 【F:routes/api.php†L259-L330】
- Sales documents add specialized actions such as PDF downloads (`{invitation_key}/download`) and payment scheduling (`invoices/{invoice}/payment_schedule`). 【F:routes/api.php†L276-L328】
- Banking flows expose CRUD for `bank_integrations`, `bank_transactions`, and `bank_transaction_rules` plus helper actions: refresh remote accounts, remove linked accounts, fetch transactions for a specific upstream account, and bulk/match endpoints for automated reconciliation. These actions sit under the authenticated `api/v1` prefix. 【F:routes/api.php†L144-L160】【F:openapi/api-docs.yaml†L343-L800】

## Request and response shape
- Controllers extend `App\\Http\\Controllers\\BaseController`, which wires Fractal serializers and establishes default includes for commonly-needed relations (account, company, clients with contacts and documents, invoices with invitations, payments with paymentables, etc.). Responses therefore ship with nested data without extra query parameters. 【F:app/Http/Controllers/BaseController.php†L52-L159】
- The default serializer key is `data`, and controllers can force additional includes via `$forced_includes` when needed. 【F:app/Http/Controllers/BaseController.php†L63-L88】
- OpenAPI schemas under `openapi/components/` define per-entity request/response bodies; paths describe status codes and accepted query parameters. Start from `openapi/paths.yaml` to locate the fragment for a specific resource, then open the matching file under `openapi/paths/` for details. 【F:openapi/paths.yaml†L1-L18】

## Copy-paste JSON for common banking calls
The shapes below mirror the OpenAPI `bank_integrations` and `bank_transactions` schemas so Cursor can avoid resolving references at runtime.

### Create or update a bank integration (`POST /api/v1/bank_integrations`)
**Request body**
```json
{
  "data": {
    "provider_bank_name": "Chase Bank",
    "bank_account_id": 1233434,
    "bank_account_name": "My Checking Acc",
    "bank_account_number": "111 234 2332",
    "bank_account_status": "ACTIVE",
    "bank_account_type": "CREDITCARD",
    "balance": 1000000,
    "currency": "USD"
  }
}
```
**Response body**
```json
{
  "data": {
    "id": "AS3df3A",
    "user_id": "AS3df3A",
    "provider_bank_name": "Chase Bank",
    "bank_account_id": 1233434,
    "bank_account_name": "My Checking Acc",
    "bank_account_number": "111 234 2332",
    "bank_account_status": "ACTIVE",
    "bank_account_type": "CREDITCARD",
    "balance": 1000000,
    "currency": "USD"
  }
}
```
【F:openapi/api-docs.yaml†L343-L427】【F:openapi/components/schemas/bank_integration.yaml†L1-L39】

### List bank integrations (`GET /api/v1/bank_integrations`)
**Response body**
```json
{
  "data": [
    {
      "id": "AS3df3A",
      "provider_bank_name": "Chase Bank",
      "bank_account_id": 1233434,
      "bank_account_name": "My Checking Acc",
      "bank_account_status": "ACTIVE",
      "bank_account_type": "CREDITCARD",
      "balance": 1000000,
      "currency": "USD"
    }
  ],
  "meta": {
    "pagination": {
      "total": 1,
      "count": 1,
      "per_page": 50,
      "current_page": 1,
      "total_pages": 1
    }
  }
}
```
【F:openapi/api-docs.yaml†L343-L406】【F:openapi/components/schemas/bank_integration.yaml†L1-L39】

### Import or edit a bank transaction (`POST /api/v1/bank_transactions`)
**Request body**
```json
{
  "data": {
    "transaction_id": 343434,
    "amount": 10,
    "currency_id": "1",
    "account_type": "creditCard",
    "description": "Potato purchases for Kevin",
    "category_id": 1,
    "category_type": "Expenses",
    "base_type": "CREDIT",
    "date": "2022-09-01",
    "bank_account_id": 1
  }
}
```
**Response body**
```json
{
  "data": {
    "id": "AS3df3A",
    "user_id": "AS3df3A",
    "transaction_id": 343434,
    "amount": 10,
    "currency_id": "1",
    "account_type": "creditCard",
    "description": "Potato purchases for Kevin",
    "category_id": 1,
    "category_type": "Expenses",
    "base_type": "CREDIT",
    "date": "2022-09-01",
    "bank_account_id": 1
  }
}
```
【F:openapi/api-docs.yaml†L788-L853】【F:openapi/components/schemas/bank_transaction.yaml†L1-L36】

### Define a bank transaction rule (`POST /api/v1/bank_transaction_rules`)
**Request body**
```json
{
  "data": {
    "name": "Rule 1",
    "rules": [
      { "operator": "contains", "value": "Amazon", "field": "description" }
    ],
    "auto_convert": true,
    "matches_on_all": true,
    "applies_to": "CREDIT",
    "client_id": "AS3df3A",
    "vendor_id": "AS3df3A",
    "category_id": "AS3df3A"
  }
}
```
**Response body**
```json
{
  "data": {
    "id": "AS3df3A",
    "user_id": "AS3df3A",
    "name": "Rule 1",
    "rules": [
      { "operator": "contains", "value": "Amazon", "field": "description" }
    ],
    "auto_convert": true,
    "matches_on_all": true,
    "applies_to": "CREDIT",
    "client_id": "AS3df3A",
    "vendor_id": "AS3df3A",
    "category_id": "AS3df3A"
  }
}
```
【F:openapi/components/schemas/bank_transaction_rule.yaml†L1-L33】【F:routes/api.php†L144-L160】

### Retrieve transactions for a specific upstream account (`POST /api/v1/bank_integrations/get_transactions/account_id`)
This helper returns transactions already linked to a given upstream account.

**Response body**
```json
{
  "data": {
    "id": "AS3df3A",
    "bank_account_id": 1233434,
    "transactions": [
      {
        "transaction_id": 343434,
        "amount": 10,
        "base_type": "CREDIT",
        "date": "2022-09-01",
        "description": "Potato purchases for Kevin"
      }
    ]
  }
}
```
【F:openapi/api-docs.yaml†L670-L737】【F:openapi/components/schemas/bank_integration.yaml†L1-L39】

## Data hydration and transformations
- Each API resource maps to an Eloquent model (e.g., `App\\Models\\Invoice`, `Client`, `Payment`, `Product`, `Project`) whose relationships are eager-loaded per the BaseController `$first_load` array so derived fields (balances, invitations, documents) are available for transformers. 【F:app/Http/Controllers/BaseController.php†L104-L159】
- Transformers under `app/Transformers/` (referenced via `$entity_transformer` in controllers) convert models into API payloads consistent with OpenAPI component schemas. The BaseController sets the serializer to `JsonApiSerializer` by default to normalize links and relationships. 【F:app/Http/Controllers/BaseController.php†L38-L88】
- Many endpoints support bulk actions that accept an array of IDs plus an `action` payload (for delete, archive, restore, etc.), mirroring patterns documented in OpenAPI path descriptions. 【F:routes/api.php†L259-L330】

## Building responses from database tables
- Sales documents (`invoices`, `quotes`, `credits`, `purchase_orders`) assemble totals, taxes, and invitation links using their respective tables and related `client_contacts`, `companies`, and `users` records. 【F:database/schema/mysql-schema.sql†L1106-L1223】【F:database/schema/mysql-schema.sql†L1555-L1625】
- Payments draw from `payments` and distribute value to invoices/credits through `paymentables`; gateway metadata comes from `company_gateways` and related tables. 【F:database/schema/mysql-schema.sql†L1353-L1431】【F:database/schema/mysql-schema.sql†L591-L631】
- Subscriptions and recurring flows leverage `recurring_invoices`, `recurring_quotes`, and `subscriptions`, which include scheduling columns such as `next_send_date` used by background jobs. 【F:database/schema/mysql-schema.sql†L1850-L2036】【F:database/schema/mysql-schema.sql†L2070-L2114】
- Banking endpoints serialize account metadata (`bank_integrations`) including provider IDs, status, balance, currency, and sync flags, and they nest or filter imported ledger rows (`bank_transactions`) by `bank_integration_id` or upstream `bank_account_id`. Matching endpoints consume arrays of transaction payloads containing invoice IDs, expense IDs, or payment IDs to reconcile records; rule endpoints expose `bank_transaction_rules` with the serialized `rules` blob and action flags. Refer to OpenAPI banking paths for request/response bodies and examples of matching payloads. 【F:database/schema/mysql-schema.sql†L151-L278】【F:openapi/api-docs.yaml†L343-L800】【F:openapi/api-docs.yaml†L1094-L1180】

## How to explore
1. Use `openapi/api-docs.yaml` in your IDE to browse endpoints, parameters, and response schemas without running code. 【F:openapi/api-docs.yaml†L1-L38】
2. Jump to `routes/api.php` to see controller mappings and custom actions for each resource. 【F:routes/api.php†L134-L330】
3. Inspect the relevant transformer in `app/Transformers/` to see how database columns map to JSON fields; the BaseController’s default includes list reveals which relationships arrive automatically. 【F:app/Http/Controllers/BaseController.php†L104-L159】
