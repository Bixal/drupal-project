include .env

.PHONY: up down stop prune ps shell dbdump dbrestore uli cim cex

default: up

up:
	@echo "Starting up containers for for $(PROJECT_NAME)..."
	docker-compose pull
	docker-compose up -d --remove-orphans

down:
	@echo "Removing containers."
	docker-compose down

stop:
	@echo "Stopping containers for $(PROJECT_NAME)..."
	@docker-compose stop

prune:
	@echo "Removing containers for $(PROJECT_NAME)..."
	@docker-compose down -v

ps:
	@docker ps --filter name="$(PROJECT_NAME)*"

shell:
	docker exec -ti $(shell docker ps --filter name='$(PROJECT_NAME)_php' --format "{{ .ID }}") sh

dbdump:
	@echo "Creating Database Dump for $(PROJECT_NAME)..."
	docker-compose run --rm php drupal database:dump --file=../db/restore.sql --gz

dbrestore:
	@echo "Restoring database..."
	docker-compose run --rm php drupal database:restore --file='/var/www/html/db/restore.sql.gz'

uli:
	@echo "Getting admin login"
	docker-compose run --rm php drush user:login --uri="$(PROJECT_BASE_URL)":8000

cim:
	@echo "Importing Configuration"
	docker-compose run --rm php drupal csim -y
	@echo "Importing Configuration Splits"
	docker-compose run --rm php drupal csim -y

cex:
	@echo "Exporting Configuration"
	docker-compose run --rm php drupal csex -y

gm:
	@echo "Displaying Generate Module UI"
	docker-compose run --rm php drupal generate:module

install-source:
	@echo "Installing dependencies"
	docker-compose run --rm php composer install --prefer-source

install:
	@echo "Installing dependencies"
	docker-compose run --rm php composer install

cr:
	@echo "Clearing Drupal Caches"
	docker-compose run --rm php drupal cache:rebuild all

logs:
	@echo "Displaying past containers logs"
	docker-compose logs

logsf:
	@echo "Follow containers logs output"
	docker-compose logs -f

dbclient:
	@echo "Opening DB client"
	docker-compose run --rm php drupal database:client

behat:
	@echo "Running behat tests"
	docker-compose run --rm php vendor/bin/behat

phpcs:
	@echo "Running coding standards on custom code"
	docker-compose run --rm php vendor/bin/phpcs --standard=vendor/drupal/coder/coder_sniffer/Drupal web/modules/custom --ignore=*.min.js --ignore=*.min.css

phpcbf:
	@echo "Beautifying custom code"
	docker-compose run --rm php vendor/bin/phpcbf --standard=vendor/drupal/coder/coder_sniffer/Drupal web/modules/custom --ignore=*.min.js --ignore=*.min.css

fresh:
	@echo "Ensure composer is up to date"
	docker-compose run --rm php composer install
	@echo "Installing a fresh Drupal 8 site"
	docker-compose run --rm php drupal si --force --no-interaction standard --account-pass="admin"
	@echo "Installing configuration from file"
	docker-compose run --rm php drupal config:import
	@echo "Installing configuration splits from file"
	docker-compose run --rm php drupal csim -y
	@echo "Importing content"
	docker-compose run --rm php drush content-sync:import -y --skiplist --entity-types=taxonomy_term,group.state,user,node.homepage
	@echo "Running initialization script"
	docker-compose run --rm php drupal sp_create:init
	@echo "Creating state plan year 2018 state plans year, state plan years, and state plan year sections"
	docker-compose run --rm php drush sp_create:content_and_answers 2018 create_plans_and_sections
	@echo "Creating state plan year 2018 state plan year answers"
	docker-compose run --rm php drush sp_create:content_and_answers 2018 modify_answers
	@echo "Rebuilding content access"
	docker-compose run --rm php drupal node:access:rebuild
	make cr
	make uli

# This does not include 'users' as an exported type because the 'changed'
# field will be updated on insert and we don't care about it and user 1
# and 0 should not be included in this list because they cause error on
# import.
export_content:
	@echo "Exporting plan year terms, states, and the homepage"
	docker-compose run --rm php drush content-sync:export --entity-types=taxonomy_term,group.state,node.homepage

standards:
	@echo "Running coding standards checks on host machine"
	phpcs --standard=DrupalPractice --colors --extensions=php,module,inc,install,test,profile,theme,css,info,txt,md --ignore=node_modules,bower_components,vendor web/modules/custom & phpcs --standard=Drupal --colors --extensions=php,module,inc,install,test,profile,theme,css,info,txt,md --ignore=node_modules,bower_components,vendor web/themes/custom & phpcs --standard=Drupal --colors --extensions=php,module,inc,install,test,profile,theme,css,info,txt,md --ignore=node_modules,bower_components,vendor web/modules/custom
