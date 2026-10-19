.PHONY: install qa cs csf phpstan tests tests-watch coverage-clover coverage-html

install:
	composer update

qa: phpstan cs

cs:
	vendor/bin/phpcs -n

csf:
	vendor/bin/phpcbf

phpstan:
	vendor/bin/phpstan analyse -c phpstan.neon --memory-limit=512M

tests:
	vendor/bin/tester -s -p php --colors 1 -C tests/cases -c tests/config/php.ini

tests-watch:
	vendor/bin/tester -s -p php --colors 1 -C tests/cases -c tests/config/php.ini -w tests -w src

coverage:
ifdef GITHUB_ACTION
	vendor/bin/tester -s -p phpdbg --colors 1 -C --coverage coverage.xml --coverage-src src tests/cases
else
	vendor/bin/tester -s -p php --colors 1 -C --coverage coverage.html --coverage-src src tests/cases
endif
