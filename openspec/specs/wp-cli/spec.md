# wp-cli Specification

## Purpose

Provide WP-CLI commands for discovering and inspecting competitions from the
command line, enabling automation and headless administration. This capability
is owned by `LMC_CLI` and is registered only when WP-CLI is available.

## Requirements

### Requirement: List Available Competitions

The system SHALL provide a command to list competitions available on GameDay for a
given association.

#### Scenario: Available competitions listed

- **WHEN** `wp lmc list-available-competitions <association_id>` is run
- **THEN** the system discovers and prints the competitions available for that association

### Requirement: List Configured Competitions

The system SHALL provide a command to list the competitions currently configured
in the plugin.

#### Scenario: Configured competitions listed

- **WHEN** `wp lmc list-competitions` is run
- **THEN** the system prints the competitions stored in plugin settings

### Requirement: Get Competition

The system SHALL provide a command to fetch and display data for a single
competition by id.

#### Scenario: Competition data retrieved

- **WHEN** `wp lmc get-competition <comp_id>` is run with a valid competition id
- **THEN** the system retrieves and prints that competition's data
