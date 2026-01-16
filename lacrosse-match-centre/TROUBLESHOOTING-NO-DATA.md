# Troubleshooting: Blocks Show "No Data" Despite JSON Files Existing

## Your Situation
- ✅ JSON files exist on the remote server in `/data/` directory
- ❌ Blocks still show "Please scrape data from the admin panel" messages

## Most Common Cause: Competition ID Mismatch

The blocks look for JSON files based on the competition ID. If there's a mismatch, they won't find the data.

### Step 1: Check JSON Filenames on Server
SSH or FTP to your server and check the data directory:
```bash
ls -la wp-content/plugins/lacrosse-match-centre/data/
```

You should see files like:
- `ladder-0-12060-0-616436-0.json`
- `fixtures-0-12060-0-616436-0.json`
- `upcoming-0-12060-0-616436-0.json`
- `results-0-12060-0-616436-0.json`

**Note the competition ID in the filename** (e.g., `0-12060-0-616436-0`)

### Step 2: Check WordPress Settings
1. Go to WordPress Admin → **Settings → Match Centre**
2. Look at your configured competitions
3. Check which one has the radio button selected as "Use as current competition"
4. **The ID must EXACTLY match the filenames**

### Step 3: Check Block Configuration
When you added the blocks to your page:
- Did you specify a **Competition ID** in the block settings?
- If yes, does it match the JSON filenames?
- If no, is there a "current competition" selected in settings?

### Step 4: Enable Debug Logging
Add to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Step 5: View the Page & Check Logs
1. Load the page with the blocks
2. Check `wp-content/debug.log`
3. Look for lines containing "LMC" - they will show:
   - What competition ID is being used
   - What files are being looked for
   - Whether data was found

Example log entries:
```
LMC Blocks: Rendering ladder block with compId: NULL (will use current)
LMC Data: No comp_id provided, using current competition: 0-12060-0-616436-0
LMC Data: Reading ladder from file: ladder-0-12060-0-616436-0.json
LMC Data: Successfully loaded ladder with 8 teams
```

## Common Issues & Solutions

### Issue 1: No Current Competition Set
**Symptoms:** Logs show "using current competition: NONE"

**Solution:**
1. Go to Settings → Match Centre
2. Select the radio button for "Use as current competition"
3. Click "Save Changes"

### Issue 2: Wrong Competition ID Format
**Symptoms:** JSON files exist but with different IDs

**Solution:**
Competition ID must be in format: `0-{Association}-0-{Competition}-0`
- Correct: `0-12060-0-616436-0`
- Wrong: `12060-616436`, `616436`, `0-616436-0`

### Issue 3: File Permissions
**Symptoms:** Can't read files even though they exist

**Solution:**
```bash
chmod 644 wp-content/plugins/lacrosse-match-centre/data/*.json
chmod 755 wp-content/plugins/lacrosse-match-centre/data/
```

### Issue 4: Cached Empty Results
**Symptoms:** Data exists but WordPress cached the "no data" state

**Solution:**
1. Go to Settings → Match Centre
2. Click "Clear Cache" button
3. Refresh the page

### Issue 5: Multiple Competitions Configured
**Symptoms:** Have multiple competitions, blocks use wrong one

**Solution:**
- **Option A:** Set the correct one as "current competition" in settings
- **Option B:** Specify Competition ID in block settings when adding to page

### Issue 6: Typo in Competition ID
**Symptoms:** Settings show one ID, files have slightly different ID

**Solution:**
1. Delete the competition from settings
2. Re-add it with the EXACT ID from the JSON filenames
3. Mark as current
4. Save

## Debug Checklist

Run through this checklist:

- [ ] JSON files exist in `wp-content/plugins/lacrosse-match-centre/data/`
- [ ] Note the exact competition ID from filenames (e.g., `ladder-XXXXXXXXX.json`)
- [ ] Check Settings → Match Centre has a competition with that EXACT ID
- [ ] That competition is selected as "current competition"
- [ ] Settings have been saved
- [ ] Cache has been cleared (Settings → Match Centre → Clear Cache)
- [ ] Debug logging is enabled in `wp-config.php`
- [ ] Viewed the page and checked `wp-content/debug.log`

## After Fixing

Once you've verified the competition ID matches:
1. **Upload the updated plugin files** (with enhanced debugging)
2. **Clear the cache** (Settings → Match Centre → Clear Cache)
3. **Refresh the page**
4. **Check debug.log** to see what's happening

The enhanced logging will show you exactly what competition ID is being used and whether data is being loaded.

## Still Not Working?

Check the debug log and provide these details:
1. Exact JSON filenames on server
2. Competition ID in WordPress settings
3. Is "current competition" selected?
4. Relevant lines from debug.log showing LMC messages
