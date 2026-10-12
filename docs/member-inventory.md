# NRAPA member platform — Phase 0 inventory

**Status:** draft for Paul's review. No code changes have been made. Phase 1 does not start until the "Questions for Paul" at the bottom are answered.

**What this document is for.** The member brief says every status computation must flow through one resolver, and every change ships one phase at a time with Paul's sign-off on live. Before any of that can start we need an honest picture of what is already on the member side: which pages exist, which pages compute a member-facing status, which rules each page applies, and where two pages compute the same thing differently. That is this document.

**What this document is not.** This is not a design. It does not pick winners between conflicting rules. Where two code paths disagree about what "compliant" means, the conflict is numbered and flagged — Paul decides.

**Scope.** Only the member-facing surface and the models/middleware that feed it. The admin, owner, developer and public-info routes are listed in Section 1 for context but their internals are out of scope.

**Guardrail from Paul (12 Oct 2026): don't change member processes.**

Returning members have learned the current flows and will complain if too much moves at once. Every later phase must honour the following:

- **Behaviour-preserving refactors by default.** Phase 1 (resolver) centralises how statuses are computed but must not change the answer for any real member. If centralising surfaces that two code paths disagree today (Conflicts 1, 3, 6, 12), the resolver mirrors whichever one the member has been seeing, and Paul picks the winner before Phase 1 ships — not during.
- **No new blockers.** Phase 2 must not add a step to the next-step card that would turn a member who is "fully compliant" today into "not compliant" tomorrow (missing-phone is the one exception, because the brief explicitly asks for it and the banner is already visible — it just moves into the card).
- **UI changes are additive before they are subtractive.** Phase 3's dashboard clean-up is where returning members will notice the biggest change; if it looks too big in review, split it so the duplicates are removed one card at a time.
- **No workflow renaming without a reason the member will recognise.** Phase 6 reorders the sidebar and reconciles "Dedicated Status" vs "Endorsements". If the brief's proposed new order has no evidence (route-access logs, support tickets), keep the current order.
- **No new fields, uploads, declarations or approvals** on the member side unless the brief or Paul asks for them in writing.
- **Rollback test every phase.** Each PR ships behind the current URL set so a hotfix can revert to the previous view without a migration.

If any later phase cannot be delivered without violating one of these, stop and ask Paul rather than widen the phase.

---

## 1. Member routes

Source: [routes/web.php](routes/web.php) and [routes/settings.php](routes/settings.php).

Two tiers of middleware guard the member area:

- `auth` + `verified`: dashboard, messages, membership (so a free member who has not paid can still get in to pick a package).
- `auth` + `verified` + `membership.required` + `terms.accepted`: everything else. [RedirectIfNoMembership](app/Http/Middleware/RedirectIfNoMembership.php) and [EnsureTermsAccepted](app/Http/Middleware/EnsureTermsAccepted.php) both let admin/owner/developer pass without an active membership and without accepting terms.

### 1a. Routes available to any verified member (including free)

| URL | Route name | Component / view | Notes |
| --- | --- | --- | --- |
| `/terms` | `terms.accept` | [pages/member/terms/accept.blade.php](resources/views/pages/member/terms/accept.blade.php) | Reached by `terms.accepted` middleware when a new terms version is published. |
| `/dashboard` | `dashboard` | [pages/member/dashboard.blade.php](resources/views/pages/member/dashboard.blade.php) | 2 080-line Volt file. The subject of Phases 1–3. |
| `/messages` | `messages.index` | [pages/member/messages/index.blade.php](resources/views/pages/member/messages/index.blade.php) | |
| `/messages/new` | `messages.create` | [pages/member/messages/create.blade.php](resources/views/pages/member/messages/create.blade.php) | |
| `/messages/{message}` | `messages.show` | [pages/member/messages/show.blade.php](resources/views/pages/member/messages/show.blade.php) | |
| `/membership/select-package` | `membership.select-package` | [pages/member/membership/select-package.blade.php](resources/views/pages/member/membership/select-package.blade.php) | |
| `/membership/payment/{membership}` | `membership.payment` | [pages/member/membership/payment.blade.php](resources/views/pages/member/membership/payment.blade.php) | |
| `/membership` | `membership.index` | [pages/member/membership/index.blade.php](resources/views/pages/member/membership/index.blade.php) | Includes the renewal flow. |
| `/membership/apply` | `membership.apply` | [pages/member/membership/apply.blade.php](resources/views/pages/member/membership/apply.blade.php) | |
| `/membership/club-apply/{club}` | `membership.club-apply` | [pages/member/membership/club-apply.blade.php](resources/views/pages/member/membership/club-apply.blade.php) | Affiliated-club onboarding. |
| `/membership/{membership}` | `membership.show` | [pages/member/membership/show.blade.php](resources/views/pages/member/membership/show.blade.php) | |

### 1b. Routes that require an active membership

