# NRAPA Database Schema

## Design Philosophy

This schema implements **attribute-driven membership logic**. Business rules are stored as data in configuration tables, not as conditional code.

---

## Entity Relationship Overview

```
┌─────────────────────┐       ┌──────────────────────┐
│  membership_types   │◄──────│     memberships      │
│  (attributes/rules) │       │  (member instances)  │
└─────────────────────┘       └──────────────────────┘
         │                              │
         │                              │
         ▼                              ▼
┌─────────────────────┐       ┌──────────────────────┐
│   document_types    │       │   member_documents   │
│ (configurable docs) │       │  (uploaded files)    │
└─────────────────────┘       └──────────────────────┘
         │
         ▼
┌─────────────────────┐       ┌──────────────────────┐
│  certificate_types  │◄──────│    certificates      │
│ (entitlement rules) │       │  (issued certs)      │
└─────────────────────┘       └──────────────────────┘
```

---

## Core Tables

### users
Extended Laravel user model for members.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| member_number | unsignedInt | Sequential member number (unique, auto-assigned) |
| uuid | uuid | Public identifier |
| name | string | Full name |
| email | string | Unique email |
| id_number | string | SA ID number (encrypted) |
| phone | string | Contact number |
| date_of_birth | date | DOB |
| physical_address | text | Physical address |
| postal_address | text | Postal address (nullable) |
| email_verified_at | timestamp | Email verification |
| password | string | Hashed password |
| is_admin | boolean | Admin flag |
| created_at | timestamp | |
| updated_at | timestamp | |
| deleted_at | timestamp | Soft delete |

### membership_types
Admin-defined membership types with configurable attributes.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| slug | string | URL-safe identifier |
| name | string | Display name |
| description | text | Description |
| duration_type | enum | annual, lifetime, custom |
| duration_months | int | Months (null for lifetime) |
| requires_renewal | boolean | Whether renewal applies |
| expiry_rule | enum | fixed_date, rolling, none |
| expiry_month | int | Month for fixed_date expiry (1-12) |
| expiry_day | int | Day for fixed_date expiry (1-31) |
| pricing_model | enum | annual, once_off, none |
| price | decimal | Amount in ZAR |
| allows_dedicated_status | boolean | Can apply for dedicated |
| requires_knowledge_test | boolean | Test required |
| discount_eligible | boolean | Eligible for discounts |
| is_active | boolean | Available for selection |
| sort_order | int | Display order |
| created_at | timestamp | |
| updated_at | timestamp | |

### memberships
Member's actual membership records with state machine.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| uuid | uuid | Public identifier |
| user_id | bigint | FK to users |
| membership_type_id | bigint | FK to membership_types |
| membership_number | string | Member number (format: NRAPA-XXXXX, from users.member_number) |
| status | enum | applied, approved, active, suspended, revoked, expired |
| applied_at | timestamp | Application date |
| approved_at | timestamp | Approval date |
| approved_by | bigint | FK to users (admin) |
| activated_at | timestamp | Activation date |
| expires_at | timestamp | Expiry date (null for lifetime) |
| suspended_at | timestamp | Suspension date |
| suspended_by | bigint | FK to users (admin) |
| suspension_reason | text | Reason for suspension |
| revoked_at | timestamp | Revocation date |
| revoked_by | bigint | FK to users (admin) |
| revocation_reason | text | Reason for revocation |
| previous_membership_id | bigint | FK to memberships (renewal chain) |
| notes | text | Admin notes |
| created_at | timestamp | |
| updated_at | timestamp | |
| deleted_at | timestamp | Soft delete |

### document_types
Admin-configurable document types.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| slug | string | URL-safe identifier |
| name | string | Display name |
| description | text | Description |
| expiry_months | int | Months until expiry (null = permanent) |
| archive_months | int | Months to keep after expiry |
| is_active | boolean | Available for use |
| sort_order | int | Display order |
| created_at | timestamp | |
| updated_at | timestamp | |

### membership_type_document_type (Pivot)
Which documents are required for each membership type.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| membership_type_id | bigint | FK to membership_types |
| document_type_id | bigint | FK to document_types |
| is_required | boolean | Mandatory for this type |
| created_at | timestamp | |
| updated_at | timestamp | |

