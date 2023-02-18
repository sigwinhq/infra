.SILENT:
RUNNER =
include vendor/sigwin/infra/resources/Pimcore/application.mk

vendor/sigwin/infra/resources/Pimcore/application.mk:
	mv composer.json composer.json~ && rm -f composer.lock
	docker run --rm --user '$(shell id -u):$(shell id -g)' --volume '$(shell pwd):/app' --workdir /app composer:2 require sigwin/infra
	mv composer.json~ composer.json && rm -f composer.lock
