# STATEMENT OF WORK (SOW)

## NRAPA Secure Membership, Compliance & Motivation Platform

**Discovery Phase – Cursor Build Specification**

---

## 1. Purpose

Build a secure, extensible web application for NRAPA to manage memberships, compliance, documentation, knowledge testing, certificates, and firearm motivation requests.

This SOW defines the **functional and architectural requirements** for the system during the discovery phase and is intended to guide implementation in Cursor without locking business rules prematurely.

---

## 2. Core Design Principle (Important)

> **Membership logic must be attribute-driven, not type-hardcoded.**

The system must allow administrators to define **membership types as a collection of attributes**, rather than baking rules into code.

This applies to:

* Annual vs Lifetime memberships
* Dedicated status
* Pricing
* Compliance requirements
* Certificate eligibility
* Renewal behavior

---

## 3. System Architecture

### 3.1 Public Site

* Existing NRAPA WordPress site remains unchanged
* Informational content only
* No sensitive data stored or processed

### 3.2 Secure Membership Platform

* Separate secure application (e.g. members.nrapa.co.za)
* Authenticated access only
* Central system of record for all compliance and member data

### 3.3 Firearm Motivation Add-On

* Separate portal/module for firearm motivation applications
* Supports members and non-members
* Optional add-on, not core dependency

---

## 4. Membership Model (Attribute-Driven)

### 4.1 Membership Types

Membership types are **admin-defined entities** with configurable attributes.

Examples:

* Standard Annual
* Dedicated Annual
* Lifetime
* Any future type without code changes

### 4.2 Membership Attributes (Examples, Not Exhaustive)

Each membership type may define:

* `duration_type`
  * annual / lifetime / custom
* `requires_renewal`
  * true / false
* `expiry_rule`
  * fixed date / rolling / none
* `pricing_model`
  * annual / once-off / none
* `allows_dedicated_status`
  * true / false
* `requires_knowledge_test`
  * true / false
* `requires_documents`
  * list of document types
* `certificate_entitlements`
  * membership / dedicated / endorsement
* `discount_eligibility`
  * allowed / not allowed

> Lifetime memberships must be implemented as a **membership attribute configuration**, not as a special case in code.

---

## 5. Membership Lifecycle

* Application
* Manual admin approval
* Activation
* Renewal (if applicable)
* Suspension
* Revocation

Membership state must be independent of membership type logic.

---

## 6. Required Member Documents (Configurable)

Administrators must be able to configure:

* Which document types exist
* Which are compulsory per membership attribute
* Expiry periods per document
* Archive timing per document

### Default Document Types (Initial Configuration)

* Identity Document
* Proof of Address
* Firearm Competency Certificate
* Shooting Activity Evidence
* Additional admin-defined documents

### Default Retention Rules (Configurable)

* ID: permanent
* Proof of Address: 3 months expiry, 12 months archive
* Competency: 10 years expiry, 12 months archive
* Activity evidence: per membership year

Archived documents remain admin-accessible.

---

## 7. Dedicated Status (Attribute-Controlled)

Dedicated status must be implemented as an **optional membership attribute**, not a standalone membership type.

* Requires admin enablement per membership type
* Requires activity tracking
* Requires manual admin approval
* Certificates only issued if all attribute conditions are met

---

## 8. Online Knowledge Testing

* Single knowledge test
* Mixed question types (MC + written)
* Attribute-controlled requirement:
  * Some membership types require test
  * Others do not
* Manual marking required for written answers
* No test completion certificates

---

## 9. Certificates & Verification

Certificates are generated based on **attribute eligibility**, not membership type name.

Certificates include:

* Membership Certificate
* Dedicated Status Certificate
* Endorsement / Confirmation Letters

All certificates:

* QR verified
* Non-sensitive public validation
* Full audit trail

---

## 10. Firearm Motivation Requests (Add-On)

* Optional module
* Supports members and non-members
* Secure document uploads
* Manual processing
* Hard-copy output only
* Tracks request and issuance history

---

## 11. Administrative System

* Role-based permissions
* Configurable dashboards
* Approval queues
* Full audit logging
* CSV export support

---

## 12. Security & Compliance

* Least-privilege access model
* Logical separation of modules
* POPIA-aligned data handling
* Immutable audit logs

---

## 13. Explicit Exclusions

* SAPS submissions
* Licence guarantees
* Biometric identity verification
* Payment gateways (unless added later)
* Digital motivation delivery

---

## 14. Discovery Phase Rule

Anything not explicitly defined in this SOW must be:

* Discussed
* Logged as a change
* Approved before implementation

---

## Cursor-Specific Implementation Guidance (Non-Binding)

* Prefer configuration tables over conditional logic
* Avoid membership-type `if/else`
* Store rules as data, not code
* Assume future membership types will be added