### member_documents
Uploaded member documents.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| uuid | uuid | Public identifier |
| user_id | bigint | FK to users |
| document_type_id | bigint | FK to document_types |
| file_path | string | Storage path (encrypted) |
| original_filename | string | Original name |
| mime_type | string | File MIME type |
| file_size | int | Size in bytes |
| uploaded_at | timestamp | Upload date |
| verified_at | timestamp | Verification date |
| verified_by | bigint | FK to users (admin) |
| expires_at | timestamp | Calculated expiry |
| archived_at | timestamp | Archive date |
| archive_until | timestamp | Retention end date |
| rejection_reason | text | If rejected |
| status | enum | pending, verified, rejected, expired, archived |
| created_at | timestamp | |
| updated_at | timestamp | |
| deleted_at | timestamp | Soft delete |

### certificate_types
Types of certificates that can be issued.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| slug | string | URL-safe identifier |
| name | string | Display name (e.g., "Membership Certificate") |
| description | text | Description |
| template | string | Blade template path |
| validity_months | int | Months valid (null = indefinite) |
| is_active | boolean | Available for issuance |
| sort_order | int | Display order |
| created_at | timestamp | |
| updated_at | timestamp | |

### membership_type_certificate_type (Pivot)
Certificate entitlements per membership type.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| membership_type_id | bigint | FK to membership_types |
| certificate_type_id | bigint | FK to certificate_types |
| requires_dedicated_status | boolean | Only if dedicated |
| requires_active_membership | boolean | Only if active |
| created_at | timestamp | |
| updated_at | timestamp | |

### certificates
Issued certificates with QR verification.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| uuid | uuid | Public identifier (for QR) |
| user_id | bigint | FK to users |
| membership_id | bigint | FK to memberships |
| certificate_type_id | bigint | FK to certificate_types |
| certificate_number | string | Unique certificate number |
| issued_at | timestamp | Issue date |
| issued_by | bigint | FK to users (admin) |
| valid_from | date | Validity start |
| valid_until | date | Validity end (null = indefinite) |
| revoked_at | timestamp | Revocation date |
| revoked_by | bigint | FK to users (admin) |
| revocation_reason | text | Reason for revocation |
| file_path | string | Generated PDF path |
| qr_code | string | QR verification code |
| metadata | json | Additional data |
| created_at | timestamp | |
| updated_at | timestamp | |

### dedicated_status_applications
Applications for dedicated status.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| uuid | uuid | Public identifier |
| user_id | bigint | FK to users |
| membership_id | bigint | FK to memberships |
| status | enum | applied, under_review, approved, rejected |
| applied_at | timestamp | Application date |
| reviewed_at | timestamp | Review date |
| reviewed_by | bigint | FK to users (admin) |
| approved_at | timestamp | Approval date |
| approved_by | bigint | FK to users (admin) |
| rejected_at | timestamp | Rejection date |
| rejection_reason | text | Reason for rejection |
| valid_from | date | Dedicated status start |
| valid_until | date | Dedicated status end |
| notes | text | Admin notes |
| created_at | timestamp | |
| updated_at | timestamp | |

### shooting_activities
Activity tracking for dedicated status eligibility.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| uuid | uuid | Public identifier |
| user_id | bigint | FK to users |
| activity_date | date | Date of activity |
| activity_type | string | Type (range session, competition, etc.) |
| venue | string | Location |
| description | text | Details |
| evidence_document_id | bigint | FK to member_documents |
| verified_at | timestamp | Verification date |
| verified_by | bigint | FK to users (admin) |
| created_at | timestamp | |
| updated_at | timestamp | |

---

## Knowledge Testing Tables

### knowledge_tests
Test definitions.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| slug | string | URL-safe identifier |
| name | string | Test name |
| description | text | Description |
| passing_score | int | Percentage to pass |
| time_limit_minutes | int | Time limit (null = unlimited) |
| max_attempts | int | Max attempts allowed |
| is_active | boolean | Available for taking |
| created_at | timestamp | |
| updated_at | timestamp | |

### knowledge_test_questions
Questions (multiple choice + written).

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| knowledge_test_id | bigint | FK to knowledge_tests |
| question_type | enum | multiple_choice, written |
| question_text | text | The question |
| options | json | MC options (null for written) |
| correct_answer | string | Correct answer (MC only) |
| points | int | Points for this question |
| sort_order | int | Question order |
| is_active | boolean | Include in test |
| created_at | timestamp | |
| updated_at | timestamp | |

