include .env
# This is only for Makefile environment variables. Specifying multiple .env files for docker-compose.yml is not supported yet.
# Includes directives inside .env are NOT processed when docker-compose is run.
include .env.make

.PHONY: up down stop prune ps shell dbdump dbrestore uli cim cex

default: up

up:
	$(UP_PREFIX)
	@echo "Starting up containers for for $(PROJECT_NAME)..."
	$(DC) pull
	$(DC) up -d --remove-orphans

down:
	${DOWN_PREFIX}
	@echo "Removing containers."
	$(DC) down

stop:
	${STOP_PREFIX}
	@echo "Stopping containers for $(PROJECT_NAME)..."
	$(DC) stop

prune:
	${PRUNE_PREFIX}
	@echo "Removing containers for $(PROJECT_NAME)..."
	$(DC) down -v

ps:
	@docker ps --filter name="$(PROJECT_NAME)*"

shell:
	docker exec -ti $(shell docker ps --filter name='$(PROJECT_NAME)_php' --format "{{ .ID }}") sh

dbdump:
	@echo "Creating Database Dump for $(PROJECT_NAME)..."
	${DC_RUN} php drupal database:dump --file=../db/restore.sql --gz

dbrestore:
	@echo "Restoring database..."
	${DC_RUN} php drupal database:restore --file='/var/www/html/db/restore.sql.gz'

uli:
	@echo "Getting admin login"
	${DC_RUN} php drush user:login --uri="$(PROJECT_BASE_URL)":8000

cim:
	@echo "Importing Configuration"
	${DC_RUN} php drupal csim -y
	@echo "Importing Configuration Splits"
	${DC_RUN} php drupal csim -y

cex:
	@echo "Exporting Configuration"
	${DC_RUN} php drupal csex -y

gm:
	@echo "Displaying Generate Module UI"
	${DC_RUN} php drupal generate:module

install-source:
	@echo "Installing dependencies"
	${DC_RUN} php composer install --prefer-source

install:
	@echo "Installing dependencies"
	${DC_RUN} php composer install

cr:
	@echo "Clearing Drupal Caches"
	${DC_RUN} php drupal cache:rebuild all

logs:
	@echo "Displaying past containers logs"
	docker-compose logs

logsf:
	@echo "Follow containers logs output"
	docker-compose logs -f

dbclient:
	@echo "Opening DB client"
	${DC_RUN} php drupal database:client

behat:
	@echo "Running behat tests"
	${DC_RUN} php vendor/bin/behat

phpcs:
	@echo "Running coding standards on custom code"
	${DC_RUN} php vendor/bin/phpcs --standard=vendor/drupal/coder/coder_sniffer/Drupal web/modules/custom --ignore=*.min.js --ignore=*.min.css

phpcbf:
	@echo "Beautifying custom code"
	${DC_RUN} php vendor/bin/phpcbf --standard=vendor/drupal/coder/coder_sniffer/Drupal web/modules/custom --ignore=*.min.js --ignore=*.min.css

fresh:
	@echo "Ensure composer is up to date"
	${DC_RUN} php composer install
	@echo "Installing a fresh Drupal 8 site"
	${DC_RUN} php drupal si --force --no-interaction standard --account-pass="admin"
	make import_latest

update:
	@echo "Ensure composer is up to date"
	${DC_RUN} php composer install
	@echo "Updating database"
	${DC_RUN} php drupal update:execute all
	make import_latest

import_latest:
	@echo "Installing configuration from file"
	${DC_RUN} php drupal config:import
	@echo "Installing configuration splits from file"
	${DC_RUN} php drupal csim -y
	@echo "Importing content"
	${DC_RUN} php drush content-sync:import -y --skiplist --entity-types=taxonomy_term,group.state,user,node.homepage,menu_link_content
	@echo "Running initialization script"
	${DC_RUN} php drupal sp_create:init
	@echo "Creating state plan year 2018 state plans year, state plan years, and state plan year sections"
	${DC_RUN} php drush sp_create:content_and_answers 2018 create_plans_and_sections
	@echo "Creating state plan year 2018 state plan year answers"
	${DC_RUN} php drush sp_create:content_and_answers 2018 modify_answers
	@echo "Rebuilding content access"
	${DC_RUN} php drupal node:access:rebuild
	make cr
	make uli

# This does not include 'users' as an exported type because the 'changed'
# field will be updated on insert and we don't care about it and user 1
# and 0 should not be included in this list because they cause error on
# import.
export_content:
	@echo "Exporting plan year terms, states, and the homepage"
	${DC_RUN} php drush content-sync:export --entity-types=taxonomy_term,group.state,node.homepage

standards:
	@echo "Running coding standards checks on host machine"
	phpcs --standard=DrupalPractice --colors --extensions=php,module,inc,install,test,profile,theme,css,info,txt,md --ignore=*node_modules/*,*bower_components/*,*vendor/*,*.min.js,*.min.css web/modules/custom & phpcs --standard=Drupal --colors --extensions=php,module,inc,install,test,profile,theme,css,info,txt,md --ignore=*node_modules/*,*bower_components/*,*vendor/*,*.min.js,*.min.css web/themes/custom & phpcs --standard=Drupal --colors --extensions=php,module,inc,install,test,profile,theme,css,info,txt,md --ignore=*node_modules/*,*bower_components/*,*vendor/*,*.min.js,*.min.css web/modules/custom

docker-sync-stop:
	@echo "Stopping synced directories"
	@echo "See http://docker-sync.io/ for more information (Easy install: gem install docker-sync). Make any changes to docker-sync.yml? Run docker-sync clean."
	docker-sync stop

docker-sync-start:
	@echo "Starting docker-sync directories, this can take a while..."
	docker-sync start

prune-sync:
	make docker-sync-stop
	make prune

dc-echo:
	@echo "${DC}"

dc-run-echo:
	@echo "${DC_RUN}"
