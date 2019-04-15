# Makefile default variables.
DC_COMMAND=docker-compose
DC_FILES=-f docker-compose.yml
DC=${DC_COMMAND} ${DC_FILES} ${DC_FILES_DOCKER_SYNC} ${DC_FILES_ENV}
DC_RUN=${DC} run --rm

# Copy .env.make.local.default to .env.make.local if you want to use this.
-include .env.make.local