### knowledge_test_attempts
Member test attempts.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| uuid | uuid | Public identifier |
| user_id | bigint | FK to users |
| knowledge_test_id | bigint | FK to knowledge_tests |
| started_at | timestamp | Start time |
| submitted_at | timestamp | Submission time |
| auto_score | int | Auto-calculated score (MC) |
| manual_score | int | Manual score (written) |
| total_score | int | Final total |
| passed | boolean | Passed/failed |
| marked_at | timestamp | Manual marking date |
| marked_by | bigint | FK to users (admin) |
| marker_notes | text | Marker comments |
| created_at | timestamp | |
| updated_at | timestamp | |

### knowledge_test_answers
Individual answers per attempt.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| attempt_id | bigint | FK to knowledge_test_attempts |
| question_id | bigint | FK to knowledge_test_questions |
| answer_text | text | Member's answer |
| is_correct | boolean | Auto-marked correctness (MC) |
| points_awarded | int | Points given |
| marker_feedback | text | Feedback (written questions) |
| created_at | timestamp | |
| updated_at | timestamp | |

---

## Firearm Motivation Tables (Add-On)

### firearm_motivation_requests
Motivation request tracking.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| uuid | uuid | Public identifier |
| user_id | bigint | FK to users (nullable for non-members) |
| applicant_name | string | Applicant name |
| applicant_email | string | Applicant email |
| applicant_id_number | string | ID number (encrypted) |
| applicant_phone | string | Contact number |
| firearm_type | string | Type of firearm |
| purpose | text | Purpose/motivation |
| status | enum | submitted, under_review, approved, rejected, issued |
| submitted_at | timestamp | Submission date |
| reviewed_at | timestamp | Review date |
| reviewed_by | bigint | FK to users (admin) |
| approved_at | timestamp | Approval date |
| approved_by | bigint | FK to users (admin) |
| rejected_at | timestamp | Rejection date |
| rejection_reason | text | Reason for rejection |
| issued_at | timestamp | Hard-copy issue date |
| issued_by | bigint | FK to users (admin) |
| notes | text | Admin notes |
| created_at | timestamp | |
| updated_at | timestamp | |

### firearm_motivation_documents
Documents attached to motivation requests.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| request_id | bigint | FK to firearm_motivation_requests |
| document_name | string | Document name |
| file_path | string | Storage path |
| original_filename | string | Original name |
| mime_type | string | File MIME type |
| file_size | int | Size in bytes |
| uploaded_at | timestamp | Upload date |
| created_at | timestamp | |
| updated_at | timestamp | |

---

## Audit & System Tables

### audit_logs
Immutable audit trail.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| uuid | uuid | Public identifier |
| user_id | bigint | FK to users (who performed action) |
| auditable_type | string | Model class |
| auditable_id | bigint | Model ID |
| event | string | created, updated, deleted, etc. |
| old_values | json | Before state |
| new_values | json | After state |
| ip_address | string | Request IP |
| user_agent | string | Browser/client info |
| created_at | timestamp | |

### roles
Admin role definitions.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| slug | string | URL-safe identifier |
| name | string | Role name |
| description | text | Description |
| permissions | json | Array of permission slugs |
| is_system | boolean | Cannot be deleted |
| created_at | timestamp | |
| updated_at | timestamp | |

### role_user (Pivot)
User role assignments.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| user_id | bigint | FK to users |
| role_id | bigint | FK to roles |
| created_at | timestamp | |
| updated_at | timestamp | |

---

## Indexes

- `users`: email (unique), uuid (unique), id_number (unique), member_number (unique)
- `memberships`: user_id, membership_type_id, status, membership_number (indexed), uuid (unique)
- `member_documents`: user_id, document_type_id, status
- `certificates`: uuid (unique), certificate_number (unique), user_id
- `audit_logs`: auditable_type + auditable_id, user_id, created_at

---

## Notes

1. All `uuid` columns are for public-facing URLs (never expose auto-increment IDs)
2. Sensitive data (ID numbers, file paths) should be encrypted at rest
3. Soft deletes used for compliance data retention
4. All state changes trigger audit log entries
5. JSON columns use PostgreSQL JSONB or MySQL JSON type
