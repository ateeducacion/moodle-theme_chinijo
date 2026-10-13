# Development tasks for theme_chinijo.
#
# Requirements on the host: Docker (with Compose v2), GNU Make and a POSIX shell.
# Everything else (Moodle, PHP, Node, moodle-plugin-ci, browsers) runs in containers.
#
# Select the Moodle release with MOODLE_VERSION=4.5|5.0|5.1|5.2|5.3 (default 5.3),
# for example: make up MOODLE_VERSION=4.5

SHELL := /bin/sh
.DEFAULT_GOAL := help

-include .env

MOODLE_VERSION ?= 5.3
CHINIJO_HTTP_PORT ?= 8080
MOODLE_ADMIN_USER ?= admin

SUPPORTED_VERSIONS := 4.5 5.0 5.1 5.2 5.3

# erseco/alpine-moodle image per Moodle release (immutable tags checked on Docker Hub on 2026-10-08).
image_tag_4.5 := v4.5.15
image_tag_5.0 := v5.0.11
image_tag_5.1 := v5.1.8
image_tag_5.2 := v5.2.4
image_tag_5.3 := v5.3.0

# Moodle Git branch and the PHP version used by the test runner for each release.
branch_4.5 := MOODLE_405_STABLE
branch_5.0 := MOODLE_500_STABLE
branch_5.1 := MOODLE_501_STABLE
branch_5.2 := MOODLE_502_STABLE
branch_5.3 := MOODLE_503_STABLE
php_4.5 := 8.3
php_5.0 := 8.3
php_5.1 := 8.3
php_5.2 := 8.3
php_5.3 := 8.4

# Moodle 5.1 moved the web root to public/, so the theme is installed in public/theme/.
theme_path_4.5 := /var/www/html/theme/chinijo
theme_path_5.0 := /var/www/html/theme/chinijo
theme_path_5.1 := /var/www/html/public/theme/chinijo
theme_path_5.2 := /var/www/html/public/theme/chinijo
theme_path_5.3 := /var/www/html/public/theme/chinijo

MOODLE_IMAGE_TAG := $(image_tag_$(MOODLE_VERSION))
MOODLE_BRANCH := $(branch_$(MOODLE_VERSION))
CI_PHP_VERSION ?= $(php_$(MOODLE_VERSION))
CHINIJO_THEME_PATH := $(theme_path_$(MOODLE_VERSION))
PROJECT := chinijo-$(subst .,,$(MOODLE_VERSION))
SITE_URL := http://localhost:$(CHINIJO_HTTP_PORT)

ifeq ($(MOODLE_IMAGE_TAG),)
$(error Unsupported MOODLE_VERSION '$(MOODLE_VERSION)'. Use one of: $(SUPPORTED_VERSIONS))
endif

export MOODLE_IMAGE_TAG CHINIJO_THEME_PATH CHINIJO_HTTP_PORT

COMPOSE := docker compose -p $(PROJECT) -f docker-compose.yml
CI := MOODLE_BRANCH=$(MOODLE_BRANCH) CI_PHP_VERSION=$(CI_PHP_VERSION) \
	docker compose -p chinijo-ci-$(subst .,,$(MOODLE_VERSION)) -f dev/ci/docker-compose.yml
CI_RUN := $(CI) run --rm ci

.PHONY: help up down logs shell install seed lint fix test test-unit test-behat test-a11y coverage \
	test-matrix validate-blueprint package screenshot reset ci-shell ci-down versions

help: ## Show this help
	@echo "Chinijo development tasks (MOODLE_VERSION=$(MOODLE_VERSION), image erseco/alpine-moodle:$(MOODLE_IMAGE_TAG))"
	@echo
	@awk 'BEGIN {FS = ":.*## "} /^[a-zA-Z0-9_-]+:.*## / {printf "  \033[1m%-20s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)
	@echo
	@echo "Variables: MOODLE_VERSION=$(SUPPORTED_VERSIONS)  CHINIJO_HTTP_PORT=$(CHINIJO_HTTP_PORT)"