| URL | Route name | Component / view / controller |
| --- | --- | --- |
| `/card` | `card` | [pages/member/card.blade.php](resources/views/pages/member/card.blade.php) |
| `/certificates` | `certificates.index` | [pages/member/certificates/index.blade.php](resources/views/pages/member/certificates/index.blade.php) |
| `/certificates/{certificate}` | `certificates.show` | [pages/member/certificates/show.blade.php](resources/views/pages/member/certificates/show.blade.php) |
| `/certificates/{certificate}/wallet/apple` | `certificates.wallet.apple` | [CertificateFileController@appleWallet](app/Http/Controllers/CertificateFileController.php) |
| `/certificates/{certificate}/wallet/google` | `certificates.wallet.google` | `CertificateFileController@googleWallet` |
| `/certificates/{certificate}/download` | `certificates.download` | `CertificateFileController@download` |
| `/certificates/{certificate}/preview` | `certificates.preview` | `CertificateFileController@preview` |
| `/knowledge-test` | `knowledge-test.index` | [pages/member/knowledge-test/index.blade.php](resources/views/pages/member/knowledge-test/index.blade.php) |
| `/knowledge-test/{test}/take` | `knowledge-test.take` | [pages/member/knowledge-test/take.blade.php](resources/views/pages/member/knowledge-test/take.blade.php) |
| `/knowledge-test/{attempt}/results` | `knowledge-test.results` | [pages/member/knowledge-test/results.blade.php](resources/views/pages/member/knowledge-test/results.blade.php) |
| `/activities` | `activities.index` | [pages/member/activities/index.blade.php](resources/views/pages/member/activities/index.blade.php) |
| `/activities/submit` | `activities.submit` | [pages/member/activities/submit.blade.php](resources/views/pages/member/activities/submit.blade.php) |
| `/activities/{activity}` | `activities.show` | [pages/member/activities/show.blade.php](resources/views/pages/member/activities/show.blade.php) |
| `/activities/{activity}/edit` | `activities.edit` | [pages/member/activities/edit.blade.php](resources/views/pages/member/activities/edit.blade.php) |
| `/armoury` | `armoury.index` | [pages/member/armoury/index.blade.php](resources/views/pages/member/armoury/index.blade.php) |
| `/armoury/add` | `armoury.create` | [pages/member/armoury/create.blade.php](resources/views/pages/member/armoury/create.blade.php) |
| `/armoury/{firearm}` | `armoury.show` | [pages/member/armoury/show.blade.php](resources/views/pages/member/armoury/show.blade.php) |
| `/armoury/{firearm}/edit` | `armoury.edit` | [pages/member/armoury/edit.blade.php](resources/views/pages/member/armoury/edit.blade.php) |
| `/armoury/{firearm}/licence` | `armoury.licence` | [FirearmLicenceController](app/Http/Controllers/FirearmLicenceController.php) |
| `/load-data` and children | `load-data.*`, `ladder-test.*` | [pages/member/load-data/](resources/views/pages/member/load-data/) |
| `/load-data/ladder-tests/{test}/labels` | `ladder-test.labels` | Inline closure in [routes/web.php](routes/web.php) |
| `/documents` | `documents.index` | [pages/member/documents/index.blade.php](resources/views/pages/member/documents/index.blade.php) |
| `/documents/{document}` | `documents.show` | [pages/member/documents/show.blade.php](resources/views/pages/member/documents/show.blade.php) |
| `/documents/{document}/preview` | `documents.preview` | [MemberDocumentPreviewController](app/Http/Controllers/MemberDocumentPreviewController.php) |
| `/learning` | `learning.index` | [pages/member/learning/index.blade.php](resources/views/pages/member/learning/index.blade.php) |
| `/learning/category/{category}` | `learning.category` | [pages/member/learning/category.blade.php](resources/views/pages/member/learning/category.blade.php) |
| `/learning/{article}` | `learning.show` | [pages/member/learning/show.blade.php](resources/views/pages/member/learning/show.blade.php) |
| `/endorsements` | `member.endorsements.index` | [pages/member/endorsements/index.blade.php](resources/views/pages/member/endorsements/index.blade.php) |
| `/endorsements/create` | `member.endorsements.create` | [pages/member/endorsements/create.blade.php](resources/views/pages/member/endorsements/create.blade.php) — hosts [app/Livewire/Member/EndorsementCreate.php](app/Livewire/Member/EndorsementCreate.php) |
| `/endorsements/self-defence` | `member.endorsements.self-defence` | [pages/member/endorsements/self-defence.blade.php](resources/views/pages/member/endorsements/self-defence.blade.php) |
| `/endorsements/{request}/edit` | `member.endorsements.edit` | reuses the create view |
| `/endorsements/{request}` | `member.endorsements.show` | [pages/member/endorsements/show.blade.php](resources/views/pages/member/endorsements/show.blade.php) |
| `/endorsements/{request}/preview` | `member.endorsements.preview` | [EndorsementLetterController@preview](app/Http/Controllers/EndorsementLetterController.php) |
| `/endorsements/{request}/letter` | `member.endorsements.letter` | `EndorsementLetterController@letter` |

### 1c. Settings pages (verified; no membership required)

Source: [routes/settings.php](routes/settings.php). `GET /settings` redirects to `/settings/profile`.

| URL | Route name | View |
| --- | --- | --- |
| `/settings/profile` | `profile.edit` | [pages/settings/profile.blade.php](resources/views/pages/settings/⚡profile.blade.php) |
| `/settings/password` | `user-password.edit` | [pages/settings/password.blade.php](resources/views/pages/settings/⚡password.blade.php) |
| `/settings/appearance` | `appearance.edit` | [pages/settings/appearance.blade.php](resources/views/pages/settings/⚡appearance.blade.php) |
| `/settings/notifications` | `notifications.edit` | [pages/settings/notifications.blade.php](resources/views/pages/settings/notifications.blade.php) |
| `/settings/security-questions` | `security-questions.edit` | [pages/settings/security-questions.blade.php](resources/views/pages/settings/security-questions.blade.php) |
| `/settings/two-factor` | `two-factor.show` | [pages/settings/two-factor.blade.php](resources/views/pages/settings/two-factor.blade.php) |

### 1d. Guides (logged-in only, except hub and sign-up)

Prefix `/guides`, name prefix `guides.`:

- `/guides` — hub, public.
- `/guides/sign-up` — public.
- `/guides/upload-proof-of-payment`, `/guides/submit-activities`, `/guides/request-endorsement` — require `auth`.

### 1e. Routes that touch the member session but are not member pages

- `GET /email/confirm/{id}/{hash}` — `verification.confirm`, signed route that marks the email verified and redirects to the dashboard if the member happens to be logged in on that device.
- `GET /dev/impersonate/{user}` — `dev.impersonate`, developer-only.
- `GET /dev/stop-impersonating` — `dev.stop-impersonating`.
- `POST /toggle-member-view` — `toggle-member-view`, admin/owner/developer only. Flips the `view_as_member` session flag; both `membership.required` and the sidebar use that flag.

---

## 2. Status computations

Every member-facing status computation we found, with its source file, the rule it applies, and how it treats the member types that break every other code path (lifetime, exempt/honorary, members with no membership row, members in their join year).

### 2a. Membership status

| What | File + line | Rule |
| --- | --- | --- |
| "Paid-up till" chip (dashboard) | [resources/views/pages/member/dashboard.blade.php:1486](resources/views/pages/member/dashboard.blade.php) | Inline: `$isLifetime \|\| !$expires_at \|\| $expires_at->isFuture()`. Shows "Lifetime" when `type->isLifetime()`. |
| `nextStep` → `apply` / `awaiting_review` | [dashboard.blade.php:462](resources/views/pages/member/dashboard.blade.php) | `nextStep()` method. "Apply" when `needsMembership`. "Awaiting review" when `latestMembership->status === 'applied'`. |
| `expiredMembership` | [dashboard.blade.php:112](resources/views/pages/member/dashboard.blade.php) | Null if no `expires_at` OR `type->isLifetime()`. Otherwise `expires_at->isPast() ? $m : null`. |
| `expiringSoonMembership` | [dashboard.blade.php:128](resources/views/pages/member/dashboard.blade.php) | Returns the membership if `expires_at` is between now and now+30 days, and not lifetime. 30-day hard-coded. |
| Renewal / grace banners (expired) | [dashboard.blade.php:770](resources/views/pages/member/dashboard.blade.php) | Uses `Membership::isExtendedGraceActive()` to switch the copy. |
| `membershipStatus` on /membership | [resources/views/pages/member/membership/index.blade.php:125](resources/views/pages/member/membership/index.blade.php) | `days_to_expiry`, `expiring_soon`, `expired` computed from `activeMembership->expires_at`. Lifetime returns all nulls/false. |
| `renewableMembership`, `canRenew`, `renewalUpcoming` | [membership/index.blade.php:66](resources/views/pages/member/membership/index.blade.php) | Thin wrappers around `Membership::isRenewable()` / `isRenewalUpcoming()`. |
| `isActive` / `isExpired` / `requiresRenewal` / `isLifetime` | [app/Models/Membership.php:494](app/Models/Membership.php) ff. | Pure model methods. `isLifetime` chains to `MembershipType::isLifetime()`. |
| `scopeCurrent` (defines "has an active membership right now") | [app/Models/Membership.php:858](app/Models/Membership.php) | status=active AND (no expires_at OR expires_at >= today OR type.duration_type = 'lifetime'). Used by `User::activeMembership`. |
| `activeMembership()` relation | [app/Models/User.php:622](app/Models/User.php) | hasOne, applies `scopeCurrent`, orders by id desc. |
| `latestMembership` | [dashboard.blade.php:67](resources/views/pages/member/dashboard.blade.php) | `memberships()->latest()->first()` — not scoped by status, so a revoked row counts. |
| `getDisplayStatus($m)` on /membership | [membership/index.blade.php:400](resources/views/pages/member/membership/index.blade.php) | Reclassifies `active` + past expiry + not lifetime as `expired` for display only. |
| `hasPendingChangeRequest` | [membership/index.blade.php:201](resources/views/pages/member/membership/index.blade.php) | Any membership with status in `pending_change`, `pending_payment`. |

