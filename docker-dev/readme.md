# Docker development environment

Develop using Docker Compose, no need to install MySQL, PHP, or Node.

## Install

`touch .pnpm.env` to create an empty file for development environment variables.

An install service attempts to install development dependencies when
the development server is brought up.


Alternatively `./pnpm.sh run install:dev` will do the job.

## Development Server

To run MariaDB and WordPress with a live copy of Geo Mashup:

`docker compose up --build`

This also runs a temporary install service to install node and PHP
development dependencies.

Control-C stops running services, or `docker compose stop` if that goes
awry.

## Testing

MariaDB must be running.

The test support framework must be installed once before running tests:

`./pnpm.sh run install:docker:test-support`

Tests can then be run as needed:

`./pnpm.sh test`

## Other tools

You can see all the package scripts with `./pnpm.sh run`. One of the
commands is `composer` which can be used to run composer commands.

## pnpm.sh

This is just a shorthand for a longer docker-compose command which
you can use if you prefer.

## End-to-end tests

Browser tests live in `e2e/` and use [Playwright](https://playwright.dev) against a
[wp-env](https://www.npmjs.com/package/@wordpress/env) site. They need [pnpm](https://pnpm.io/installation) and Docker. pnpm
downloads the Node.js version pinned in `package.json` (`devEngines.runtime`) and uses it to
run scripts, so no separate Node install or version manager is needed.

```sh
composer install                       # vendor/ must exist for the plugin to load
pnpm install
pnpm exec playwright install chromium  # once
pnpm run e2e:env:start                 # http://localhost:8888 (admin / password), seeds fixtures
pnpm run e2e                           # or: pnpm run e2e --ui
pnpm run e2e:env:stop
```

`pnpm run e2e:env:start` runs `wp geo-mashup-e2e seed` (from `e2e/fixtures/`), which recreates
the located test posts, `test_tax` terms, plugin options, and one global map page per map
provider. Re-run it any time with `pnpm exec wp-env run cli wp geo-mashup-e2e seed`.

Google Maps tests are skipped unless `GOOGLE_MAPS_API_KEY` is set when the site is seeded
and when the tests run.
