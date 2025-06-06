.PHONY: install qa cs csf phpstan tests tests-watch coverage-clover coverage-html

install:
	composer update

qa: phpstan cs

cs:
	vendor/bin/codesniffer src tests

csf:
	vendor/bin/codefixer src tests

phpstan:
	vendor/bin/phpstan analyse -l max -c phpstan.neon src

tests:
	vendor/bin/tester -s -p php --colors 1 -C tests/cases -c tests/config/php.ini

tests-watch:
	vendor/bin/tester -s -p php --colors 1 -C tests/cases -c tests/config/php.ini -w tests -w src

coverage:
	vendor/bin/tester -s -p phpdbg --colors 1 -C --coverage ./coverage.xml --coverage-src ./src tests/cases

coverage-clover:
	vendor/bin/tester -s -p phpdbg --colors 1 -C --coverage ./coverage.xml --coverage-src ./src tests/cases

coverage-html:
	vendor/bin/tester -s -p phpdbg --colors 1 -C --coverage ./coverage.html --coverage-src ./src tests/cases