### 2b. Documents

| What | File + line | Rule |
| --- | --- | --- |
| `idDocumentStatus` | [dashboard.blade.php:160](resources/views/pages/member/dashboard.blade.php) | Finds the ID document (slugs `identity-document`, `id-document`) with the most favourable status via `orderByRaw("FIELD(status,'verified','pending','rejected') ASC")`. Returns `'none'`, `'missing'`, `'verified'`, `'pending'`, `'rejected'`. **MySQL-only SQL.** The constants `MemberDocument::ID_DOCUMENT_SLUGS` include a third slug `id-copy` which this check ignores. |
| ID row inside the requirements card | [dashboard.blade.php:1270](resources/views/pages/member/dashboard.blade.php) | Reimplements the same raw-SQL ID lookup inline in Blade, then formats a status label. Does not reuse `idDocumentStatus`. |
| `pendingDocuments` | [dashboard.blade.php:182](resources/views/pages/member/dashboard.blade.php) | `MemberDocument::where(user)->pending()` → `status=pending`. |
| `rejectedDocuments` | [dashboard.blade.php:204](resources/views/pages/member/dashboard.blade.php) | `status=rejected` AND `rejection_reason` not null. |
| `activeRejectedDocuments` / `dismissedRejectedDocuments` | [dashboard.blade.php:244](resources/views/pages/member/dashboard.blade.php) | Partitioned by `NotificationDismissal`. |
| `MemberCompliance`-style "documents complete" | [app/Models/EndorsementRequest.php:498](app/Models/EndorsementRequest.php) `getMissingRequiredDocuments` | Only checks ID (`identity-document` OR `id-document`). A document is valid if `status=verified` AND (no `expires_at` OR future). Does not check anything else despite "required documents" wording. |
| `documents_complete` (eligibility summary) | [EndorsementRequest.php:620](app/Models/EndorsementRequest.php) | `count($missingDocuments) === 0`. |
| `MemberDocument::scopeValid` / `scopePending` / `scopeVerified` | [app/Models/MemberDocument.php:647](app/Models/MemberDocument.php) | status filters; `valid` adds the expiry test. |
| `isExpired` on a document | [MemberDocument.php:273](app/Models/MemberDocument.php) | `expires_at && expires_at->isPast()`. |
| Documents page status chips | [pages/member/documents/index.blade.php:316](resources/views/pages/member/documents/index.blade.php) `getStatusBadgeClass` | Static mapping for `pending`, `verified`, `rejected`, `expired`, `archived`. |

### 2c. Shooting activities

| What | File + line | Rule |
| --- | --- | --- |
| `ShootingActivity::complianceSummary` | [app/Models/ShootingActivity.php:614](app/Models/ShootingActivity.php) | Previous year Jan 1 – Oct 31 approved count qualifies the current year; current year Jan 1 – Oct 31 banks for next year. Join-year exempt. Uses `sum(track = 'hunting')` in SQL (MySQL/SQLite boolean coercion). `$required` is a parameter — every caller passes `2`. |
| `ShootingActivity::getActivityPeriod` | [ShootingActivity.php:584](app/Models/ShootingActivity.php) | Jan 1 – Oct 31 window for a given year. |
| `ShootingActivity::scopeWithinActivityYear` | [ShootingActivity.php:568](app/Models/ShootingActivity.php) | Same window as a query scope. |
| `EndorsementRequest::checkActivityRequirements` | [app/Models/EndorsementRequest.php:525](app/Models/EndorsementRequest.php) | **Different rule.** Lifetime → `met=true, required=0`. Join-year → `met=true, exempt=true, required=<system setting>`. Else counts current-year Jan 1 – Oct 31 approved; before Oct 31 the previous year's count can carry over. Pulls `$required` from `SystemSetting::get('endorsement_min_activities', 2)` instead of hardcoding. |
| `activityCompliance` (dashboard) | [dashboard.blade.php:308](resources/views/pages/member/dashboard.blade.php) | Hardcodes `$required = 2`, calls `complianceSummary`, projects into an array for the dashboard cards. |
| `complianceStatus` (/endorsements) | [resources/views/pages/member/endorsements/index.blade.php:122](resources/views/pages/member/endorsements/index.blade.php) | Hardcodes `$required = 2` **again**, calls `complianceSummary`. |
| `activityYear` and `activityPeriod` (/endorsements) | [endorsements/index.blade.php:101](resources/views/pages/member/endorsements/index.blade.php) | Flips to `year+1` from November, declares the period `Jan {year} – Sep {year}` with Oct 31 as the deadline. **Does not match either `complianceSummary` or `checkActivityRequirements`.** Only used for the display label on this page. |
| `/activities` page `with()` | [resources/views/pages/member/activities/index.blade.php:154](resources/views/pages/member/activities/index.blade.php) | Hardcodes `$requiredCount = 2`, calls `complianceSummary`, also runs its own `YEAR(activity_date)` yearly history query (MySQL-only). Mount hard-redirects members whose type does not allow dedicated status. |
| `/endorsements` eligibility card activity line | [endorsements/index.blade.php:490](resources/views/pages/member/endorsements/index.blade.php) | Reads `$this->eligibility['activity_details']['approved_count']`, which is `checkActivityRequirements`'s output — a different number from the compliance bar above it on the same page. |
| `pendingActivities` / `rejectedActivities` (dashboard) | [dashboard.blade.php:196](resources/views/pages/member/dashboard.blade.php) | Filter by `status=pending` / `status=rejected`. |
| `withinActivityYear` + ApprovedActivityCount on `Membership` | [app/Models/Membership.php:462](app/Models/Membership.php) | Used by `meetsActivityRequirement` for affiliated clubs. Uses `activityType` relation and the Jan–Oct window. |

### 2d. Knowledge test

| What | File + line | Rule |
| --- | --- | --- |
| `User::hasPassedKnowledgeTest` | [app/Models/User.php:897](app/Models/User.php) | True if any attempt has `passed=true` OR `marked_by` is not null. **Any** test, not test-type-specific. |
| `MembershipType::hasUserPassedAllRequiredTests` | [app/Models/MembershipType.php:180](app/Models/MembershipType.php) | Requires passes for every test in the pivot table where `is_required=true`. |
| `hasPassedTest` (dashboard) | [dashboard.blade.php:79](resources/views/pages/member/dashboard.blade.php) | Thin wrapper around `User::hasPassedKnowledgeTest`. |
| `requiresTest` (dashboard) | [dashboard.blade.php:84](resources/views/pages/member/dashboard.blade.php) | `activeMembership?->type?->requires_knowledge_test`. |
| `knowledge_test_passed` on eligibility | [EndorsementRequest.php:624](app/Models/EndorsementRequest.php) | `User::hasPassedKnowledgeTest`. |
| `dedicatedStatusRequirements` (/knowledge-test) | [resources/views/pages/member/knowledge-test/index.blade.php:69](resources/views/pages/member/knowledge-test/index.blade.php) | Its own test-type matrix (hunter / sport / both). Treats "passed" as `passed=true` OR `marked_by` not null. |
| `attemptCountsAsPassed` | [knowledge-test/index.blade.php](resources/views/pages/member/knowledge-test/index.blade.php) | Same definition of "passed". |

