# Docker development environment

Develop using Docker Compose, no need to install MySQL, PHP, or Node.

## Install

An install service attempts to install development dependencies when
the development server is brought up.

Alternatively `./yarn.sh dev:install` will do the job.

## Development Server

To run MariaDB and WordPress with a live copy of Geo Mashup:

`docker-compose up`

This also runs a temporary install service to install node and PHP
development dependencies.

Control-C stops running services, or `docker-compose stop` if that goes
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
