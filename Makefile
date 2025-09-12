.PHONY: deps test release

deps:
	composer update

test:
	./vendor/bin/phpunit tests

release:
	mkdir $(VERSION)
	cp composer.json $(VERSION)
	cp composer.lock $(VERSION)
	cp index.php $(VERSION)
	cp pirsch-wordpress.php $(VERSION)
	cp readme.txt $(VERSION)
	cp -r vendor $(VERSION)
