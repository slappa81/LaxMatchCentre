# Diagnostic Steps for "No Data" Issue

## Problem
Blocks show messages like:
- "No results available. Please scrape data from the admin panel."
- "No ladder data available. Please scrape data from the admin panel."
- "No upcoming games available. Please scrape data from the admin panel."

## Root Cause
The `data/` directory has no JSON files. The blocks read data from JSON files that are created by the scraper.

## Solution Steps

### Step 1: Verify Plugin is Active
1. Go to WordPress Admin → Plugins
2. Ensure "Lacrosse Match Centre" is activated

### Step 2: Configure Competition
1. Go to WordPress Admin → Settings → Match Centre
2. Add a competition:
   - **Competition ID**: Format is `0-{Association}-0-{Competition}-0`
   - **Competition Name**: Give it a descriptive name
   - Example: `0-1064-0-646414-0` for association 1064, competition 646414
3. Select the radio button "Use as current competition"
4. Click "Save Changes"

### Step 3: Scrape Data
1. On the same settings page, click the "Scrape Data" button for your competition
2. Wait for the scraping to complete (may take 1-2 minutes)
3. You should see a success message: "✓ Successfully scraped all data"

### Step 4: Verify Data Files
Check that JSON files were created in: `lacrosse-match-centre/data/`
- Should contain files like:
  - `ladder-{comp_id}.json`
  - `fixtures-{comp_id}.json`
  - `upcoming-{comp_id}.json`
  - `results-{comp_id}.json`

### Step 5: Check Block Configuration
When adding blocks to a page:
1. If you have multiple competitions, specify the Competition ID in block settings
2. If you have only one competition marked as "current", you can leave it blank

### Step 6: Clear Cache (if needed)
1. Go to Settings → Match Centre
2. Click "Clear Cache" button
3. Refresh your page

## Troubleshooting

### If scraping fails:
1. Check that your Competition ID is correct
2. Check browser console for errors
3. Check WordPress debug log at `wp-content/debug.log`

### If data files exist but blocks still show "no data":
1. Verify the competition ID in block settings matches the scraped data
2. Clear the WordPress cache
3. Check file permissions on the `data/` directory (must be writable)

### Common Issues:
- **Wrong Competition ID format**: Must be `0-X-0-Y-0` format
- **No current competition set**: Check radio button is selected
- **Permissions issue**: Data directory must be writable by web server
- **Block competition ID mismatch**: Block compId must match scraped data

## Expected File Structure After Scraping
```
lacrosse-match-centre/
  data/
    README.txt
    ladder-0-1064-0-646414-0.json
    fixtures-0-1064-0-646414-0.json
    upcoming-0-1064-0-646414-0.json
    results-0-1064-0-646414-0.json
```

## Testing
After scraping, you should be able to:
1. Add blocks to a page/post
2. See data displayed without error messages
3. Widgets should also work if you have the competition configured
