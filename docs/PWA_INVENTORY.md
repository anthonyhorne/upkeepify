# PWA Inventory

Focused inventory against `includes/pwa.php` and `js/service-worker.js`.

## Current Coverage

- [x] Manifest endpoint: `/upkeepify-manifest.json` is served through the `upkeepify_pwa=manifest` rewrite path.
- [x] Manifest injection: front-end pages receive a manifest link and theme color meta tag via `wp_head`.
- [x] Service worker endpoint: `/upkeepify-sw.js` is served through the `upkeepify_pwa=sw` rewrite path.
- [x] Service worker scope: `Service-Worker-Allowed: /` and registration scope `/`.
- [x] Static asset cache: caches core plugin CSS and selected minified JS assets with cache name `upkeepify-v1`.
- [x] Cache cleanup: activation removes older cache names.
- [x] Offline fallback: same-origin GET requests fall back to a matching cached response when network fetch fails.
- [x] Selective registration: service worker registers on pages with selected Upkeepify shortcodes or the provider response token query var.

## Gaps To Scope

- [ ] Icons: manifest currently points both 192x192 and 512x512 entries at `favicon.png`; add real PWA-sized PNG icons.
- [ ] Manifest completeness: add `id`, confirm `start_url` strategy, and decide whether the theme color should be configurable.
- [ ] Registration coverage: add resident confirmation and any other PWA-relevant shortcodes if they should work offline.
- [ ] Offline task submission: no offline queue exists for resident task form submissions.
- [ ] Offline provider flow: no queue exists for provider response, quote, or completion submissions.
- [ ] Offline resident review: no queue exists for resident confirmation submissions.
- [ ] Offline shell: there is no dedicated offline page or user-facing offline message when a request has no cached match.
- [ ] Push/VAPID: no VAPID key storage, subscription endpoint, browser permission flow, or server-side web-push library integration.
- [ ] Subscriptions: no per-user, provider, resident, or trustee subscription model exists yet.
- [ ] Notification triggers: current email lifecycle has no web-push hooks for task approval, provider invite, provider response, completion, or resident confirmation.
- [ ] Trustee cache: no trustee/admin task cache exists beyond generic same-origin request fallback.
- [ ] Cache versioning: cache name is hardcoded to `upkeepify-v1`; define an update strategy tied to plugin or asset version changes.
- [ ] SW verification: needs browser validation for manifest installability, service worker registration, cache population, and offline behavior.

## Suggested Mini-Epic Order

1. Add real manifest icons, manifest `id`, and shortcode registration coverage.
2. Add a user-facing offline shell before building any write queues.
3. Design one offline queue shape for resident, provider, and resident-review submissions.
4. Add push subscriptions and VAPID settings only after the queue and lifecycle triggers are clear.
5. Add browser-based PWA checks for installability, cache behavior, offline fallback, and queued submission replay.
