# CLAUDE.md for Craft CMS Revalidate Plugin

## Commands
- **Install**: `composer install`
- **Run tests**: `php vendor/bin/codecept run`
- **Run single test**: `php vendor/bin/codecept run path/to/testfile`
- **Lint code**: `php vendor/bin/phpcs`
- **Fix code style**: `php vendor/bin/phpcbf`

## Code Style
- **Namespace**: `today\revalidate`
- **PSR-4 autoloading** with `today\revalidate\` namespace prefix
- **PHP version**: >= 8.0.2
- **Indent**: 2 spaces 
- **Method naming**: camelCase
- **Class naming**: PascalCase
- **Variable naming**: camelCase
- **Error handling**: Use try/catch with specific exception messages
- **Service pattern** for business logic in services/ directory
- **Model pattern** for data in models/ directory

## Project Structure
- **Plugin main class**: src/Revalidate.php
- **Controllers**: src/controllers/
- **Models**: src/models/
- **Services**: src/services/
- **Migrations**: src/migrations/