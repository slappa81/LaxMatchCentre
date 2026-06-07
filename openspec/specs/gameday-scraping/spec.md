# gameday-scraping Specification

## Purpose

Fetch lacrosse competition data from the public GameDay / SportsTG website and
transform it into the plugin's normalized JSON data files. This capability owns
all HTTP retrieval and HTML parsing performed by `LMC_Scraper`, including
ladder standings, round fixtures, and the derivation of upcoming games and
recent results, as well as the scheduled (cron) refresh of configured
competitions.

## Requirements

### Requirement: Ladder Retrieval

The system SHALL fetch and parse the competition ladder for a given competition
id and round from GameDay into a structured standings list.

#### Scenario: Ladder fetched successfully

- **WHEN** `get_ladder($comp_id, $round_num)` is called with a valid competition id
- **THEN** the system fetches the ladder page via the WordPress HTTP API
- **AND** parses the HTML table into an ordered array of team standings

#### Scenario: Ladder page unavailable

- **WHEN** the ladder page cannot be retrieved or contains no recognizable table
- **THEN** the system logs the error and returns `false` without raising a fatal error

### Requirement: Round Fixture Retrieval

The system SHALL fetch and parse fixtures for a specific round and pool, and SHALL
fetch all rounds for a competition into a combined fixtures list.

#### Scenario: Single round parsed

- **WHEN** `get_round_fixtures($comp_id, $round_num, $pool_num)` is called
- **THEN** the system returns the valid fixtures for that round with team names, scores, date, time, and venue when present

#### Scenario: All fixtures aggregated across rounds

- **WHEN** `fetch_all_fixtures($comp_id, $comp_name, $current_round, $max_rounds)` is called
- **THEN** the system iterates rounds (and pools) and returns the combined set of valid fixtures

#### Scenario: Invalid fixtures excluded

- **WHEN** a parsed row does not represent a valid fixture (e.g. missing both teams)
- **THEN** the system omits it from the results

### Requirement: Finals And Stage Labelling

The system SHALL annotate fixtures with stage and round labels so finals and pool
stages are distinguishable from regular season rounds.

#### Scenario: Finals round named

- **WHEN** a round corresponds to a finals stage
- **THEN** the system applies the appropriate finals label (using discovered names when available, otherwise a sensible default)

#### Scenario: Pool stage metadata added

- **WHEN** fixtures belong to a pool stage
- **THEN** each fixture is annotated with its stage label

### Requirement: Upcoming And Results Derivation

The system SHALL split a fixtures list into upcoming games and recent results
based on whether a fixture has been played.

#### Scenario: Upcoming games derived

- **WHEN** `get_upcoming_games($fixtures)` is called
- **THEN** the system returns fixtures that have not yet been played, ordered by date/time

#### Scenario: Recent results derived

- **WHEN** `get_recent_results($fixtures)` is called
- **THEN** the system returns fixtures that have been completed (scores present), most recent first

### Requirement: Scheduled Scraping

The system SHALL refresh all configured competitions automatically on a recurring
schedule via WordPress cron.

#### Scenario: Hourly cron refresh

- **WHEN** the `lmc_hourly_scrape` cron event fires
- **AND** at least one competition is configured
- **THEN** the system scrapes each configured competition and regenerates its JSON data files

#### Scenario: No competitions configured

- **WHEN** the cron event fires and no competitions are configured
- **THEN** the system logs that there is nothing to scrape and exits without error

#### Scenario: Cron lifecycle managed on activation and deactivation

- **WHEN** the plugin is activated
- **THEN** an hourly `lmc_hourly_scrape` event is scheduled if not already present
- **AND WHEN** the plugin is deactivated, the scheduled event is removed
