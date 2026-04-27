# Drupal Rick and Morty Project

This is an educational Drupal project featuring a custom module `my_helper`
that integrates with the Rick and Morty API. It imports characters as
`api_item` nodes and automatically manages user accounts and roles based on
character species.

## 🚀 Getting Started

### Prerequisites
- [DDEV](https://ddev.readthedocs.io/en/stable/users/install/ddev-installation/)
  installed on your machine.
- Docker or OrbStack/Colima.

### Setup and Running
1. **Start the environment:**
   ```bash
   ddev start
   ```
2. **Install dependencies:**
   ```bash
   ddev composer install
   ```
3. **Open the project in your browser:**
   ```bash
   ddev launch
   ```
   Or visit: [https://drupal.ddev.site](https://drupal.ddev.site)

## 🔗 Custom Routes

The `my_helper` module defines the following routes:

- **Hello Page:** `/hello/{name}` — A simple greeting page (Default: Guest).
- **API Settings:** `/admin/config/services/my_helper` — Configuration for the
  Rick and Morty API (Status filters, etc.).
- **Batch Management:** `/admin/config/services/rick-and-morty-batch` — Form
  to trigger character import/delete batch processes.
- **Time Log Settings:** `/admin/structure/my-helper-my-helper-module` —
  Configuration for the Time Log entity.
- **Time Log Form:** `/log-time` — A custom form for logging time.

## 🛠 Development Commands

The project uses GrumPHP for pre-commit checks and PHPStan for static analysis.

### Running Quality Tools
You can run tools directly via DDEV to ensure the environment is consistent.

- **Run all checks (GrumPHP):**
  ```bash
  ddev exec ./vendor/bin/grumphp run
  ```
- **Run PHPStan:**
  ```bash
  ddev exec composer stan
  # or
  ddev exec ./vendor/bin/phpstan analyse
  ```
- **Check Coding Standards (PHPCS):**
  ```bash
  ddev exec ./vendor/bin/phpcs
  ```
- **Fix Coding Standards (PHPCBF):**
  ```bash
  ddev exec ./vendor/bin/phpcbf
  ```

### Useful Drush Commands
- **Clear Cache:** `ddev drush cr`
- **Import Configuration:** `ddev drush cim`
- **Export Configuration:** `ddev drush cex`

## 📖 Features
- **API Integration:** Fetches character data from
  `https://rickandmortyapi.com/graphql`.
- **Event-Driven Architecture:** Uses `entity_events` to handle node
  operations.
- **Automated User Management:** Creates users and assigns species-based
  roles (`RM Species: ...`) upon character import.
- **GraphQL Support:** Custom schema to expose API items via GraphQL.
