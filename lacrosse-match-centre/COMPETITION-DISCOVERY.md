# Competition Discovery Guide

This plugin now includes functionality to automatically discover all available competitions from GameDay with their friendly names.

## Methods Available

### 1. WordPress Admin Interface (Easiest)

**Steps:**
1. Go to **Settings → Match Centre** in WordPress admin
2. Find the "🔍 Discover Competitions" section at the top
3. Enter your **Association ID** (e.g., `1064` for Lacrosse Victoria)
4. Click **Load Seasons** and choose the season
5. Click **Discover Competitions**
6. Browse the list of available competitions
7. Click **Use This** next to any competition to add it automatically
8. Click **Save Settings** to save your changes

**Benefits:**
- No command line needed
- Visual interface
- One-click to add competitions
- See all available competitions at once

### 2. WP-CLI Command

**Usage:**
```bash
wp lmc list-available-competitions <association-id>
```

**Example:**
```bash
wp lmc list-available-competitions 1064
```

**Output:**
```
+-------------------------+----------------------------------+
| Competition ID          | Competition Name                 |
+-------------------------+----------------------------------+
| 0-1064-0-646414-0       | Men's State League               |
| 0-1064-0-646422-0       | Women's State League             |
| 0-1064-0-646413-0       | U12 Boys - East                  |
| 0-1064-0-646410-0       | U12 Boys - West                  |
| 0-1064-0-646424-0       | U12 Girls                        |
| 0-1064-0-646416-0       | U14 Boys Sixes Olympic Cup       |
| 0-1064-0-646423-0       | U16 Boys                         |
+-------------------------+----------------------------------+
```

**Benefits:**
- Good for automation
- Can be used in scripts
- Outputs formatted table

### 3. Standalone PHP Script

**Usage:**
```bash
php list-competitions.php <association-id>
```

**Example:**
```bash
cd /path/to/LaxMatchCentre
php list-competitions.php 1064
```

**Benefits:**
- Works without WordPress
- No database needed
- Quick testing
- Can be run anywhere with PHP and cURL

## Finding Your Association ID

Your Association ID is in any GameDay URL:

```
https://websites.mygameday.app/comp_info.cgi?c=0-1064-0-646414-0
                                                  ^^^^
                                            Association ID
```

Common Association IDs:
- **1064** - Lacrosse Victoria
- (Add your association ID here)

## What Gets Listed

The discovery feature shows:
- **Competition ID**: Full ID in format `0-XXXX-0-YYYY-0`
- **Competition Name**: Human-readable name (e.g., "Men's State League")
- **All active competitions** for the current season

## Using the Results

Once you have the competition IDs:

1. **Add to WordPress**:
   - Copy the Competition ID
   - Go to Settings → Match Centre
   - Add Competition
   - Paste the ID
   - Add a friendly name
   - Save Settings

2. **Scrape Data**:
   - After adding, click "Scrape Data"
   - Wait for completion
   - Data is now available for widgets

## Troubleshooting

### No Competitions Found
- Verify the Association ID is correct
- Check that you have an active season on GameDay
- Ensure the GameDay website is accessible

### Wrong Competitions Listed
- The list shows ALL competitions for the association
- Filter manually to find the ones you need
- Check the season year in competition names

### Error Messages
- Check WordPress debug.log for detailed errors
- Verify internet connectivity
- Ensure GameDay website is not down

## Examples

### Example 1: Finding Lacrosse Victoria Competitions
```bash
# Via WP-CLI
wp lmc list-available-competitions 1064

# Via PHP script
php list-competitions.php 1064
```

### Example 2: Using in a Script
```bash
#!/bin/bash
# List competitions and save to file
wp lmc list-available-competitions 1064 > competitions.txt
```

## API Integration

The feature works by:
1. Fetching a GameDay page for your association
2. Parsing the competition dropdown HTML
3. Extracting competition IDs and names
4. Displaying them in a formatted list

**URL Format:**
```
https://websites.mygameday.app/comp_info.cgi?c=0-{ASSOCIATION_ID}-0-0-0&a=FIXTURE
```

The page includes a `<select id="compselectbox">` dropdown with all competitions.

## Development Notes

### Code Locations

**Scraper Method:**
- File: `includes/class-lmc-scraper.php`
- Method: `list_competitions($association_id, $season_id = null)`
- Returns: Array of `['id' => ..., 'name' => ...]`

**WP-CLI Command:**
- File: `includes/class-lmc-cli.php`
- Command: `list_available_competitions`

**Admin AJAX Handler:**
- File: `includes/class-lmc-admin.php`
- Action: `lmc_list_available_competitions`

**Standalone Script:**
- File: `list-competitions.php` (root directory)

### Adding New Features

To extend this functionality:

1. **Filter by Season**: Pass `$season_id` parameter
2. **Filter by Type**: Parse competition names for patterns
3. **Cache Results**: Store competitions list temporarily
4. **Auto-Import**: Automatically add all competitions

## Support

For issues or questions:
1. Check WordPress debug.log
2. Verify Association ID
3. Test with standalone PHP script
4. Check GameDay website directly

## Version History

- **v1.0** - Initial competition discovery feature
  - WordPress admin interface
  - WP-CLI command
  - Standalone PHP script
  - Auto-parse GameDay dropdown
