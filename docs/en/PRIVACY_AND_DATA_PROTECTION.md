# AUB — Privacy and Data Protection

> This is technical/project documentation, **not final legal text**. Legal review by qualified counsel is required before production use and App Store publication.

## Overview

AUB is an academy management system that will process **personal data** and **children's data**. Privacy and data protection must be designed into the system from the start, not added later.

## Role-based access as a privacy requirement

The planned **Staff Roles & Role-Based Workplaces** system is a **privacy requirement**, not only a convenience feature.

- Staff must access only data required for their job
- Teachers should not see payment or parent contact data unless explicitly granted
- Health and sensitive notes require the highest access restriction
- Parent and student access (future mobile channels) must be limited to their own data

Without role-based access control, the system cannot comply with data minimization principles.

## Data controller and processor roles

| Role | Entity | Responsibility |
|------|--------|----------------|
| **Data Controller** (Titolare del trattamento) | The academy (AUB) | Determines purposes and means of processing |
| **Data Processor** (Responsabile del trattamento) | OwlSolutions / developer | Processes data on behalf of the controller when accessing server, database, backups, admin panel, or support tools |

**Recommendation:** Register the server and database under the academy's ownership where possible. Processing agreements (DPA) should be signed between the academy and any technical provider with system access.

## Types of data processed

| Data category | Examples | Sensitivity |
|---------------|----------|-------------|
| Staff personal data | Names, emails, phones of employees | Personal data |
| Student personal data | Names, dates of birth, addresses | **Children's data** |
| Parent/guardian data | Names, emails, phones, addresses | Personal data |
| Financial data | Payments, invoices, debts | Personal + financial |
| Health/sensitive notes | Medical info, special needs | **Special category** (if stored) |
| Consent records | Photo consent, data processing consent | Children's data |
| System logs | IP addresses, login times | Personal data (limited) |
| AI provider keys | API keys in `ai_provider_settings` | Technical secrets |

## Children's data — special care

The academy serves minors. Enhanced protection applies:

- Collect only necessary data fields
- Restrict access by role (see [USER_ROLES_AND_ACCESS.md](USER_ROLES_AND_ACCESS.md))
- Do not display children's data to unauthorized roles
- Plan audit logging for access to student records

### Italy — consent for minors online

Under Italian law (D.Lgs. 196/2003 as amended, GDPR, and related guidance):

- For children **under 14**, online consent for data processing generally requires authorization from a parent or legal guardian
- The academy must obtain and record valid consent before processing children's data through digital channels (web, mobile apps)
- Consent records must be stored with version reference to the privacy policy in effect

**Planned entity:** `ConsentRecord` linked to `PrivacyPolicyVersion` — see [DATA_MODEL_DRAFT.md](DATA_MODEL_DRAFT.md).

## GDPR principles applied to AUB

| Principle | AUB implementation |
|-----------|---------------------|
| Lawfulness, fairness, transparency | Privacy policy, consent records |
| Purpose limitation | Module-based data collection; no scope creep |
| Data minimization | Role-based field visibility; minimal required fields |
| Accuracy | Edit workflows with audit trail (planned) |
| Storage limitation | Retention policies (to be defined with academy) |
| Integrity and confidentiality | Auth, RBAC, encrypted connections (HTTPS), hashed passwords |
| Accountability | Documentation, consent records, access logs (planned) |

## Technical security measures (current and planned)

### Current (implemented)

- HTTPS via Nginx (`https://aub.owlsolutions.net`)
- Session-based authentication
- Password hashing (bcrypt)
- CSRF protection (Laravel + Inertia)
- `.env` secrets not in version control
- AI API keys stored in database (access restricted to authenticated admin)

### Planned

- Role-based access control (Phase 1)
- Field-level visibility by role
- Access audit logging
- Consent management module
- Privacy policy version tracking
- Data export for subject access requests
- Data deletion/anonymization workflows

## App Store and mobile publication

When mobile apps are published (Phase 5):

- Privacy Policy URL required (App Store, Google Play)
- App Privacy Details must accurately describe data collected
- Children's app considerations (COPPA-like requirements, age gates, parental consent)
- Minimum necessary data collection for app functionality

## AI Settings privacy note

The installed AI Settings module (`/ai-settings`) allows configuring external AI provider API keys (OpenAI, Anthropic, Gemini).

- API keys are sensitive — never expose in logs or documentation
- If AI features process personal data in the future, additional DPIA (Data Protection Impact Assessment) may be required
- Document which data is sent to AI providers before enabling production AI features

## Data retention

Retention periods must be defined with the academy owner:

- Student records: typically duration of enrollment + legal retention period
- Financial records: per Italian accounting/tax law (often 10 years)
- Consent records: duration of processing + proof period
- System logs: recommended 90–365 days

**Status:** Retention policy not yet defined — client clarification required.

## Subject rights (GDPR Articles 15–22)

The system should eventually support:

- Right of access (export student/parent data)
- Right to rectification (edit records)
- Right to erasure (with legal exceptions)
- Right to restriction of processing
- Right to data portability

**Status:** Not implemented — planned for later phases.

## Documentation and legal text

| Document | Status |
|----------|--------|
| This technical privacy doc | Current |
| Privacy Policy (public-facing) | Not created — legal review required |
| Terms of Service | Not created — legal review required |
| DPA with processor | Not created — legal review required |
| Cookie policy | Not required yet (session cookies only) |

## Changes requiring privacy doc update

Update this document when:

- New modules collect personal or children's data
- Role permissions change
- Mobile apps are planned or launched
- AI features process user data
- Data is shared with third parties
- Retention or deletion policies are defined

See [DEVELOPMENT_RULES.md](DEVELOPMENT_RULES.md).

## Contact and responsibility

Data protection responsibility lies with the **academy (Data Controller)**. Technical implementation support is provided by the development team as **Data Processor** when applicable.

Final legal texts, consent forms, and privacy policies must be approved by the academy and qualified legal counsel before production use.