### 2e. Dedicated status / endorsement eligibility

| What | File + line | Rule |
| --- | --- | --- |
| `hasDedicatedMembership` | [dashboard.blade.php:271](resources/views/pages/member/dashboard.blade.php) | `type->allows_dedicated_status \|\| !empty(type->dedicated_type)`. |
| `Membership::allowsDedicatedStatus` | [app/Models/Membership.php:744](app/Models/Membership.php) | `type?->allows_dedicated_status`. Nav tabs use this one. |
| `member-nav-tabs` tab for "Dedicated Status" | [resources/views/partials/member-nav-tabs.blade.php:77](resources/views/partials/member-nav-tabs.blade.php) | `$membership->allowsDedicatedStatus()` — i.e. ignores `dedicated_type`, unlike the dashboard. |
| `dedicatedType` (/endorsements) | [endorsements/index.blade.php:85](resources/views/pages/member/endorsements/index.blade.php) | `activeMembership?->type?->dedicated_type`. |
| `userDedicatedType` (/knowledge-test) | [knowledge-test/index.blade.php:14](resources/views/pages/member/knowledge-test/index.blade.php) | Same expression. |
| `canApplyForDedicatedStatus` (/knowledge-test) | [knowledge-test/index.blade.php:32](resources/views/pages/member/knowledge-test/index.blade.php) | `membership->type->allows_dedicated_status`. |
| `canUpgradeToDedicated` (/membership) | [membership/index.blade.php:181](resources/views/pages/member/membership/index.blade.php) | `dedicated_type === null && !allows_dedicated_status`. |
| `EndorsementRequest::checkUserEligibility` | [EndorsementRequest.php:428](app/Models/EndorsementRequest.php) | Checks knowledge test, missing docs, activities, active membership whose `dedicated_type` is `hunter`/`sport`/`both`. |
| `EndorsementRequest::getEligibilitySummary` | [EndorsementRequest.php:616](app/Models/EndorsementRequest.php) | Composes the above three booleans. The dashboard's eligibility card consumes this. |
| `endorsementEligibility` (dashboard) | [dashboard.blade.php:328](resources/views/pages/member/dashboard.blade.php) | Returns `null` for members without dedicated membership — so none of the eligibility messaging renders for a basic member even though their rejected items do. |
| `allRequirementsComplete` (dashboard) | [dashboard.blade.php:346](resources/views/pages/member/dashboard.blade.php) | AND of `knowledge_test_passed`, `documents_complete`, `activities_met`. Drives the "All requirements met" headline inside the eligibility card. |
| "Request Endorsement" button on the eligibility card | [dashboard.blade.php:1401](resources/views/pages/member/dashboard.blade.php) | Only renders when `dedicated_type` is in `['hunter','sport','both']` — matches `checkUserEligibility`'s own gate. |

### 2f. Firearm / virtual safe status

| What | File + line | Rule |
| --- | --- | --- |
| `firearmCounts` (dashboard) | [dashboard.blade.php:287](resources/views/pages/member/dashboard.blade.php) | `UserFirearm::forUser(id)->expired()->count()` + `expiringSoon(90)->count()` + `license_status='renewal_pending'` + totals. |
| `UserFirearm::scopeExpired` | [app/Models/UserFirearm.php:485](app/Models/UserFirearm.php) | `license_expiry_date < now()`. |
| `UserFirearm::scopeExpiringSoon($days = 90)` | [UserFirearm.php:478](app/Models/UserFirearm.php) | `license_expiry_date` between now and now+days. |
| Firearm status display (/armoury) | [UserFirearm.php:442](app/Models/UserFirearm.php) ff. | `['color' => 'red', 'text' => 'Expired']` / 'Revoked' / 'Renewal Pending' / 'Expires in N days'. |
| `scopeActive` | [UserFirearm.php:473](app/Models/UserFirearm.php) | `is_active = true` — separate from expiry/licence status. |

### 2g. Profile data

| What | File + line | Rule |
| --- | --- | --- |
| Phone missing banner | [dashboard.blade.php:675](resources/views/pages/member/dashboard.blade.php) | Inline `@if(empty($this->user->phone))`. Independent of `nextStep()`. |
| Phone pulse dot on the quick-link | [dashboard.blade.php:1582](resources/views/pages/member/dashboard.blade.php) | Same `empty(phone)` check, renders a pulsing dot. |
| Profile validation | [app/Concerns/ProfileValidationRules.php:57](app/Concerns/ProfileValidationRules.php) | Phone is `required`. ID number, address and date of birth live elsewhere. |

### 2h. Certificates / welcome letter

| What | File + line | Rule |
| --- | --- | --- |
| `certificates` (dashboard, latest 3 valid) | [dashboard.blade.php:73](resources/views/pages/member/dashboard.blade.php) | `User::certificates()->valid()->latest()->take(3)` — the certificate `valid` scope. |
| `membershipCertificate` / `welcomeLetterCertificate` (dashboard) | [dashboard.blade.php:376](resources/views/pages/member/dashboard.blade.php) | Filter by certificate type slug. |
| `shouldShowWelcomeLetterPrompt` | [dashboard.blade.php:423](resources/views/pages/member/dashboard.blade.php) | Active membership + welcome letter cert exists + `welcome_letter_seen_at` null. |
| `isNewlyApproved` | [dashboard.blade.php:361](resources/views/pages/member/dashboard.blade.php) | `approved_at` within last 30 days. |
| `cardQrCodeUrl` | [dashboard.blade.php:402](resources/views/pages/member/dashboard.blade.php) | Latest valid certificate of type `membership-card` with a QR code. |
| Card page lifetime check | [resources/views/pages/member/card.blade.php:99](resources/views/pages/member/card.blade.php) | **Uses `$this->membership->type?->is_lifetime`** — a property that does not exist on `MembershipType`. The method is `isLifetime()`. See conflict 7. |

### 2i. Approvals / "waiting on NRAPA" surface

Driven by two unrelated code paths today — no `my submissions` page exists. The admin side already carries the `Approvable` contract and the `ApprovalQueue` sidebar count ([app/Helpers/SidebarMenu.php:170](app/Helpers/SidebarMenu.php)); the member side only shows "pending" alerts for documents and activities and does not surface the admin-side `waiting_on_member` / `changes_requested` states.

Member-side pending surfaces:

- `pendingDocuments` and `pendingActivities` on the dashboard.
- Membership "pending review" copy on `/membership` ([membership/index.blade.php:400](resources/views/pages/member/membership/index.blade.php)).
- `pending`, `approved`, `issued` counts on `/endorsements` ([endorsements/index.blade.php:46](resources/views/pages/member/endorsements/index.blade.php)).

Phase 4 of the brief depends on admin Phases 1 + 3 to replace the member side of this.

### 2j. Middleware-level status

