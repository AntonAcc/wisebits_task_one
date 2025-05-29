# Test Assignment - Credit App

## Assignment specifications:

https://github.com/AntonAcc/credit-app/blob/master/specifications.md

## Testing:

1. If not already done, [install Docker Compose](https://docs.docker.com/compose/install/) (v2.10+)
2. Run `docker compose build --no-cache` to build fresh images
3. Run `docker compose up --pull --wait -d` to start the project 
4. Run `docker compose exec -T php composer install` to install dependencies 
5. Run `docker compose exec -T php bin/console doctrine:migrations:migrate` to init the DB

### Phpunit

```
docker compose exec -T php bin/console doctrine:database:create --env=test
docker compose exec -T php bin/console doctrine:migrations:migrate --env=test
docker compose exec -T php bin/phpunit 
```

### API

Open in your web browser http://localhost:8000/api
