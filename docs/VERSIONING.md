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

Use `npm run version:check` before publishing if you only want to verify that
`package.json`, `package-lock.json`, `upkeepify.php`, and `readme.txt` agree.

## Automated GitHub Releases

Use **Actions → Release → Run workflow** on the `main` branch and choose `patch`,
`minor`, or `major`.

The release workflow also runs automatically every Monday at 08:00 SAST. Scheduled
runs publish a `patch` release only when plugin runtime files have changed since
the latest exact release tag.

The workflow:

1. Installs PHP and Node dependencies.
2. Runs PHPUnit, PHP syntax checks, Composer audit, and the JS build.
3. Bumps the chosen semantic version.
4. Verifies all plugin version metadata is aligned.
5. Commits the version bump back to `main`.
6. Creates an immutable release tag such as `v1.3.1`.
7. Moves the floating major tag such as `v1` to the latest release in that major line.
8. Moves the floating `latest` tag to the newest release.
9. Builds the slim WordPress plugin zip.
10. Publishes a GitHub Release marked as latest with the zip attached.

Use exact tags like `v1.3.1` when you need a reproducible release. Use major tags
like `v1` when you want the newest compatible release in that major line.

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