- `membership.required` → [RedirectIfNoMembership](app/Http/Middleware/RedirectIfNoMembership.php) uses `$user->activeMembership`. Free routes it lets through: `dashboard`, `membership.*`, `logout`, `profile.edit`, `user-password.edit`, `two-factor.show`, `appearance.edit`, `verification.*`, `settings.*`. Admin-level users bypass unless `view_as_member` is set.
- `terms.accepted` → [EnsureTermsAccepted](app/Http/Middleware/EnsureTermsAccepted.php) uses `$user->hasAcceptedActiveTerms()`. Admin/owner/developer bypass unconditionally.
- `view_as_member` session flag flipped by `POST /toggle-member-view`. Both the sidebar and `RedirectIfNoMembership` honour it.

### 2k. Navigation sources

Three navigation surfaces coexist on the member dashboard, each with its own order and vocabulary:

1. **Sidebar** — [resources/views/layouts/app/sidebar.blade.php](resources/views/layouts/app/sidebar.blade.php) + [app/Helpers/SidebarMenu.php](app/Helpers/SidebarMenu.php). Sections: `VIRTUAL CARD`, `MEMBER AREA`. Member Area order: Dashboard, Messages, My Membership, Guides, Documents, Activities, Virtual Safe, Endorsements, Certificates, Learning (collapsible: Learning Center, Knowledge Tests).
2. **Horizontal tab row** under the page header — [resources/views/partials/member-nav-tabs.blade.php](resources/views/partials/member-nav-tabs.blade.php). Order: Dashboard, Membership, Documents, Activities, Virtual Safe, Certificates, Learning, Dedicated Status (conditional on `allowsDedicatedStatus()`), Renew (conditional), Profile. Labels and ordering differ from the sidebar; "Dedicated Status" and "Endorsements" are the same route (`member.endorsements.index`) under two different names.
3. **"Quick Links" grid** inside [dashboard.blade.php:1567](resources/views/pages/member/dashboard.blade.php) — Membership Card, My Profile, Virtual Safe, Activities (conditional), Endorsements (conditional), Documents, Learning Center, Certificates, Payments.

---

## 3. Conflicts

Each conflict is numbered, cites the file and line of each side, and ends with a question for Paul. Nothing is picked here.

### Conflict 1 — Lifetime members: "0/0" vs "0 / 2"

- [EndorsementRequest::checkActivityRequirements:529](app/Models/EndorsementRequest.php) short-circuits lifetime members with `required = 0, met = true, period = 'Lifetime'`. The dashboard's eligibility card then renders a tick and "0/0" ([dashboard.blade.php:1345](resources/views/pages/member/dashboard.blade.php)).
- [ShootingActivity::complianceSummary:614](app/Models/ShootingActivity.php) has **no** lifetime branch; it always counts activities and uses `$required = 2`. The dashboard's "Compliance Status" card ([dashboard.blade.php:1540](resources/views/pages/member/dashboard.blade.php)) consumes this and shows "0 / 2".
- Both cards render for the same lifetime member on the same page.
- **Question for Paul:** What is the correct lifetime treatment on the dashboard? Options: (a) "Not applicable" with a reason sentence, nothing rendered as a ratio or a tick; (b) "Compliant" with the Lifetime badge, no activity count at all; (c) something else.

### Conflict 2 — How many activities are required

- [EndorsementRequest::checkActivityRequirements:542](app/Models/EndorsementRequest.php) reads `SystemSetting::get('endorsement_min_activities', 2)`.
- [dashboard.blade.php:310](resources/views/pages/member/dashboard.blade.php), [activities/index.blade.php:158](resources/views/pages/member/activities/index.blade.php) and [endorsements/index.blade.php:129](resources/views/pages/member/endorsements/index.blade.php) all hardcode `$required = 2`.
- If the setting is ever changed, the eligibility card and the compliance cards will disagree. Nobody we spoke to said the setting is live-tuned today, but the branch exists.
- **Question for Paul:** Is `endorsement_min_activities` live and authoritative, and should every member-side computation route through it? Or is "2" the business rule and the setting should be removed?

### Conflict 3 — Which window counts as "this year's activities"

- [complianceSummary:614](app/Models/ShootingActivity.php): previous year Jan 1 – Oct 31 proves current-year compliance; current year Jan 1 – Oct 31 banks for next year.
- [checkActivityRequirements:562](app/Models/EndorsementRequest.php): starts from the current year's Jan 1 – Oct 31 window; only if that is short of the required number AND we are before 31 October does the previous year carry over.
- [endorsements/index.blade.php:101](resources/views/pages/member/endorsements/index.blade.php): declares a third window — year flips in November, period labelled `Jan {year} – Sep {year}` with Oct 31 as the deadline — used only for the display label.
- For a member with 0 approved in 2025 and 3 approved so far in 2026 (as of today, 12 Oct 2026) the three paths give different answers for "which activities count" and "what label to show the period".
- **Question for Paul:** What is the single rule for the activity year? If `complianceSummary` is right (previous year qualifies this year), confirm it and we rip out the other two.

### Conflict 4 — "Dedicated status" is decided three ways

- [dashboard.blade.php:271](resources/views/pages/member/dashboard.blade.php): `allows_dedicated_status || !empty(dedicated_type)` — most permissive.
- [app/Models/Membership.php:744](app/Models/Membership.php) `allowsDedicatedStatus()`: `type?->allows_dedicated_status` only. The horizontal tab row uses this one, so the "Dedicated Status" tab can disappear while the dashboard's dedicated-status card is still rendering.
- [EndorsementRequest::checkUserEligibility:478](app/Models/EndorsementRequest.php): "endorsement letters are only available when `dedicated_type` is in `hunter`/`sport`/`both`" — the strictest gate. The "Request Endorsement" button on the dashboard uses the same gate ([dashboard.blade.php:1401](resources/views/pages/member/dashboard.blade.php)).
- A member whose type has `allows_dedicated_status = true` but `dedicated_type = null` sees the dedicated card and the eligibility messaging, but not the "Request Endorsement" button and not the horizontal tab.
- **Question for Paul:** Which attribute decides dedicated status for the member side? `allows_dedicated_status`, `dedicated_type`, or both?

### Conflict 5 — "Fully compliant" vs "Phone number required" on the same page

- `nextStep()` ([dashboard.blade.php:462](resources/views/pages/member/dashboard.blade.php)) never checks `empty(phone)`. For a dedicated-status member whose knowledge test, docs and activities are all green it returns `['key' => 'compliant', 'title' => "You're Fully Compliant", ...]`.
- The phone-missing banner is rendered independently just above the next-step card ([dashboard.blade.php:675](resources/views/pages/member/dashboard.blade.php)) using `empty($this->user->phone)`.
- The two can therefore co-exist on the same screen, as the brief points out.
- **Question for Paul:** Is a missing required profile field a blocker that should push the member out of "compliant"? And what counts as "required" on the profile beyond phone (ID number, date of birth, physical address, postal address)?

### Conflict 6 — "Fully compliant" vs "0/0" on the same page

- `nextStep()` for a lifetime dedicated-status member calls `endorsementEligibility`, which short-circuits activities as `met = true, required = 0`. So the next-step card says "You're Fully Compliant".
- The "Compliance Status" strip card uses `activityCompliance` (ShootingActivity::complianceSummary) and renders "0 / 2".
- Same root cause as Conflict 1. Called out separately because Phase 1 has to make the next-step card and the compliance card say the same thing.
- **Question for Paul:** Is `nextStep` using the right source today? Phase 2 moves to `MemberActions`, so this gets fixed there, but we need to know whether the lifetime answer "compliant / no activities needed" is correct in both cards.

