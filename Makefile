.PHONY: deps test

deps:
	composer update

test:
	./vendor/bin/phpunit tests
