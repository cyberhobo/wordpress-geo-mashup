---
name: release
description: Cut a Geo Mashup release following release-checklist.txt — release branch, readme.txt updates, version bump, CI check, merge to master, tag (deploys to wordpress.org), merge back to develop.
argument-hint: <version> [readme notes: changelog text, upgrade notice, security credits, ...]
disable-model-invocation: true
---

# Release Geo Mashup $ARGUMENTS

Follow `release-checklist.txt` (the source of truth — re-read it first in case it changed). The steps below
adapt it to this sandbox. If any step fails, stop, fix it (or report to the user), and repeat prior steps as needed.

## Inputs

The first word of the arguments is the new version (e.g. `1.13.25`). The rest is the user's description of
readme.txt updates. If the version is missing, or no changelog content was given, ask before starting.
Do not invent changelog text beyond what the user described; tidy wording only.

## Sandbox notes

- `origin` is an SSH URL the sandbox can't reach. Push with the HTTPS URL instead, without changing the remote:
  `REPO=https://github.com/cyberhobo/wordpress-geo-mashup.git` then `git push $REPO <ref>`.
  After pushing, run `git fetch $REPO <branch>:refs/remotes/origin/<branch>` if tracking info matters.
- `gh` works through the proxy even though `gh auth status` says not logged in.
- Use `pnpm` (it manages Node via `devEngines`). Use `bash -l -c` if a tool isn't on PATH.

## Preflight

1. `git status` must be clean and on `develop`. `git pull $REPO develop` (fast-forward only).
2. Confirm the version is greater than the latest tag (`git tag --sort=-v:refname | head -3`) and that no
   `release/<version>` branch or `<version>` tag exists locally or remotely (`git ls-remote $REPO`).
3. Check that develop's latest CI runs pass: `gh run list --repo cyberhobo/wordpress-geo-mashup --branch develop --limit 5`.
4. Show the user `git log --oneline <last tag>..develop` so they can sanity-check what is shipping.

## Steps

1. **Branch:** `git checkout -b release/<version> develop`.
2. **readme.txt:**
   - `Tested up to:` — set to the current WordPress release. Get it from
     `curl -s https://api.wordpress.org/core/version-check/1.7/` (first offer's `current`); if unreachable, ask.
   - `== Change Log ==` — add a new entry at the top, format `= <version> <Mon D YYYY> =` (today's date,
     e.g. `Nov 19 2026`), a blank line, the changelog text, a blank line.
   - `== Upgrade Notice ==` — only if the user asked (e.g. security releases use
     `This version includes a security update.  Upgrade immediately.` — note the two spaces). Newest at top.
   - "Thanks for security reports to:" list — add any credited researchers at the top of the list in the
     existing `* [Name](url)` format.
   - Apply any other readme changes the user described. Do NOT touch `Stable tag` — step 4 automates it.
3. **Language template:** run `pnpm run makepot`. If `git diff lang/` shows only header/date changes
   (`POT-Creation-Date` etc.), `git checkout lang/` to discard. If strings changed, keep it.
4. **Version:** `pnpm pkg set version=<version> && pnpm run version`. Verify the diff updates `package.json`,
   both the `Version:` header and `GEO_MASHUP_VERSION` constant in `geo-mashup.php`, and `Stable tag` in readme.txt.
   Then commit everything for the release in one commit: `Updates for <version>`.
5. **Push & CI:** `git push $REPO release/<version>`. Watch the runs for that branch
   (`gh run list --repo cyberhobo/wordpress-geo-mashup --branch release/<version>`, then `gh run watch <id> --exit-status`).
   All must pass before continuing. If they fail, stop and report.
6. **Confirm with the user** before going further — show the final readme diff and the CI result. Steps 7–9
   publish the release: the tag push deploys to wordpress.org and cannot be cleanly undone.
7. **Merge to master:** `git checkout master && git pull --ff-only $REPO master &&
   git merge --no-ff release/<version> -m "Merge branch 'release/<version>'" && git push $REPO master`.
8. **Tag:** `git tag <version> && git push $REPO <version>` (push only that tag, not `--tags`).
   Then watch the "Deploy to WordPress.org" workflow run for the tag and report its result.
9. **Back to develop:** `git checkout develop && git merge master && git push $REPO develop`.

## Finish

Report: the release commit, merge commits, tag, CI and deploy run links, and anything skipped or left for the user.
