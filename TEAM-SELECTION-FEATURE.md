# Team Selection Feature

## Overview
This feature extends the Lacrosse Match Centre plugin with the ability to select one or more primary teams for each competition and display team-specific results and upcoming games in dedicated blocks.

## What's New

### 1. Admin Interface Enhancements
- **Primary Team Selection**: Each competition now has a "Primary Team(s)" selector in the admin settings (multi-select)
- **Team Discovery Button**: After scraping competition data, click "Load Teams" to populate the team list
- **Automatic Team Loading**: Teams are automatically loaded from fixture data if available

### 2. New Data Methods
Added to `class-lmc-data.php`:
- `get_primary_team($comp_id)` - Retrieves the primary team for a competition
- `get_primary_teams($comp_id)` - Retrieves all primary teams for a competition
- `get_teams_list($comp_id)` - Extracts all unique teams from fixture data
- `get_team_results($comp_id, $team_name, $limit)` - Gets results filtered by team (home or away)
- `get_team_upcoming($comp_id, $team_name, $limit)` - Gets upcoming games filtered by team (home or away)

### 3. New Gutenberg Blocks
Two new blocks are available in the WordPress block editor:

#### **Team Results** (`lacrosse-match-centre/team-results`)
Displays recent results for a specific team with:
- Team name prominently displayed
- Opponent name
- Scores for both teams
- Home/Away indicator (uses "vs" for home games, "@" for away games)
- Round number and venue information

**Block Settings:**
- Title (customizable)
- Competition (optional - defaults to current)
- Team Name (optional - defaults to primary team list)
- Number of Results (1-20, default: 5)

#### **Team Upcoming Games** (`lacrosse-match-centre/team-upcoming`)
Displays upcoming games for a specific team with:
- Team name prominently displayed
- Opponent name
- Date and time
- Home/Away indicator
- Round number and venue information

**Block Settings:**
- Title (customizable)
- Competition (optional - defaults to current)
- Team Name (optional - defaults to primary team list)
- Number of Games (1-20, default: 5)

## Usage Instructions

### Setting Up Primary Team(s)

1. **Navigate to Settings**
   - Go to WordPress Admin → Settings → Match Centre

2. **Configure Competition**
   - Either add a new competition or use an existing one
   - Make sure to scrape data first (click "Scrape Data" button)

3. **Load Teams**
   - After scraping, click the "Load Teams" or "Refresh Teams" button
   - This fetches all unique teams from the competition fixtures

4. **Select Primary Team(s)**
   - Choose one or more teams from the "Primary Team(s)" selector
   - Click "Save Settings"

### Using Team Blocks

1. **Add Block to Page/Post**
   - Open the block editor
   - Search for "Team Results" or "Team Upcoming Games"
   - Insert the block

2. **Configure Block Settings**
   - Open the block settings panel (right sidebar)
   - Set a custom title (optional)
   - Select a specific competition (optional - uses current by default)
   - Enter a team name override (optional - uses primary team list by default)
   - Adjust the number of results/games to display

3. **Publish**
   - The block will automatically display data for the selected/primary team(s)

## Team Filtering Logic

Both blocks filter games/results where the selected team is **either home or away**. If no team is specified, the blocks render a section per primary team.
- Results show the team's score first, opponent second
- Upcoming games show team name in bold/primary styling
- Venue information is prefixed with "vs" for home games and "@" for away games

## Technical Details

### Data Storage
The primary team list is stored in the `lmc_settings` WordPress option under each competition:
```php
'competitions' => [
    [
        'id' => '0-1064-0-646414-0',
        'name' => '2024 - Men's Premier League',
        'season' => '2024',
      'primary_teams' => ['Perth Lacrosse Club', 'Perth Lacrosse Club Reserves'],
      'primary_team' => 'Perth Lacrosse Club'
    ]
]
```

### Block Attributes
Both team blocks accept:
- `title` (string) - Block title
- `compId` (string) - Competition ID override
- `teamName` (string) - Team name override
- `limit` (number) - Number of items to display

### AJAX Handler
New AJAX action: `lmc_get_teams`
- Endpoint: `wp-ajax` hook
- Security: Uses nonce verification
- Returns: Array of team names from fixtures

## CSS Classes

### Team Results Block
- `.lmc-team-results-block` - Block container
- `.lmc-team-result` - Individual result
- `.lmc-result-primary-team` - Primary team row
- `.lmc-result-opponent` - Opponent row
- `.lmc-home` / `.lmc-away` - Home/Away indicators

### Team Upcoming Block
- `.lmc-team-upcoming-block` - Block container
- `.lmc-team-game` - Individual game
- `.lmc-team-primary` - Primary team name
- `.lmc-team-opponent` - Opponent name
- `.lmc-team-home` / `.lmc-team-away` - Position indicators

## Benefits

1. **Focused Content**: Display only relevant matches for your club/team
2. **Flexibility**: Override team selection per block if needed
3. **Maintains Compatibility**: Existing blocks (ladder, results, upcoming) still work as before
4. **Easy Setup**: Simple dropdown selection in admin
5. **Smart Defaults**: Uses primary team automatically, but allows overrides
6. **Clear Presentation**: Shows home/away status and highlights primary team

## Future Enhancements (Possible)

- Team logo support
- Win/loss/draw statistics for team
- Head-to-head comparison blocks
- Team ladder position widget
- Auto-update team list when scraping
