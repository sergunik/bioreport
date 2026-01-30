# Command Execution Contract

- The host system must NEVER be used to run project commands
- All project-related commands MUST be executed inside Docker containers
- Local execution of the following tools is strictly forbidden:
  - composer
  - php
  - artisan
  - phpunit
  - any framework or build-related CLI

## Command Execution Policy
- Any `composer` command MUST be executed as:
  `docker-compose exec app composer <command>`
- Any `php artisan` command MUST be executed as:
  `docker-compose exec app php artisan <command>`
- Any test execution MUST be executed inside the container

## Enforcement
- If a command is proposed without `docker-compose exec app`, it is considered invalid
- Always assume Docker is running and containers are available

## Correct Command Examples

✅ Correct:
- docker-compose exec app composer require dedoc/scramble
- docker-compose exec app php artisan test
- docker-compose exec app php artisan migrate

❌ Incorrect:
- composer require dedoc/scramble
- php artisan test
- phpunit
