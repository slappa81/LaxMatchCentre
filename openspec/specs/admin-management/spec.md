# admin-management Specification

## Purpose

Provide the WordPress admin experience for configuring the plugin: managing
competitions, triggering scrapes, managing the cache, and configuring team
display options including team logos. This capability is owned by `LMC_Admin`
and its registered AJAX handlers.

## Requirements

### Requirement: Settings Page

The system SHALL register a settings page under the WordPress admin and persist
configuration in the `lmc_settings` option via the Settings API.

#### Scenario: Settings page available

- **WHEN** an administrator opens the Match Centre settings page
- **THEN** the system renders the configuration form with current settings (cache duration, competitions, current competition)

#### Scenario: Settings persisted

- **WHEN** an administrator saves the settings form
- **THEN** the system sanitizes and stores the values in the `lmc_settings` option

### Requirement: Competition Management

The system SHALL allow administrators to add, edit, and remove competitions and
designate a current/default competition.

#### Scenario: Competition added

- **WHEN** an administrator adds a competition with an id, name, current round, and max rounds
- **THEN** the competition is stored in `lmc_settings.competitions`

#### Scenario: Current competition selected

- **WHEN** an administrator marks a competition as current
- **THEN** the system records it as the default competition used when none is specified

### Requirement: On-Demand Scraping

The system SHALL let administrators scrape a competition's data on demand from the
settings page.

#### Scenario: Scrape triggered

- **WHEN** an authorized administrator invokes the `lmc_scrape_competition` AJAX action with a valid nonce
- **THEN** the system scrapes the competition, writes its JSON data files, and returns a success/failure result

#### Scenario: Unauthorized scrape rejected

- **WHEN** the scrape request lacks a valid nonce or sufficient capability
- **THEN** the system rejects the request

### Requirement: Cache Management

The system SHALL let administrators clear cached competition data from the admin.

#### Scenario: Cache cleared from admin

- **WHEN** an authorized administrator invokes the `lmc_clear_cache` AJAX action with a valid nonce
- **THEN** the system clears the relevant transients and reports success

### Requirement: Team Configuration

The system SHALL expose the teams within a competition so administrators can
configure team-specific display.

#### Scenario: Teams listed

- **WHEN** an authorized administrator invokes the `lmc_get_teams` AJAX action for a competition with a valid nonce
- **THEN** the system returns the list of teams for that competition

### Requirement: Team Logo Management

The system SHALL allow administrators to upload, delete, and clear cached team
logos used in front-end display.

#### Scenario: Team logo uploaded

- **WHEN** an authorized administrator uploads a logo via the `lmc_upload_team_logo` AJAX action with a valid nonce
- **THEN** the system stores the logo and associates it with the team

#### Scenario: Team logo deleted

- **WHEN** an authorized administrator invokes the `lmc_delete_team_logo` AJAX action with a valid nonce
- **THEN** the system removes the stored logo association

#### Scenario: Cached logos cleared

- **WHEN** an authorized administrator invokes the `lmc_clear_cached_logos` AJAX action with a valid nonce
- **THEN** the system clears cached logo data
