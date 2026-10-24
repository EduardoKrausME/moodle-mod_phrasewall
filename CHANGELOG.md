# Changelog

This changelog documents only the two most recent published GitHub releases. Each entry is based on the changes between the release tag and the immediately preceding **published** release. In particular, a `V 1.1.5` commit falls within the `1.1.4...1.1.6` comparison, but no published GitHub release for `1.1.5` was found.

## 1.1.6 - 2026-10-15

### Changed

- Updated GitHub Actions CI database services from PostgreSQL 16 to PostgreSQL 17 and from MariaDB 10 to MariaDB 11.4, including MariaDB initialization and health-check settings.
- Added `pix/icon.png` and reorganized the SVG logo asset as `pix/monologo.svg`.
- Updated the root plugin release from `1.1.4` to `1.1.6` and the build number from `2026101200` to `2026101501`. The interval includes a `V 1.1.5` commit, but the two releases being documented are the published `1.1.4` and `1.1.6` releases.
- Removed a version requirement comment from `version.php` without changing the minimum Moodle build (`2024100700`).

No functional changes to the Phrase Wall activity were identified in this release comparison.

[Compare 1.1.4...1.1.6](https://github.com/EduardoKrausME/moodle-mod_phrasewall/compare/1.1.4...1.1.6)

## 1.1.4 - 2026-10-12

### Changed

- Updated the PHP 8.3 CI matrix entry from `MOODLE_502_STABLE` (Moodle 5.2) to `MOODLE_503_STABLE` (Moodle 5.3), while retaining Moodle 4.5 CI coverage.
- Hardened the existing GitHub release workflow against shell injection by supplying the commit message through an environment variable instead of interpolating it directly into shell commands.
- Stopped attaching an automatically generated plugin ZIP to GitHub releases in that workflow; the version tag and release creation steps remained in the workflow.
- Updated the root plugin release from `1.1.3` to `1.1.4` and its build number from `2026100501` to `2026101200`.

No functional changes to the Phrase Wall activity were identified in this release comparison.

[Compare 1.1.3...1.1.4](https://github.com/EduardoKrausME/moodle-mod_phrasewall/compare/1.1.3...1.1.4)
