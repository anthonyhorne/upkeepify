# Upkeepify Licensing

## Intended licensing model

Upkeepify is moving to a dual-licensing model. The source code is publicly
available on GitHub for inspection, contribution, and non-commercial use. A
commercial licence is required for production deployments that manage more than
one complex under a single install, or that participate in the benchmark
network.

> **Note:** The plugin file headers currently carry a GPL v2 declaration from
> the initial release. The definitive licence terms will be updated in the next
> major version once legal review is complete. If you are unsure which terms
> apply to your use case, contact the maintainer before deploying.

---

## Free tier

**Single-complex deployments are free**, including:

- All core features (task submission, contractor invite flow, response lifecycle,
  photo uploads, GPS capture)
- PWA (manifest, service worker, offline support)
- Local variance scoring and contractor reliability views

**Conditions:**

- Non-commercial or community (HOA/body-corporate) use only
- One complex (one physical property or strata scheme) per install
- Attribution — "Powered by Upkeepify" link must remain visible in the footer
  of public-facing plugin pages, or an equivalent credit must be maintained

---

## Commercial licence (multi-complex or network)

A yearly licence fee applies if any of the following are true:

- The plugin manages more than one complex under a single WordPress install
- The opt-in benchmark network sync is enabled
- The plugin is deployed as part of a commercial service (property management
  software, SaaS, white-label product)

Licence fees and terms are set by the maintainer. Contact
[anthony@horne.id.au](mailto:anthony@horne.id.au) for pricing.

**What the commercial licence includes:**

- Multi-complex management under one install
- Benchmark network participation (contribution + read access)
- Contractor reputation portability across nodes
- Priority support and access to pre-release builds

---

## Intended licence (Business Source Licence model)

Once formal legal drafting is complete, Upkeepify will adopt a licence
structured as follows:

| Use | Terms |
|---|---|
| Non-commercial / single-complex | Free, conditions above |
| Commercial / multi-complex / network | Annual commercial licence required |
| Contributions to this repository | Contributor Licence Agreement (CLA) required |
| Forking for private, non-distributed modification | Permitted under free tier |
| Forking for redistribution or SaaS | Commercial licence required |

This is modelled on the **Business Source Licence (BUSL 1.1)** used by
HashiCorp, MariaDB, and others — source-available, not proprietary, but with
commercial use requiring a licence.

---

## WordPress GPL compatibility note

WordPress core is GPL v2. Plugins that hook into WordPress APIs are generally
considered derivative works and must be GPL-compatible. This is an active area
of legal interpretation.

The practical approach being taken here:

- The plugin PHP code (which hooks into WordPress) will remain GPL v2 or
  compatible.
- Commercial restrictions will be enforced via licence agreement and the
  benchmark network's server-side terms, not via code restrictions on the
  WordPress plugin itself.
- This is consistent with how other commercial WordPress plugins operate
  (e.g. WooCommerce extensions, Gravity Forms, ACF Pro).

Legal advice has been sought and will be reflected in the next version update.

---

## Contributing

Contributions are welcome. By submitting a pull request you agree that your
contribution will be licensed under the same terms as the project. A formal
Contributor Licence Agreement (CLA) will be introduced alongside the BUSL
update.

See [CONTRIBUTING.md](docs/contributing.md) for development guidelines.
