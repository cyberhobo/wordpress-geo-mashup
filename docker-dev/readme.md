# Docker development environment

Develop using Docker Compose, no need to install MySQL, PHP, or Node.

## Install

`touch .yarn.env` to create an empty file for development environment variables.

An install service attempts to install development dependencies when
the development server is brought up.


Alternatively `./yarn.sh install:dev` will do the job.

## Development Server

To run MariaDB and WordPress with a live copy of Geo Mashup:

`docker compose up`

This also runs a temporary install service to install node and PHP
development dependencies.

Control-C stops running services, or `docker compose stop` if that goes
awry.

## Testing

MariaDB must be running.

The test support framework must be installed once before running tests:

`./yarn.sh install:docker:test-support`

Tests can then be run as needed:

`./yarn.sh test`

## Other tools

You can see all the yarn commands with `./yarn.sh run`. One of the
commands is `composer` which can be used to run composer commands.

## yarn.sh

This is just a shorthand for a longer docker-compose command which
you can use if you prefer.

## End-to-end tests

Browser tests live in `e2e/` and use [Playwright](https://playwright.dev) against a
[wp-env](https://www.npmjs.com/package/@wordpress/env) site. They need Node 24+ and Docker.

```sh
composer install                     # vendor/ must exist for the plugin to load
yarn install
npx playwright install chromium      # once
yarn e2e:env:start                   # http://localhost:8888 (admin / password), seeds fixtures
yarn e2e                             # or: yarn e2e --ui
yarn e2e:env:stop
```

`yarn e2e:env:start` runs `wp geo-mashup-e2e seed` (from `e2e/fixtures/`), which recreates
the located test posts, `test_tax` terms, plugin options, and one global map page per map
provider. Re-run it any time with `npx wp-env run cli wp geo-mashup-e2e seed`.

Google Maps tests are skipped unless `GOOGLE_MAPS_API_KEY` is set when the site is seeded
and when the tests run.
