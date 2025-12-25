#!/usr/bin/env bash
docker compose -f "docker-compose.yarn.yml" run --rm yarn "$@"
