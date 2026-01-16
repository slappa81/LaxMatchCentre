# Installation Guide

## Quick Installation

### Method 1: Upload via WordPress Admin (Recommended)

1. **Prepare the Plugin**
   - Zip the `lacrosse-match-centre` folder
   - Name it `lacrosse-match-centre.zip`

2. **Upload to WordPress**
   - Log into your WordPress admin panel
   - Go to **Plugins → Add New**
   - Click **Upload Plugin**
   - Choose the `lacrosse-match-centre.zip` file
   - Click **Install Now**

3. **Activate**
   - Click **Activate Plugin**
   - You should see "Lacrosse Match Centre" in your plugins list

### Method 2: FTP/File Manager Upload

1. **Upload Files**
   - Connect to your server via FTP or use your hosting control panel's file manager
   - Navigate to `wp-content/plugins/`
   - Upload the entire `lacrosse-match-centre` folder

2. **Activate**
   - Go to WordPress admin → **Plugins**
   - Find "Lacrosse Match Centre" in the list
   - Click **Activate**

## Configuration

### Step 1: Add a Competition

1. Go to **Settings → Match Centre** in WordPress admin
2. Click **Add Competition**
3. Fill in the details:
   - **Competition ID**: Find this in the SportsTG URL (e.g., `140768`)
   - **Competition Name**: E.g., "Men's Division 1"
   - **Current Round**: The current round number (e.g., `8`)
   - **Max Rounds**: Total rounds in the season (e.g., `18`)
4. Select **Use as current competition** (radio button)
5. Click **Save Settings**

### Step 2: Scrape Data

1. After saving, click the **Scrape Data** button for your competition
2. Wait for the process to complete (may take 30-60 seconds)
3. You should see a success message with data status

### Step 3: Add Widgets

1. Go to **Appearance → Widgets**
2. Find the Lacrosse Match Centre widgets:
   - **LMC: Competition Ladder**
   - **LMC: Upcoming Games**
   - **LMC: Recent Results**
3. Drag widgets to your desired sidebar or widget area
4. Configure each widget:
   - Set a title
   - Select competition (or use default)
   - For Upcoming/Results: set number of items to display
5. Click **Save**

## Finding Your Competition ID

To find your SportsTG competition ID:

1. Go to SportsTG website
2. Navigate to your competition/league
3. Look at the URL in your browser
4. The competition ID is in the URL after `c=`
   
   Example: `https://www.sportstg.com/comp_ladder.cgi?c=140768&round=8`
   
   Competition ID = `140768`

## Verifying Installation

### Check Data Files

After scraping, you should see data files in:
`wp-content/plugins/lacrosse-match-centre/data/`

Files created:
- `ladder-{comp_id}.json`
- `fixtures-{comp_id}.json`
- `upcoming-{comp_id}.json`
- `results-{comp_id}.json`

### Check Widget Display

1. Visit your website frontend
2. Navigate to a page with the widgets
3. Verify data is displaying correctly

## Troubleshooting

### Data Not Showing

**Problem**: Widgets show "No data available"

**Solutions**:
1. Go to Settings → Match Centre
2. Click **Scrape Data** for your competition
3. Wait for completion
4. Check that data files exist in the `data/` folder
5. Click **Clear Cache** if data still doesn't appear

### Permission Errors

**Problem**: Cannot write to data directory

**Solutions**:
1. Set proper permissions on `data/` folder (755 or 775)
2. Ensure WordPress can write to plugin directories
3. Contact your hosting provider if issues persist

### Scraping Fails

**Problem**: "Failed to scrape data" message

**Solutions**:
1. Verify the Competition ID is correct
2. Check that SportsTG website is accessible
3. Increase PHP `max_execution_time` if timeout occurs
4. Verify your server can make outbound HTTP requests

### Widgets Not Appearing

**Problem**: Widgets don't show in Appearance → Widgets

**Solutions**:
1. Deactivate and reactivate the plugin
2. Check for PHP errors in debug.log
3. Ensure plugin files uploaded correctly

## System Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- PHP Extensions:
  - DOMDocument (usually enabled by default)
  - JSON (usually enabled by default)
- Server must allow outbound HTTP requests

## Security Notes

- Data directory is protected by .htaccess
- All inputs are sanitized and validated
- Output is properly escaped
- Admin functions check user capabilities
- AJAX requests use nonces for security

## Next Steps

After installation:
1. Set up automatic updates (optional) - see [Deployment Guide](DEPLOYMENT.md)
2. Customize widget styling if needed
3. Test on mobile devices
4. Set cache duration based on your needs

## Support

For issues or questions:
- Check the README.md for detailed documentation
- Review TROUBLESHOOTING.md for common issues
- Check WordPress debug.log for errors
- Verify server requirements are met
