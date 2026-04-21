# Upkeepify TODO

## Release Planning

- [x] Adopt semantic versioning rules for `major`, `minor`, and `patch` releases.
- [x] Add a repo script to auto-bump plugin metadata files together.
- [ ] Automate changelog/release note scaffolding as part of the release workflow.
- [x] Automate production zip creation from a release-oriented file list that stays lightweight and WordPress.org-ready.

## End-to-End Lifecycle Gaps

- [x] Add a trustee lifecycle panel on maintenance task edit screens.
- [x] Require trustee estimate approval before a contractor can submit a formal quote.
- [x] Require trustee quote approval before a contractor can mark work complete.
- [x] Notify contractors when trustee approval unlocks their next step.
- [x] Add a trustee action to resolve a resident-reported issue after contractor follow-up.
- [x] Add a trustee action to re-request resident confirmation after contractor follow-up.
- [x] Add lifecycle status columns/filters for pending estimate approval, pending quote approval, awaiting completion, awaiting resident confirmation, and needs review.
- [x] Sync visible task status terms with lifecycle state, especially quote approved, completed, resident confirmed, and resident issue reported.
- [x] Add token revoke/regenerate controls for contractor response links.
- [x] Add a manual resident-confirmation or manual-close path for tasks submitted without resident email.
- [x] Add end-to-end tests covering two contractors, trustee approval selection, quote approval, completion, resident dissatisfaction, contractor follow-up, and trustee resolution.
