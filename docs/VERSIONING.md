# Versioning

Upkeepify uses semantic versioning for plugin releases.

## Rules

- `major` for breaking changes.
  This includes incompatible schema changes, removed shortcodes/settings, or workflow changes that require admins or contractors to behave differently after upgrade.
- `minor` for new features.
  This includes new settings, new shortcodes, new lifecycle steps, new admin panels, and meaningful user-facing UX changes.
- `patch` for fixes and small improvements.
  This includes bug fixes, validation fixes, copy tweaks, styling cleanups, or non-breaking internal improvements that ship in the plugin.

## Source Of Truth

The plugin version is stored in:

- `upkeepify.php`
- `readme.txt`
- `package.json`
- `package-lock.json`

The bump script keeps these files aligned.

## Bumping A Release

Run one of the following commands from the repo root:

```bash
npm run version:patch
npm run version:minor
npm run version:major
npm run package:release
```

The `version:*` commands update plugin metadata files automatically. The `package:release`
command builds a slim WordPress-ready plugin archive from the runtime file list.

## Release Checklist

1. Choose the correct bump type: `patch`, `minor`, or `major`.
2. Run the matching `npm run version:*` command.
3. Update `docs/changelog.md`.
4. Update the changelog and upgrade notice blocks in `readme.txt` when the release is user-facing.
5. Update the short changelog summary in `README.md` when needed.
6. Build a slim release zip with `npm run package:release`.
7. Exclude repository-only content such as `.github/`, `.codex/`, local archives, tests, and other development-only files from distributed plugin archives.
8. Keep release packaging aligned with WordPress plugin directory expectations for eventual WordPress.org distribution.
9. Commit the version bump and release notes together.
10. Verify the final plugin zip before sharing it.