versions: ## Print the Moodle, image, PHP and path mapping used for MOODLE_VERSION
	@echo "MOODLE_VERSION=$(MOODLE_VERSION) image=erseco/alpine-moodle:$(MOODLE_IMAGE_TAG) branch=$(MOODLE_BRANCH)"
	@echo "ci_php=$(CI_PHP_VERSION) theme_path=$(CHINIJO_THEME_PATH) project=$(PROJECT) url=$(SITE_URL)"

up: ## Start Moodle + PostgreSQL, install or upgrade, activate Chinijo and print the URL
	$(COMPOSE) up -d
	@sh dev/wait-for-moodle.sh "$(SITE_URL)" "$(PROJECT)"
	@$(MAKE) --no-print-directory install
	@echo "Chinijo is ready on Moodle $(MOODLE_VERSION): $(SITE_URL)  (admin: $(MOODLE_ADMIN_USER))"

down: ## Stop the stack and keep its data
	$(COMPOSE) down

logs: ## Follow the Moodle and database logs
	$(COMPOSE) logs -f --tail=200

shell: ## Open a shell in the Moodle container
	$(COMPOSE) exec moodle sh

install: ## Install or upgrade the mounted theme, make it the site theme and purge caches
	$(COMPOSE) exec -T moodle sh "$(CHINIJO_THEME_PATH)/dev/install.sh"

seed: ## Create the synthetic demo users, course, activities and pictograms (idempotent)
	$(COMPOSE) exec -T moodle php "$(CHINIJO_THEME_PATH)/dev/seed.php"

lint: ## Run moodle-plugin-ci static checks, language parity and blueprint validation
	$(CI_RUN) lint
	@$(MAKE) --no-print-directory validate-blueprint

fix: ## Apply safe automatic fixes (Moodle PHPCBF, amd/build rebuild) to the working tree
	$(CI_RUN) fix
	@if [ -s build/fix.patch ]; then git apply --whitespace=nowarn build/fix.patch && echo "Applied build/fix.patch."; else echo "Nothing to fix."; fi

test: ## Run PHPUnit and the Behat suite (including the accessibility scenarios)
	$(CI_RUN) test

test-unit: ## Run the PHPUnit tests only
	$(CI_RUN) phpunit

test-behat: ## Run the Behat scenarios in Chromium
	$(CI_RUN) behat

test-a11y: ## Run the @accessibility Behat scenarios (axe-core) and keep their reports
	$(CI_RUN) a11y

coverage: ## Run PHPUnit with PCOV and write coverage reports to build/coverage
	$(CI_RUN) coverage

test-matrix: ## Run PHPUnit on every supported Moodle release, one after the other
	@set -e; for v in $(SUPPORTED_VERSIONS); do \
		echo "=== Moodle $$v ==="; \
		$(MAKE) --no-print-directory test-unit MOODLE_VERSION=$$v; \
	done

ci-shell: ## Open a shell in the test runner
	$(CI_RUN) shell

ci-down: ## Stop the test runner services (keeps its caches)
	$(CI) down

security: ## Run Semgrep (PHP/JS/OWASP/CWE/secrets rulesets) and gitleaks; reports in build/security
	@sh dev/security-scan.sh

validate-blueprint: ## Validate blueprint.json against the Moodle Playground schema and the runner's step list
	@sh dev/validate-blueprint.sh

package: ## Build the installable ZIP in build/ and check its contents
	@sh dev/package.sh

screenshot: ## Capture docs/screenshots/chinijo-course.png from the running site (run make seed first)
	@sh dev/screenshot.sh "$(SITE_URL)"

reset: ## Delete the stack and its volumes for MOODLE_VERSION (asks for CONFIRM=yes)
	@if [ "$(CONFIRM)" != "yes" ]; then \
		echo "This deletes the database and moodledata volumes of $(PROJECT)."; \
		echo "Run again with CONFIRM=yes to proceed: make reset MOODLE_VERSION=$(MOODLE_VERSION) CONFIRM=yes"; \
		exit 1; \
	fi
	$(COMPOSE) down -v
