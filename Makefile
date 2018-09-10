SHELL := /bin/bash

#
# Define NPM and check if it is available on the system.
#
NPM := $(shell command -v npm 2> /dev/null)
ifndef NPM
    $(error npm is not available on your system, please install npm)
endif

NODE_PREFIX=$(shell pwd)

PHPUNIT="$(PWD)/lib/composer/phpunit/phpunit/phpunit"
BOWER=$(NODE_PREFIX)/node_modules/bower/bin/bower
JSDOC=$(NODE_PREFIX)/node_modules/.bin/jsdoc

app_name=$(notdir $(CURDIR))
ldap_doc_files=LICENSE README.md CHANGELOG.md
ldap_src_dirs=appinfo css img js lib l10n templates vendor
ldap_all_src=$(ldap_src_dirs) $(ldap_doc_files)
build_dir=$(CURDIR)/build
dist_dir=$(build_dir)/dist
COMPOSER_BIN=$(build_dir)/composer.phar
PHAN_BIN=$(build_dir)/phan.phar

# internal aliases
composer_deps=vendor/
composer_dev_deps=lib/composer/phpunit
nodejs_deps=node_modules
bower_deps=vendor/ui-multiselect

occ=$(CURDIR)/../../occ
private_key=$(HOME)/.owncloud/certificates/$(app_name).key
certificate=$(HOME)/.owncloud/certificates/$(app_name).crt
sign=php -f $(occ) integrity:sign-app --privateKey="$(private_key)" --certificate="$(certificate)"
sign_skip_msg="Skipping signing, either no key and certificate found in $(private_key) and $(certificate) or occ can not be found at $(occ)"
ifneq (,$(wildcard $(private_key)))
ifneq (,$(wildcard $(certificate)))
ifneq (,$(wildcard $(occ)))
	CAN_SIGN=true
endif
endif
endif

#
# Catch-all rules
#
.PHONY: all
all: $(composer_dev_deps) $(bower_deps)

.PHONY: clean
clean: clean-composer-deps clean-dist clean-build


#
# Basic required tools
#
$(COMPOSER_BIN):
	mkdir $(build_dir)
	cd $(build_dir) && curl -sS https://getcomposer.org/installer | php

$(PHAN_BIN): $(COMPOSER_BIN)
	cd $(build_dir) && curl -s -L https://github.com/phan/phan/releases/download/0.12.10/phan.phar -o phan.phar;
#
# ownCloud ldap PHP dependencies
#
$(composer_deps): $(COMPOSER_BIN) composer.json composer.lock
	php $(COMPOSER_BIN) install --no-dev

$(composer_dev_deps): $(COMPOSER_BIN) composer.json composer.lock
	php $(COMPOSER_BIN) install --dev

.PHONY: clean-composer-deps
clean-composer-deps:
	rm -f $(COMPOSER_BIN)
	rm -Rf $(composer_deps)

.PHONY: update-composer
update-composer: $(COMPOSER_BIN)
	rm -f composer.lock
	php $(COMPOSER_BIN) install --prefer-dist

#
## Node JS dependencies for tools
#
$(nodejs_deps): package.json
	$(NPM) install --prefix $(NODE_PREFIX) && touch $@

$(BOWER): $(nodejs_deps)
$(JSDOC): $(nodejs_deps)

$(bower_deps): $(BOWER)
	$(BOWER) install --allow-root && touch $@

#
# dist
#

$(dist_dir)/user_ldap: $(composer_deps) $(bower_deps)
	rm -Rf $@; mkdir -p $@
	cp -R $(ldap_all_src) $@
	find $@/vendor -type d -iname Test? -print | xargs rm -Rf
	find $@/vendor -name travis -print | xargs rm -Rf
	find $@/vendor -name doc -print | xargs rm -Rf
	find $@/vendor -iname \*.sh -delete
	find $@/vendor -iname \*.exe -delete

ifdef CAN_SIGN
	$(sign) --path="$(dist_dir)/user_ldap"
else
	@echo $(sign_skip_msg)
endif
	tar -czf $(dist_dir)/user_ldap.tar.gz -C $(dist_dir) $(app_name)

.PHONY: dist
dist: $(dist_dir)/user_ldap

.PHONY: clean-dist
clean-dist:
	rm -Rf $(dist_dir)

.PHONY: clean-build
clean-build:
	rm -Rf $(build_dir)

.PHONY: test-php-phan
test-php-phan: $(PHAN_BIN)
	php $(PHAN_BIN) --config-file .phan/config.php --require-config-exists -p

.PHONY: test-php-lint
test-php-lint: $(composer_dev_deps)
	$(composer_deps)/bin/parallel-lint --exclude vendor --exclude build .

.PHONY: test-php-style
test-php-style: $(composer_dev_deps)
	$(composer_deps)/bin/php-cs-fixer fix -v --diff --dry-run --allow-risky yes

.PHONY: test-php
test-php: $(composer_dev_deps)
	PHPUNIT=$() cd tests/unit && phpunit