### Conflict 7 — Lifetime membership check uses a non-existent attribute on the card page

- [resources/views/pages/member/card.blade.php:99](resources/views/pages/member/card.blade.php) reads `$this->membership->type?->is_lifetime`. `MembershipType` has no `is_lifetime` column, cast or accessor; the method is `isLifetime()`.
- Every other call site uses `isLifetime()` ([dashboard.blade.php](resources/views/pages/member/dashboard.blade.php), [Membership.php](app/Models/Membership.php), [MembershipType.php](app/Models/MembershipType.php) and others).
- Effect today: `isLifetime` on the card page always returns `false`, so a lifetime member's card never follows the lifetime branch of its own component. Visible rendering seems unaffected because the card template re-checks `$this->activeMembership->type->isLifetime()` directly rather than the computed property, but this is a latent bug.
- **Question for Paul:** Fix in Phase 0's follow-up (one-line change, no behaviour change), or defer to Phase 1 under the resolver?

### Conflict 8 — "Dedicated Status" tab and "Endorsements" sidebar entry both point at `/endorsements`

- [partials/member-nav-tabs.blade.php:77](resources/views/partials/member-nav-tabs.blade.php) renders a tab labelled "Dedicated Status" linking to `route('member.endorsements.index')`.
- [SidebarMenu.php:122](app/Helpers/SidebarMenu.php) renders a sidebar entry labelled "Endorsements" linking to the same route.
- The brief calls this out: "Dedicated status is reachable by that name". Noted here so the inventory is complete; the actual decision is a Phase 6 topic.

### Conflict 9 — `idDocumentStatus` ignores one of its own ID slugs

- [app/Models/MemberDocument.php:90](app/Models/MemberDocument.php) `ID_DOCUMENT_SLUGS = ['identity-document', 'id-document', 'id-copy']`.
- [dashboard.blade.php:160](resources/views/pages/member/dashboard.blade.php) only looks at `['identity-document', 'id-document']`.
- [EndorsementRequest::getMissingRequiredDocuments:503](app/Models/EndorsementRequest.php) also only looks at `['identity-document', 'id-document']`.
- A member who uploaded an ID under `id-copy` is treated as missing their ID everywhere on the member side. If `id-copy` is dead data we should delete it from the constant; if it is live we should include it in every lookup.
- **Question for Paul:** Is `id-copy` a real document type slug anyone uses today? If yes, include it; if no, delete the constant entry.

### Conflict 10 — `latestMembership` picks revoked/old rows for display

- [dashboard.blade.php:67](resources/views/pages/member/dashboard.blade.php) `latestMembership` is `memberships()->latest()->first()` with no status filter.
- `nextStep()` ([dashboard.blade.php:479](resources/views/pages/member/dashboard.blade.php)) branches on `latestMembership?->status === 'applied'`. A member whose newest row is `revoked` or `expired` does not get the "awaiting review" step but also does not get a different one — they fall through to `null`.
- The "Membership Status" fallback block below ([dashboard.blade.php:1830](resources/views/pages/member/dashboard.blade.php)) renders a revoked/suspended/expired badge based on `latestMembership`.
- **Question for Paul:** For a member whose only membership rows are revoked or expired, what should the dashboard say in the next-step slot — "Reapply", "Contact NRAPA", or "Renew"?

### Conflict 11 — Rejected items are the top of the next step only for dedicated members

- [dashboard.blade.php:545](resources/views/pages/member/dashboard.blade.php): the `fix_rejected` branch of `nextStep()` sits inside an `if ($this->hasDedicatedMembership)` block, below `return null` for non-dedicated members.
- A basic member whose uploaded ID is rejected sees the red "Documents Rejected" alert further down the page, but their "Your Next Step" card can simultaneously say "Your Membership is Active — explore the portal".
- **Question for Paul:** Should rejected items always rank first in the next-step card, regardless of membership type? The brief's Phase 2 ordering already assumes yes; this is a check that we read it correctly.

### Conflict 12 — Endorsements eligibility list and compliance bar disagree on activities on the same page

- The top compliance bar on `/endorsements` uses `ShootingActivity::complianceSummary` ([endorsements/index.blade.php:129](resources/views/pages/member/endorsements/index.blade.php)) → counts previous-year activities.
- The eligibility checklist on the same page uses `EndorsementRequest::getEligibilitySummary` ([endorsements/index.blade.php:490](resources/views/pages/member/endorsements/index.blade.php)) → counts current-year activities.
- Same root cause as Conflict 3; noted here because it is a user-visible contradiction on a single page.

---

## 4. Requirement × member-type matrix

How each requirement currently behaves for each kind of member on the member side. Reading guide: ✓ = currently renders as "met / complete / valid", ✗ = currently renders as "action required / not met / missing", ⊖ = renders as something irrelevant (e.g. "0/0"), empty = requirement does not surface at all for that member type.

| Requirement | Annual, with gaps | Annual, in good standing | Lifetime (dedicated) | Lifetime (basic) | Honorary / exempt (if type exists) | Member in join year | No membership row |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Membership paid-up | ✗ (renew banner, expired block) | ✓ (Paid-up chip) | ✓ (Lifetime) | ✓ (Lifetime) | — (no code path — see Q6) | ✓ | ✗ (apply block) |
| ID document on file | ✗ (ID row + next-step upload) | ✓ | ✓ | ✓ | ✓ | ✗ if missing | — |
| Required documents (endorsement) | ✗ in eligibility card if dedicated; otherwise not rendered | ✓ if dedicated | ✓ if dedicated | — (card hidden) | depends on `dedicated_type` | ✗ if dedicated and missing | — |
| Knowledge test | ✗ "Required" if `type.requires_knowledge_test` | ✓ "Complete" if passed | ✓ if passed; ✗ if not — the test is still required when `type.requires_knowledge_test` is true, lifetime or not | ✗ as above | ✗ as above | ✗ as above | — |
| Activities (dashboard compliance card) | depends on `complianceSummary` | ✓ | **⊖ "0 / 2"** (Conflict 1) | — (card hidden; `hasDedicatedMembership` is false) | **⊖** likely (Conflict 1 scope) | ✗ unless previous year counts carried | — |
| Activities (eligibility card inside the dashboard) | ✗ with count | ✓ with count | **⊖ "0/0 Complete"** (Conflict 1) | — (card hidden) | **⊖** | ✓ "Exempt" if `isInJoinYear` | — |
| Firearm licence expiry (virtual safe) | ✗ red/amber chip if any expired/≤90 | ✓ Active count | ✓ | ✓ | ✓ | ✓ | — |
| Endorsement eligibility ("Request Endorsement" button) | ✗ unless all three green | ✓ when all three green | ✓ (activities short-circuit); gate still needs `dedicated_type` in `hunter`/`sport`/`both` | hidden | depends on `dedicated_type` | ✓ (activities exempt) | — |
| Phone number on profile | ✗ banner + pulse dot | ✓ silent | ✗ same banner | ✗ same banner | ✗ same banner | ✗ same banner | ✗ same banner |

Notes:

