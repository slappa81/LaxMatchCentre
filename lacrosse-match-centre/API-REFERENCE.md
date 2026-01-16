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
https://websites.mygameday.app/comp_display_round.cgi?c={comp_id}&round={round_num}
```

Returns all fixtures and results for a specific round.

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

### Upcoming Games (`upcoming-{comp_id}.json`)
- Filtered list of future games
- Sorted by date

### Results (`results-{comp_id}.json`)
- Filtered list of completed games
- Includes final scores
- Sorted by date (most recent first)

## Scraping Process

1. **Ladder**: Fetches current standings for the specified round
2. **Fixtures**: Iterates through all rounds (1 to max_rounds) to collect all fixtures
3. **Separation**: Automatically separates upcoming games from completed results
4. **Storage**: Saves data as JSON files in the `/data/` directory
5. **Caching**: Uses WordPress transients to cache data and reduce server load

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
