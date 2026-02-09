# MyGameDay API Reference

This plugin scrapes data from MyGameDay/SportsTG websites to display lacrosse competition information.

## Official Documentation

For detailed instructions, see: [MyGameDay Help - Match Centre IDs](https://helpdesk.mygameday.app/help/adding-and-changing-the-match-centre-ids)

## Competition ID Format

Competition IDs must be in the following format:

```
0-<Association>-<Competition>-0
```

### Components

1. **Association ID**: A hyphenated number identifying your association (e.g., `1064-96359`)
2. **Competition ID**: The specific competition number (e.g., `646414`)

### Example

- Association: `1064-96359`
- Competition: `646414`
- **Full Competition ID**: `0-1064-96359-646414-0`

## Finding Your Competition ID

1. Go to your GameDay Passport website
2. Navigate to **DRAWS and RESULTS**
3. Ensure the correct season is selected
4. Look at the URL in your browser's address bar
5. The Competition ID will be in the format shown above

Example URL:
```
https://websites.mygameday.app/comp_ladder.cgi?c=0-1064-96359-646414-0&round=1
```

The Competition ID is: `0-1064-96359-646414-0`

## API Endpoints Used

The plugin uses the following MyGameDay endpoints:

### Ladder/Standings
```
https://websites.mygameday.app/comp_ladder.cgi?c={comp_id}&round={round_num}
```

Returns the competition ladder/standings for a specific round.

### Fixtures/Results
```
https://websites.mygameday.app/comp_info.cgi?c={comp_id}&pool={pool_num}&round={round_num}&a=FIXTURE
```

Returns fixtures and results for a specific round in the selected pool.

The plugin also supports the GameDay alternate URL format used by some competitions:
```
https://websites.mygameday.app/comp_info.cgi?client={comp_id}&pool={pool_num}&action=FIXTURE&round={round_num}
```

## Data Structure

The plugin scrapes and stores the following data:

### Ladder Data (`ladder-{comp_id}.json`)
- Team position
- Team name
- Games played
- Wins, losses, draws
- Goals for/against
- Percentage
- Total points

### Fixtures Data (`fixtures-{comp_id}.json`)
- All fixtures across all rounds
- Home and away teams
- Date and time
- Venue
- Scores (if completed)
- Finals metadata:
  - `pool` is `1001` for finals, `1` for regular season
  - `stage` is `Finals` or `Regular Season`
  - `round_label` is the display label (e.g., `Semi Final`, `Preliminary Final`, `Grand Final`)

### Upcoming Games (`upcoming-{comp_id}.json`)
- Filtered list of future games
- Sorted by date
- Includes finals games when they are future-dated

### Results (`results-{comp_id}.json`)
- Filtered list of completed games
- Includes final scores
- Sorted by date (most recent first)
- Includes finals when they are completed

## Scraping Process

1. **Ladder**: Fetches current standings for the specified round
2. **Fixtures**: Iterates through all rounds (1 to max_rounds) to collect all fixtures
  - Regular season uses `pool=1`
  - Finals use `pool=1001` and can include up to 5 weeks by default
  - `round=0` may be used by the finals pool to return all rounds
3. **Separation**: Automatically separates upcoming games from completed results
4. **Storage**: Saves data as JSON files in the `/data/` directory
5. **Caching**: Uses WordPress transients to cache data and reduce server load

## Finals Labels

Finals fixtures are labeled using the first available source, in this order:

1. `MatchName` from the GameDay fixtures payload (if provided)
2. Finals round names from the competition API (`awsapi.foxsportspulse.com/v2/compdata/competitions/{onlineCompID}`)
3. A default finals mapping:
  - Round 1: Semi Final
  - Round 2: Preliminary Final
  - Round 3: Grand Final

If none of the above are available, the label falls back to `Finals Week X`.

## Fixture Filtering

Fixtures that contain no teams (for example, placeholder rows like `Undecided` vs empty) are filtered out during scraping so they do not appear in results or upcoming games.

## Rate Limiting

The plugin includes a 0.5-second delay between round requests to avoid overwhelming MyGameDay servers:

```php
usleep(500000); // 0.5 seconds
```

## Notes

- Data is stored in JSON format for easy parsing
- The plugin respects WordPress HTTP API and error handling
- All external requests use proper user-agent headers
- Error logging helps diagnose scraping issues
- Data files are protected by `.htaccess` to prevent direct access

## Troubleshooting

If scraping fails:

1. Verify the Competition ID format is correct
2. Check WordPress debug.log for detailed error messages
3. Ensure the data directory exists and is writable
4. Verify the competition exists on MyGameDay
5. Check that the season is published on MyGameDay

Enable WordPress debugging in `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Then check `/wp-content/debug.log` for detailed error information.

## WP-CLI Commands

The plugin provides WP-CLI commands for managing competitions from the command line.

### List All Competitions

List all configured competitions with their IDs and friendly names:

```bash
wp lmc list-competitions
```

Example output:
```
+----------------------+----------------------------------+
| Competition ID       | Competition Name                 |
+----------------------+----------------------------------+
| 0-1064-96359-646414-0| Men's Premier League 2024        |
| 0-1064-96359-646415-0| Women's Premier League 2024      |
+----------------------+----------------------------------+
Success: Found 2 competition(s).
```

The current competition (if set) will be marked with "(current)" after the name.

### Get Competition Details

Get detailed information about a specific competition:

```bash
wp lmc get-competition <competition-id>
```

Example:
```bash
wp lmc get-competition 0-1064-96359-646414-0
```

Example output:
```
Competition Details:
-------------------
ID: 0-1064-96359-646414-0
Name: Men's Premier League 2024
Is Current: Yes
Data Available: Yes

Data Files:
  ✓ Ladder: Last updated 2 hours ago (12 KB)
  ✓ Fixtures: Last updated 2 hours ago (45 KB)
  ✓ Upcoming: Last updated 2 hours ago (8 KB)
  ✓ Results: Last updated 2 hours ago (23 KB)
Success: Competition details retrieved.
```

## PHP API

The plugin provides public methods for accessing competition data programmatically.

### Get All Competitions

```php
$competitions = LMC_Data::get_all_competitions();
// Returns array of competitions: [['id' => '...', 'name' => '...'], ...]
```

### Get Current Competition ID

```php
$current_comp_id = LMC_Data::get_current_competition_id();
// Returns competition ID string or false if none set
```
