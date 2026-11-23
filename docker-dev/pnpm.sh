#!/usr/bin/env bash
HOST_UID="$(id -u)" HOST_GID="$(id -g)" docker compose -f "docker-compose.pnpm.yml" run --build --rm pnpm "$@"
