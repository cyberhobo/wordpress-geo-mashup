#!/usr/bin/env bash
docker compose -f "docker-compose.pnpm.yml" run --build --rm pnpm "$@"
