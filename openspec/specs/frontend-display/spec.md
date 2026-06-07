# frontend-display Specification

## Purpose

Render competition data to site visitors through both classic WordPress widgets
and Gutenberg blocks. This capability is owned by `LMC_Blocks` and the
`LMC_Ladder_Widget`, `LMC_Upcoming_Widget`, and `LMC_Results_Widget` classes,
together with their front-end assets.

## Requirements

### Requirement: Classic Widgets

The system SHALL provide sidebar widgets for the ladder, upcoming games, and
recent results.

#### Scenario: Ladder widget renders standings

- **WHEN** the Competition Ladder widget is placed and a competition has ladder data
- **THEN** the system renders the standings table for the selected (or current) competition

#### Scenario: Upcoming widget renders games

- **WHEN** the Upcoming Games widget is placed
- **THEN** the system renders upcoming games for the selected competition, limited to the configured number

#### Scenario: Results widget renders results

- **WHEN** the Recent Results widget is placed
- **THEN** the system renders completed games for the selected competition, limited to the configured number

#### Scenario: No data available

- **WHEN** a widget's competition has no data
- **THEN** the system renders gracefully without a fatal error

### Requirement: Gutenberg Blocks

The system SHALL register a set of blocks in a dedicated "Lacrosse Match Centre"
block category for displaying competition data in the block editor.

#### Scenario: Core display blocks registered

- **WHEN** the block editor loads
- **THEN** the system registers the `ladder`, `upcoming`, `results`, and `results-upcoming` blocks with server-side render callbacks

#### Scenario: Block attributes drive rendering

- **WHEN** a block specifies a title, competition id, or display mode attribute
- **THEN** the system renders the block using those attributes, falling back to the current competition when no id is set

### Requirement: Team And Club Blocks

The system SHALL provide team-scoped blocks and Williamstown club convenience
variants (including compact variants) for upcoming games and results.

#### Scenario: Team blocks registered

- **WHEN** the block editor loads
- **THEN** the system registers the `team-results` and `team-upcoming` blocks

#### Scenario: Williamstown variants registered

- **WHEN** the block editor loads
- **THEN** the system registers the `williamstown-results`, `williamstown-upcoming`, `williamstown-results-compact`, and `williamstown-upcoming-compact` blocks

### Requirement: Competition Selector Block

The system SHALL provide a competition selector block that lets visitors choose
which competition is displayed.

#### Scenario: Selector renders configured competitions

- **WHEN** the competition-selector block is placed
- **THEN** the system renders a selector populated with the configured competitions

#### Scenario: Selection updates displayed data

- **WHEN** a visitor changes the selected competition
- **THEN** the front-end requests the corresponding data via the `lmc_render_block` AJAX endpoint and updates the display

### Requirement: Output Escaping

The system SHALL escape all dynamic values rendered by widgets and blocks.

#### Scenario: Dynamic values escaped

- **WHEN** any widget or block renders team names, scores, dates, venues, or titles
- **THEN** the system escapes the output with the appropriate WordPress escaping function
