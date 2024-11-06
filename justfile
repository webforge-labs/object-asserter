set dotenv-load := false
set positional-arguments

project_root := justfile_directory()

export XDEBUG_TRIGGER := env_var_or_default('XDEBUG_TRIGGER', '')
export COLUMNS := '550'

default:
  @just --list

php := "docker compose exec -e COLUMNS -e XDEBUG_TRIGGER --user=www-data php"
composer := "docker compose exec --user=www-data php composer"

cli *args='':
    {{php}} bin/console "$@"

phpstan *args='':
    {{ php }} vendor/bin/phpstan "${@}"

watch-phpstan *args='':
    find src/ tests/ -name '*.php' | entr j phpstan analyse "${@}"

phpunit *args='':
    {{ php }} vendor/bin/phpunit "${@}"

composer *args='':
    {{ composer }} "${@}"

fix:
    {{ php }} vendor/bin/php-cs-fixer fix -v

prep:
    j fix
    j phpstan
    j phpunit

up:
    docker compose up -d

fix-docker-permissions:
    #docker compose exec --user=root php bash -c "mkdir -p /var/www/.composer /app/public/build && chown -R 33:33 /var/www /app/public/build /app/var/cache"
    #docker compose exec --user=root node bash -c "chown -R 33:33 /var/www"
    #docker compose exec --user=root node bash -c "mkdir -p /var/www/.cache && chown -R 33:33 /var/www/.cache /app/node_modules"
