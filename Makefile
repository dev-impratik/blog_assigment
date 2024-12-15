setup:
	@make build
	@make up 
	@make composer-update
build:
	docker-compose build --no-cache --force-rm
stop:
	docker-compose stop
up:
	docker-compose up -d
composer-update:
	docker exec laravel-docker bash -c "composer update"
publish:
	php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"
	php artisan vendor:publish --provider="L5Swagger\L5SwaggerServiceProvider"
data:
	docker exec intuji-docker-test bash -c "php artisan migrate"
	docker exec intuji-docker-test bash -c "php artisan db:seed"
	docker exec intuji-docker-test bash -c "php artisan jwt:secret"
	docker exec intuji-docker-test bash -c "php artisan l5-swagger:generate"
	docker exec intuji-docker-test bash -c "php artisan key:generate"
	docker exec intuji-docker-test bash -c "php artisan config:cache"
	docker exec intuji-docker-test bash -c "php artisan route:cache"
	docker exec intuji-docker-test bash -c "php artisan view:cache"