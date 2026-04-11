# Upkeepify — Product Direction & Roadmap

## AI Review Priority

If Claude or any other AI coding assistant is asked to plan or implement work in this repo, review these resolved guardrails before changing related flows:

1. Public task submission exposes and saves only resident-facing taxonomies (`task_category` and `task_type`).
2. Public submissions start as `pending`; contractor response generation happens only after trustee approval/publish and must not duplicate existing provider responses.
3. `Allow Public Task Logging` is enforced in both the shortcode render path and submission handler.
4. Database optimization avoids duplicate `postmeta(meta_key, post_id)` index definitions, and PHPCS uses available sniff references. The lint command is operational, though broader findings remain.

Treat these as architectural and product guardrails for future work.

## Table of Contents
- [1. Product Statement](#1-product-statement)
- [2. Design Rules](#2-design-rules)
- [3. The Response Lifecycle](#3-the-response-lifecycle)
- [4. Roadmap](#4-roadmap)
- [5. Trust & Bad Actor Controls](#5-trust--bad-actor-controls)
- [6. Benchmark Network](#6-benchmark-network)
- [7. Progressive Web App](#7-progressive-web-app)
- [8. Licensing & Monetisation](#8-licensing--monetisation)
- [9. Architectural Guardrails](#9-architectural-guardrails)

---

## 1. Product Statement

Upkeepify helps HOAs move from reported issue to confident action with minimal friction. Residents report problems easily, contractors give low-cost early estimates without a site visit, trustees make auditable decisions, and the whole path from estimate to completion stays lightweight and trackable.

Contractors build portable reputations. Trustees plan budgets against real regional benchmarks. The system stays honest through variance scoring, token hygiene, and a mandatory human triage gate. It scales from a single complex to a network of HOAs sharing anonymised cost intelligence — without ever becoming molasses.

**What this is not:** a contractor marketplace, a vendor portal, or a project management tool. Complexity stays proportional to the problem.

---

## 2. Design Rules

These are non-negotiable and must be preserved through every future change:

1. **No login for residents or contractors.** The moment a login is required, submission rates drop and contractor participation friction rises. Token-gated access only.
2. **Every state transition requires as little typing as possible.** Structured fields over free text. Suggestions over blanks. Confirmations over forms.
3. **Trustees see one clear timeline.** Issue → invite → estimate → quote → completion → resident confirmation. No hidden state, no parallel workflows.
4. **Auditability comes from captured transitions and variance data**, not from admin ceremony or manual record-keeping.
5. **The cost curve must be flat.** Any feature that introduces ongoing per-usage cost or a vendor dependency that scales with submissions will eventually get cut, gamed, or charged for. Design it out.

---

## 3. The Response Lifecycle

The response post (already the core data carrier) becomes the single state machine for each contractor/task pairing.

```
SUBMITTED → INVITED → ACCEPTED/DECLINED → ESTIMATED → QUOTED → COMPLETED → CONFIRMED
```

| State | Actor | Key data captured |
|---|---|---|
| Submitted | Resident | Title, description, photo, category, optional location |
| Invited | System | Tokenized link sent to relevant contractors by category |
| Accepted / Declined | Contractor | Accept or pass (no ambiguous non-response) |
| Estimated | Contractor | Ballpark figure, optional range + confidence, availability, short note |
| Quoted | Contractor | Formal quote amount, updated availability |
| Completed | Contractor | Completion photos, confirmation of work done |
| Confirmed | Resident | Up/down vote via tokenized link |

Each transition is timestamped and immutable. The estimate and formal quote are separate records — the delta between them is what feeds variance scoring.

### Data model additions required

- Status field on response post (controlled vocabulary, not free text)
- Structured estimate fields: `estimate_low`, `estimate_high`, `estimate_confidence`, `availability_date`, `note`
- Formal quote field: `formal_quote_amount`
- Completion photo attachments (same pattern as task photo, linked to response post)
- Resident confirmation field: `resident_confirmed` (boolean + timestamp)
- Resident confirmation token (separate from contractor token, issued at task creation)

---

## 4. Roadmap

Sequenced by dependency and impact. Each step should be solid before the next begins.

### Step 1 — Tokenized contractor invite email, filtered by category
The single highest-leverage change. Tokens already exist but go nowhere. Add a `wp_mail()` call on task publish, filtered to contractors whose `service_provider` taxonomy term matches the task category. This makes the existing contractor flow immediately functional without redesigning anything.

**Guardrails:** token expiry (14 days default), revoke/regenerate at trustee discretion, one-click reissue if email is forwarded to the wrong person.

### Step 2 — Structured contractor response form
Replace the single textarea with a minimal structured form:
- Accept / Decline (required)
- Ballpark estimate (required on accept)
- Estimate range and confidence (optional)
- Earliest availability (optional)
- Short note (optional, capped length)

Declined responses close that contractor's token. Accepted responses move to Estimated state.

### Step 3 — Formal quote + completion proof
Same token, later form state. Contractor records:
- Formal quote amount
- Completion photos (upload, same mechanism as task photo)

Completion photos trigger resident notification.

### Step 4 — Resident confirmation token flow
At task submission, generate a second token for the original submitter. On contractor completion, send the resident a tokenized link with a simple up/down confirmation. No login, one click. Confirmation closes the lifecycle.

### Step 5 — Variance scoring and contractor reliability views
Trustees see, per contractor:
- Estimate vs. formal quote delta, per job and as a trend
- Completion confirmation rate
- Average time from acceptance to completion

This makes serial under-estimators visible over time without requiring trustees to track it manually. No automated penalty — trustees decide what to do with the information.

### Step 6 — On-device photo-assisted issue classification
Residents take a photo; the phone's own AI (Apple Intelligence, Google Gemini Nano, etc.) suggests a category from the controlled taxonomy. The plugin accepts it as a pre-filled hint that the resident can confirm or change. Implemented as progressive enhancement in JavaScript — works better on newer devices, falls back to a plain dropdown everywhere else.

No API calls. No cost. No vendor dependency. Inference never leaves the device. Feature improves automatically as on-device AI improves.

**Critical dependency:** category taxonomy must be tight, stable, and centrally controlled. Vague or overlapping categories produce noisy suggestions and degrade benchmarking.

### Step 7 — Opt-in benchmark network
After the local workflow is solid and data is accumulating. See Section 6.

---

## 5. Trust & Bad Actor Controls

### Resident side — spam and fake submissions
- Math captcha (already in place) as a first layer
- IP rate limiting on form submission
- Category and description minimums (partially in place)
- **Primary gate:** trustee triage is mandatory before any contractor is invited. No automated flow reaches contractors without a human checkpoint. This one rule eliminates most resident-side abuse.

### Contractor side — estimate gaming
- Estimates are timestamped and immutable once submitted
- Formal quote is a separate record; delta is computed and stored
- Trustees see estimate vs. quote variance per contractor over time (Step 5)
- Contractors with persistent low-estimate/high-quote patterns are flagged for trustee review — not automatically penalised

### Token hygiene
- Tokens are long, random, non-sequential (already using `wp_generate_password(20, false)`)
- Time-bound expiry (14 days default, configurable)
- Single-use state transitions — a token cannot replay a completed action
- Revoke and regenerate at trustee discretion without restarting the task
- Separate tokens for contractors and residents

### Benchmark network — data poisoning
- HOA identity verified at opt-in (not self-declared)
- Outlier filtering before aggregation
- Category and region taxonomy controlled centrally — no free text
- Contribution volume thresholds per category before a data point enters distribution
- Anomaly detection on submission bursts

---

## 6. Benchmark Network

### What it is
HOAs that opt in contribute anonymised job records to a central aggregation service. The plugin receives benchmark distributions back as read-only reference data. Trustees can sanity-check contractor estimates against regional norms before committing.

### What is anonymised
| Data | Shared | Notes |
|---|---|---|
| Task category | Yes | Must match controlled taxonomy |
| Region (suburb/postcode level) | Yes | Not full address |
| Ballpark estimate | Yes | |
| Formal quote | Yes | |
| Final cost (if recorded) | Yes | |
| Contractor identity | No | Never leaves the instance |
| Completion photos | No | Never leave the instance |
| Resident identity | No | Never leaves the instance |
| HOA identity | No | Anonymised at submission |

### What trustees get back
Not a single number — a distribution. "Roof gutter clearing in your region: median $X, 80th percentile $Y, typical range $Z–$W, based on N jobs." Useful for sanity-checking estimates before accepting.

### Opt-in model
- Per-HOA opt-in (off by default)
- Per-task opt-out flag for sensitive jobs (security systems, structural issues, etc.)
- A blanket HOA opt-in with per-task exclusion is the right shape

### Contractor reputation portability (network-level)
Contractors who opt in carry estimate accuracy and completion quality scores across HOAs. A contractor who performs well for one body corporate becomes discoverable by another in the same region. This is the core network effect — more valuable as adoption grows, and gives contractors a direct incentive to perform honestly.

### Predictive budget modelling
Once regional benchmark data is established: given a building's age, type, and category history, project likely maintenance spend over 1–3 years. Moves trustees from reactive to genuinely strategic. Feeds directly into reserve fund planning.

### Insurance and compliance record export
The completed lifecycle record — issue, estimate, quote, before/after photos, resident confirmation — is a ready-made audit trail. One-click PDF export per job, useful for insurance claims, AGM reporting, and strata compliance. No new data collection required.

---

## 7. Progressive Web App

### Purpose
A PWA adds an app-like feel without an app store, separate codebase, or install friction. Residents and contractors tap "Add to Home Screen" once; from then on it behaves like a native app — icon on the home screen, push notifications, offline capability. No separate iOS or Android build required.

### Scope (strict)
The PWA wraps three surfaces only:

| Surface | Who | Why |
|---|---|---|
| Resident submission form | Residents | Fast, camera-ready, offline-capable issue reporting |
| Contractor response form | Contractors | Notification-driven, token-gated job handling |
| Trustee task list | Trustees | At-a-glance triage and timeline view |

The WordPress admin panel is explicitly out of scope. Wrapping the full admin produces a slow, unfocused experience. Trustees who need deep admin access use the browser as normal.

### What it requires

**Web App Manifest**
A JSON file declaring the app name, icons, theme colour, and display mode (`standalone` so the browser chrome disappears). Trivial to add, served as a static file and linked from the relevant pages.

**Service Worker**
Handles two jobs:
- *Offline support* — caches the submission form shell so residents can start a report with no signal. Draft is stored in IndexedDB and synced when connectivity returns.
- *Push notifications* — receives push events from the server and surfaces them as native alerts, even when the browser is closed.

Requires HTTPS, which is already a WordPress best practice and a hard browser requirement for service workers.

**Push notification infrastructure**
- On PWA install, the browser issues a push subscription endpoint unique to that device
- The plugin stores the endpoint server-side (linked to the contractor token or trustee user)
- At the right lifecycle events (task published, contractor invited, resident confirmation requested) the plugin sends a Web Push message to the stored endpoint
- No third-party push service required — Web Push is a browser standard using VAPID keys

### Notification triggers by role

| Event | Resident | Contractor | Trustee |
|---|---|---|---|
| Task submitted | Confirmation | — | New submission alert |
| Contractor invited | — | Job notification (primary) | — |
| Estimate received | — | — | Alert |
| Quote received | — | — | Alert |
| Work completed | Confirmation prompt | — | Alert |
| Resident confirmed | — | — | Lifecycle closed |

Email remains a fallback for all contractor notifications. Push is primary if the PWA is installed; email fires regardless as a safety net.

### Offline behaviour
- Resident form: fully available offline. Submission queues locally and syncs on reconnect.
- Contractor form: available offline for viewing task details. Submission requires connectivity (token validation is server-side).
- Trustee list: cached for read-only offline viewing. Actions require connectivity.

### Implementation sequence
1. Web App Manifest + icons (trivial, ship early for home screen install)
2. Basic service worker with static asset caching (page speed improvement, no offline UX yet)
3. Offline-capable resident submission form with IndexedDB draft queue
4. VAPID key generation and push subscription storage
5. Push notification triggers at each lifecycle event
6. Trustee task list caching

Steps 1–2 can ship with Step 1 of the main roadmap. Steps 3–6 align with Step 4 (resident confirmation) when the full lifecycle is in place.

---

## 8. Licensing & Monetisation

### Open source, commercially licensed
The code is public on GitHub — readable, auditable, forkable — but the licence restricts commercial use without purchasing. This is a well-trodden model (Business Source Licence, GPL with commercial exception). Recommended approach:

- **Business Source Licence (BUSL):** code is open, free for non-production or single-complex use, commercial licence required beyond that threshold.
- Alternatively: free tier is MIT/GPL; multi-complex features require a licence key validated against a lightweight licensing server.

Note: if distributed through WordPress.org, GPL compatibility is required. Independent distribution gives more flexibility. Legal advice recommended before publishing.

### Tiers

| Tier | Price | Includes |
|---|---|---|
| Single complex | Free | Full lifecycle, local data only |
| Multi-complex | Small annual fee | Multiple complexes under one install, benchmark network participation |
| Network membership | Included with paid | Benchmark data contribution and read access, contractor reputation network |

### Why this works
The benchmark network gets better as paid adoption grows. Contractors have a reason to perform well because their reputation travels. HOAs have a reason to upgrade because regional benchmarks are more useful with more data. The incentives align without requiring a marketplace or heavy platform play.

---

## 9. Architectural Guardrails

- **Trustee triage is a mandatory gate.** Nothing automated reaches contractors without a human checkpoint.
- **State vocabulary stays small and human.** Avoid sub-states, parallel workflows, or status proliferation.
- **The response post is the single state carrier.** All lifecycle data hangs off it. No parallel data structures for the same job.
- **Token hygiene is not optional.** Expiry, revocation, and single-use transitions must be in place before the invite email ships.
- **Category taxonomy is controlled centrally.** Free-text categories break both on-device classification and benchmark aggregation. Lock it down early.
- **Flat cost curve.** No feature should introduce per-submission API cost. On-device AI, not cloud AI. Local inference where possible.
- **Export before sync.** The anonymisation and export layer should be designed and reviewed before any data leaves an instance. Retrofitting privacy is harder than building it in.