- "Honorary / exempt" row: no explicit flag in the current schema. There is no `membership_types.is_honorary` or `is_exempt` boolean, and no "exempt" status on `memberships`. The nearest thing is `MembershipType::isLifetime()`. Paul to confirm whether honorary members exist today and how they are modelled.
- "No membership row": [`User::activeMembership`](app/Models/User.php) returns null, `needsMembership` is true, the dashboard shows the apply block and the quick-links grid is hidden.
- Lifetime basic members (type with `duration_type = lifetime` AND `allows_dedicated_status = false`): the entire eligibility card and the compliance strip card are hidden because `hasDedicatedMembership` is false. Only the Firearm Status card renders in the status strip.

---

## 5. Sidebar and quick-link badges — what they count

### 5a. Sidebar badges (number in a pill on the right of the item)

Rendered by [resources/views/partials/sidebar-menu-item.blade.php:132](resources/views/partials/sidebar-menu-item.blade.php) whenever `pending_count > 0`. Sources from [app/Helpers/SidebarMenu.php](app/Helpers/SidebarMenu.php):

| Sidebar item | Section | Source | Label it needs |
| --- | --- | --- | --- |
| **Messages** | Member Area | `$user->unreadMessages()->count()`, cached per user for 60 s. `unreadMessages` = admin→member messages with `read_at` null. | "Unread messages from NRAPA". |
| **Approvals** | Administration | `ApprovalQueue::sidebarTotal()`. | Admin-side; out of scope. |
| **Inbox** | Administration | `MemberMessage::unreadByAdmins()->count()`. | Admin-side; out of scope. |

No sidebar items for members carry a numeric badge today **except** Messages. There is no "1" on My Profile and no "3" on Learning Center in the sidebar.

### 5b. Horizontal tab row badges

[partials/member-nav-tabs.blade.php](resources/views/partials/member-nav-tabs.blade.php) renders no badges at all — the whole thing is an active/inactive outline.

### 5c. Dashboard "Quick Links" badges

| Quick-link tile | Badge rendered | What it counts | Four-word label candidate |
| --- | --- | --- | --- |
| Membership Card | none | — | — |
| My Profile | small yellow pulsing dot (not a number) | `empty($this->user->phone)` ([dashboard.blade.php:1583](resources/views/pages/member/dashboard.blade.php)). The brief's "1" is this dot — a visual nudge, not a count. | "Phone number missing". |
| Virtual Safe | numeric | `$this->firearmCounts['total']` — all firearms, active or expired. The brief's "Exempt" badge is actually on the Activities tile, not here. | "Firearms in your safe". |
| Activities (dedicated only) | "Exempt" blue pill **OR** `approved / required` emerald/amber pill | `$this->activityCompliance['exempt']` from `complianceSummary` is-join-year-exempt flag. Otherwise the current-year ratio. | "Activities this year". |
| Endorsements (dedicated only) | none | — | — |
| Documents | none | — | — |
| Learning Center | none | The brief's "3" is not in the current code. Possibly from a mock-up. If Paul wants a count here, we need to agree what it counts (unread articles, uncompleted articles, something else). | — |
| Certificates | numeric | `$this->certificates->count()` — valid, non-welcome, non-card certificates in the latest 3. | "Valid certificates issued". |
| Payments | none | — | — |

Summary of the brief's "1 / 3 / Exempt":

- "1" on My Profile → actually a pulse dot driven by `empty(phone)`.
- "3" on Learning Center → **not present in the current code**. Needs Paul's intent.
- "Exempt" on Virtual Safe → actually on the Activities tile. Driven by `isInJoinYear()`.

The quick-links grid itself is removed in Phase 3, so the badges only need labels where they survive the move to the sidebar.

---

## 6. Brief versus codebase — mismatches to confirm before Phase 1

- **Database.** The brief says PostgreSQL. [docker-compose.yml](docker-compose.yml) and the production AGENTS notes both say MySQL 8.0, and `complianceSummary` uses `sum(track = 'hunting')` while `idDocumentStatus` and the activities history query use MySQL-specific `FIELD()` and `YEAR()`. The resolver should avoid driver-specific SQL regardless, but we should not plan around Postgres features.
- **`Member` model.** The brief writes `MemberCompliance::for(Member $member)`. There is no `Member` model. Members are [`App\Models\User`](app/Models/User.php) with `role=member` (or admin/owner/developer viewing as a member via the session flag). The resolver signature will be `for(User $user)` unless Paul says otherwise.
- **Domain.** The brief names the site `nrapa.ranyati.co.za`. The current production hostname and canonical URL need confirming on live before Phases 2–3 ship.
- **"Dedicated status" naming.** The brief says "Dedicated status is reachable by that name" and "either give it its own route or label the link honestly". Today both the sidebar entry (Endorsements) and the horizontal tab (Dedicated Status) point at `/endorsements`. Phase 6 territory, not Phase 1.
- **Honorary / exempt members.** The brief lists them as a member type. No such flag or type exists in the schema today. The join-year exemption is the only "exempt" code path. If honorary members exist and need distinct handling, the resolver needs an attribute to drive them off (see Question 6).
- **"Learning Centre" badge of 3.** The code renders no badge on Learning Center. The brief treats this as a known badge; we need Paul to say what it is meant to count before we add it.

---

## 7. Current next-step order vs the brief's proposed order

### 7a. What `nextStep()` on the dashboard does today

In order of priority ([dashboard.blade.php:462](resources/views/pages/member/dashboard.blade.php)):

1. `apply` — if `needsMembership` (no active, no latest).
2. `null` — if an `awaitingPaymentMembership` exists (hands over to the payment card above).
3. `awaiting_review` — if `latestMembership->status === 'applied'`.
4. `null` — if `expiredMembership` (hands over to the expired block).
5. `id_upload` (missing / rejected) or `id_pending` — based on `idDocumentStatus`.
6. `explore` — active membership that is not dedicated.
7. `null` — not dedicated and none of the above matched.
8. `fix_rejected` — any active rejected documents or activities.
9. `knowledge_test` → `documents` → `activities` — from `endorsementEligibility`.
10. `compliant` — all three green.

Not represented at all: missing phone (or any other required profile field).

### 7b. Brief's proposed order (confirmed with Paul before building Phase 2)

From the brief, highest first:

1. Something NRAPA has sent back for changes (empty until Phase 4).
2. Membership unpaid or expired.
3. Required document missing or expired.
4. Required profile field missing (phone is the current case).
5. Knowledge test not passed.
6. Activity requirement not yet met this year.
7. Firearm licence in the virtual safe expiring within 90 days.

Delta the member will see when we swap in the brief's order:

- A missing phone will appear in the next-step card (not just as a yellow dot elsewhere). Conflict 5 resolved.
- Firearm licence expiry within 90 days becomes an action. Today it is a chip on the "Firearm Status" card only.
- "Explore" / "Fully compliant" messaging disappears for a lifetime basic member who does nothing wrong — the brief's step 7 (firearm licence expiring) may fire, otherwise the empty state (good standing) renders.

Nothing is decided here. Phase 2 proposes the final order and Paul signs off before it ships.

---

## 8. Questions for Paul — answered

Paul answered these in chat on 12 Oct 2026. Transcribed verbatim where quoted. Where he said "you recommend", my recommendation is written in and marked as needing a one-line "OK" before Phase 1 ships.

Two items still need a second pass before Phase 1 (flagged **🔁 needs confirmation**): the dedicated-status gate (Q4, could silently remove the Dedicated Status tab from members on the `transfer` type), and the honorary/exempt modelling (Q6).


