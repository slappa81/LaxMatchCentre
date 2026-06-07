# competition-discovery Specification

## Purpose

Discover the seasons and competitions available on GameDay for a given
association so administrators can find and configure competitions without
manually constructing competition ids. This capability covers `LMC_Scraper`'s
season/competition listing and the admin/CLI surfaces that expose it.

## Requirements

### Requirement: Season Listing

The system SHALL list the available seasons for a GameDay association id.

#### Scenario: Seasons listed for an association

- **WHEN** `list_seasons($association_id)` is called with a valid association id
- **THEN** the system fetches and parses the available seasons into a list of season identifiers and names

#### Scenario: Association has no seasons

- **WHEN** no seasons can be parsed for the association
- **THEN** the system returns an empty list rather than failing

### Requirement: Competition Listing

The system SHALL list the available competitions for an association and season.

#### Scenario: Competitions listed

- **WHEN** the competition listing is requested for an association/season
- **THEN** the system fetches and parses the competitions into a list of competition ids and names

### Requirement: Admin Discovery Surface

The system SHALL expose season and competition discovery through authenticated
admin AJAX endpoints used by the settings page.

#### Scenario: Admin lists seasons

- **WHEN** an authorized admin requests seasons via the `lmc_list_seasons` AJAX action with a valid nonce
- **THEN** the system returns the discovered seasons as JSON

#### Scenario: Admin lists available competitions

- **WHEN** an authorized admin requests competitions via the `lmc_list_available_competitions` AJAX action with a valid nonce
- **THEN** the system returns the discovered competitions as JSON

#### Scenario: Unauthorized discovery request rejected

- **WHEN** a discovery AJAX request is made without a valid nonce or sufficient capability
- **THEN** the system rejects the request and does not return data
