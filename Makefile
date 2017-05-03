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
ldap_doc_files=LICENSE README.md
ldap_src_dirs=ajax appinfo css img js lib templates vendor
ldap_all_src=$(ldap_src_dirs) $(ldap_doc_files)
build_dir=$(CURDIR)/build
dist_dir=$(build_dir)/dist
COMPOSER_BIN=$(build_dir)/composer.phar

# internal aliases
composer_deps=vendor/
composer_dev_deps=lib/composer/phpunit
bower_deps=vendor/

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
all: $(composer_dev_deps)

.PHONY: clean
clean: clean-composer-deps clean-dist clean-build


#
# Basic required tools
#
$(COMPOSER_BIN):
	mkdir $(build_dir)
	cd $(build_dir) && curl -sS https://getcomposer.org/installer | php

#
# ownCloud ldap PHP dependencies
#
$(composer_deps): $(COMPOSER_BIN) composer.json composer.lock
	php $(COMPOSER_BIN) install --no-dev
	$(BOWER) install && touch $(bower_deps)

$(composer_dev_deps): $(COMPOSER_BIN) composer.json composer.lock
	php $(COMPOSER_BIN) install --dev
	$(BOWER) install && touch $(bower_deps)

.PHONY: clean-composer-deps
clean-composer-deps:
	rm -f $(COMPOSER_BIN)
	rm -Rf $(composer_deps)

.PHONY: update-composer
update-composer: $(COMPOSER_BIN)
	rm -f composer.lock
	php $(COMPOSER_BIN) install --prefer-dist

#
# dist
#

$(dist_dir)/user_ldap: $(composer_deps)  $(bower_deps)
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