1. **Lifetime + activities (Conflicts 1 and 6).** **Answer: b.** Show *"Compliant"* with the Lifetime badge, no numbers. Resolver returns `not_applicable` for the activities requirement when `type.isLifetime()`, with the reason sentence *"Lifetime members are always compliant"*. The compliance strip card and the eligibility card both render the Lifetime badge in place of the "0 / 2" ratio.

2. **Activity count source (Conflict 2).** **Answer: a.** Keep the `endorsement_min_activities` setting, route every page through it. Paul added *"lifetime members will always be compliant"* — reinforces Q1, handled by the exemption branch so the setting's value never reaches the UI for lifetime members.

3. **Activity window (Conflicts 3 and 12).** **Answer: a.** Previous year Jan 1 – Oct 31 proves compliance for the current year; current year Jan 1 – Oct 31 banks for next year. `complianceSummary` wins; `checkActivityRequirements`'s current-year window and `endorsements/index`'s Jan–Sep label both go.

4. **Dedicated status (Conflict 4).** **Answer: d — Both (AND).** 🔁 **Needs confirmation before Phase 1.** The live seeder has one active type with `allows_dedicated_status = true` **and** `dedicated_type = null`: the **`transfer`** membership type ([MembershipConfigurationSeeder.php:379](database/seeders/MembershipConfigurationSeeder.php)). Strict AND would mean members on `transfer`:
   - Lose the "Dedicated Status" tab on the horizontal nav today (comes from `Membership::allowsDedicatedStatus()`).
   - Lose the "Dedicated Status Requirements" card on the dashboard today (comes from the dashboard's OR check).
   - Keep the current eligibility behaviour (they already cannot request endorsements because `dedicated_type` is null).

   Two ways to apply the answer:
   - **4a (strict AND):** apply AND everywhere. `transfer` members lose the tab and the card. One clean rule.
   - **4b (hybrid, "don't change processes"):** AND for *actionable* eligibility (`checkUserEligibility` already does this — unchanged); OR for *display* (keep showing the Dedicated Status surface so `transfer` members still see where their journey is heading). Matches today's visible behaviour exactly.

   My recommendation under the guardrail: **4b**. One flag on your end: OK with 4b, or do you want strict 4a?

5. **Required profile fields (Conflict 5).** *Not answered in chat.* **My recommendation:** phone only, matching today. The brief singles out phone as "the current case". Adding ID number / date of birth / address to the next-step card would introduce new blockers for members who have been running for years without them — violates the "no new blockers" guardrail. Keep phone; any other field becomes mandatory later in a dedicated phase with notification. **Needs a one-line "OK".**

6. **Honorary / exempt members.** **Answer: two tags.** Paul: *"you recommend. but i would expect lifetime. can we have the two tags."* 🔁 **Needs confirmation before Phase 1.** My proposal:
   - Lifetime stays driven by the existing `MembershipType.duration_type = 'lifetime'` attribute — no schema change needed.
   - Add a new boolean attribute `MembershipType.is_honorary` (new migration, defaults `false`). An honorary member joins on a type whose `is_honorary = true`.
   - The resolver's "exempt from activities" branch fires when `type.isLifetime() OR type.is_honorary` — the "two tags" Paul asked for.
   - No existing type gets `is_honorary = true` by default. Honorary types are an admin-created artefact going forward.
   - Nothing on-screen changes for anyone today — zero live members affected until an honorary type is created.

   OK with per-type `is_honorary`, or do you want it per-member (a `users.is_honorary` flag) instead?

7. **Rejected items ordering (Conflict 11).** *Not answered in chat.* **My recommendation:** always first, for every membership type. Matches the brief's Phase 2 ordering (rank 1) and the admin brief, and does not add any blocker — it just promotes an alert that already shows further down the page today. **Needs a one-line "OK".**

8. **ID document slugs (Conflict 9).** **Answer: a.** Yes, `id-copy` is a real slug — include it everywhere. Both `idDocumentStatus` on the dashboard and `getMissingRequiredDocuments` on `EndorsementRequest` get the full `MemberDocument::ID_DOCUMENT_SLUGS` list via the resolver in Phase 1.

9. **Lifetime check on card page (Conflict 7).** *Not answered in chat.* **My recommendation:** defer to Phase 1. The typo (`is_lifetime` vs `isLifetime()`) is latent — the card template already re-checks `type->isLifetime()` on the next line so no member sees a wrong card today. Phase 1's resolver absorbs it as the first commit, no behaviour change. **Needs a one-line "OK".**

10. **"Dedicated Status" naming (Conflict 8, Phase 6 topic).** **Answer: Dedicated Status.** Both the sidebar entry (currently "Endorsements") and the horizontal tab stay/get labelled *"Dedicated Status"*. Phase 6 renames the sidebar entry; the route (`member.endorsements.index`) is unchanged. The "Endorsements" label dies.

11. **Members with only revoked / expired memberships (Conflict 10).** **Answer: Paul's recommendation.** My proposal, differentiated by status so the member gets the right action:
    - `expired` → *"Renew your membership"*, CTA *Renew*, routes to `/membership`. Matches the current renewal flow.
    - `revoked` → *"Your membership was revoked. Please contact NRAPA."*, CTA *Contact NRAPA*, routes to `/messages/new`. No self-service reactivation — admin deals with it.
    - `suspended` → *"Your membership is suspended. Please contact NRAPA."*, same CTA. Suspended is temporary; admin lifts it.

    **Needs a one-line "OK".**

12. **"Learning Center 3" badge.** **Answer: Paul's recommendation.** My proposal: treat as a mock-up artefact. **Do not add the badge in Phase 3.** If you ever want "N new articles since you last visited", the `learning_article_reads` pivot already captures per-user read state and we can light it up in a half-day follow-up. Adding a count that does not actually tell the member anything would be noise. **Needs a one-line "OK".**

13. **`Member` model vs `User`.** **Answer: Yes.** The resolver type-hints `App\Models\User`: `App\Support\Members\MemberCompliance::for(User $user)`. No new `Member` model.

14. **Production hostname.** **Answer: Paul's recommendation.** My proposal: **`https://nrapa.ranyati.co.za`** for Phase 1+ live checks. Confirmed by [DEPLOYMENT.md:215](DEPLOYMENT.md) and [OPERATIONS.md:124](OPERATIONS.md) as the current live test / staging domain. If and when `members.nrapa.co.za` goes live, Phase 2+ live checks move with it. **Needs a one-line "OK".**

### Items still blocking Phase 1

Phase 1 planning starts after you've given me a one-line "OK" (or an alternative) on each of these:

- **Q4** — 4a (strict AND, `transfer` members lose the tab) or 4b (display OR, eligibility AND, no visible change)?
- **Q5** — phone-only required profile field for Phase 2?
- **Q6** — `MembershipType.is_honorary` boolean, lifetime unchanged, resolver reads either as exempt?
- **Q7** — rejected items always first on every member's next-step card?
- **Q9** — fold the `is_lifetime` typo fix into Phase 1 rather than a Phase 0 follow-up?
- **Q11** — differentiated copy for expired / revoked / suspended as above?
- **Q12** — no badge on Learning Center in Phase 3?
- **Q14** — `https://nrapa.ranyati.co.za` as the live-check host for every phase?
