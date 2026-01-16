# Debugging Competition Discovery

## Error: "No competitions found or unable to fetch from GameDay"

I've updated the code with better error handling and logging. Here's how to debug:

### 1. Check WordPress Debug Log

Enable WordPress debugging in `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Then check `/wp-content/debug.log` for messages like:
```
LMC Scraper: Fetching competitions list from https://websites.mygameday.app/comp_info.cgi?c=0-1064-0-0-0&a=FIXTURE
LMC Scraper: Found competition dropdown
LMC Scraper: Found 25 option elements
LMC Scraper: Adding competition: Men's State League (ID: 0-1064-0-646414-0)
...
```

### 2. Test with Standalone Script

Run the standalone PHP script to get more detailed output:
```bash
php list-competitions.php 1064
```

This will show:
- The URL being fetched
- HTTP status codes
- Whether the dropdown was found
- How many competitions were discovered

### 3. Common Issues

**Issue: Association ID is wrong**
- Solution: Check your GameDay URL to confirm the association ID
- Example URL: `https://websites.mygameday.app/comp_info.cgi?c=0-1064-0-646414-0`
- The second number (1064) is your association ID

**Issue: No active season**
- GameDay might not have any competitions published for the current season
- Check directly on the GameDay website

**Issue: Website structure changed**
- The code now tries two different selectors:
  - `//select[@id='compselectbox']`
  - `//select[@name='client']`
- If both fail, GameDay may have changed their HTML structure

**Issue: Network/connectivity**
- Check that your WordPress site can reach external URLs
- Test with: `curl https://websites.mygameday.app/`

### 4. Manual Test URL

Try visiting this URL in your browser (replace 1064 with your association ID):
```
https://websites.mygameday.app/comp_info.cgi?c=0-1064-0-0-0&a=FIXTURE
```

You should see a page with a competition dropdown. If you see a dropdown with competitions, the code should work.

### 5. What I Fixed

**Before:**
- Hardcoded skip for association `1064`
- Less detailed error logging
- Single selector for dropdown

**After:**
- Dynamic skip pattern using regex (skips any `0-XXXX-0-0-0` placeholder)
- Detailed logging at each step
- Tries multiple selectors for the dropdown
- Logs HTML samples when dropdown not found

### 6. Next Steps

If it still doesn't work:
1. Share the contents of `debug.log` 
2. Share the association ID you're using
3. Confirm the GameDay URL works in your browser
4. Run the standalone script and share the output

The enhanced logging will help us identify exactly where the process is failing.
