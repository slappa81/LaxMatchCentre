# data-caching Specification

## Purpose

Provide read access to scraped competition data with timezone-aware formatting
and a caching layer, decoupling the front-end (widgets and blocks) from the raw
JSON files on disk. This capability is owned by `LMC_Data`.

## Requirements

### Requirement: Cached Data Reads

The system SHALL read competition data (ladder, fixtures, upcoming, results) from
JSON files and serve subsequent reads from the WordPress transient cache.

#### Scenario: First read populates cache

- **WHEN** ladder, upcoming, results, or fixtures data is requested and not cached
- **THEN** the system reads the corresponding `<type>-<comp_id>.json` file
- **AND** stores the parsed data in a transient keyed by type and competition id

#### Scenario: Subsequent read served from cache

- **WHEN** the same data is requested again before the cache expires
- **THEN** the system returns the cached data without reading the file

#### Scenario: Missing or invalid data file

- **WHEN** the requested JSON file does not exist or fails to decode
- **THEN** the system logs the error and returns `false`

### Requirement: Default Competition Resolution

The system SHALL resolve the current/default competition when no competition id
is supplied to a data read.

#### Scenario: Current competition used

- **WHEN** a data read is requested without a competition id
- **THEN** the system uses the configured current competition id

#### Scenario: No competition configured

- **WHEN** no competition id is supplied and no current competition is configured
- **THEN** the system returns `false`

### Requirement: Result Limiting

The system SHALL optionally limit the number of upcoming games or results returned.

#### Scenario: Limit applied

- **WHEN** a limit is supplied to `get_upcoming_games` or `get_results`
- **THEN** the system returns at most that many items

### Requirement: Timezone-Aware Formatting

The system SHALL format fixture dates and times from the source timezone into the
configured site timezone.

#### Scenario: Datetime converted to site timezone

- **WHEN** `format_datetime($date, $time)` is called with a parseable value
- **THEN** the system interprets it as Australia/Melbourne time and returns it formatted in the site timezone

#### Scenario: Unparseable datetime preserved

- **WHEN** the supplied date/time cannot be parsed
- **THEN** the system returns the original string unchanged

### Requirement: Cache Invalidation

The system SHALL allow cached data to be cleared per competition or globally.

#### Scenario: Clear single competition cache

- **WHEN** `clear_cache($comp_id)` is called
- **THEN** the ladder, upcoming, results, and fixtures transients for that competition are deleted

#### Scenario: Clear all cached data

- **WHEN** `clear_all_cache()` is called
- **THEN** all transients with the `lmc_` prefix are removed

### Requirement: Data File Status

The system SHALL report the existence, size, and last-modified time of each data
file for a competition.

#### Scenario: Status reported

- **WHEN** `check_data_files($comp_id)` is called
- **THEN** the system returns the existence, modified timestamp, and size for the ladder, fixtures, upcoming, and results files
