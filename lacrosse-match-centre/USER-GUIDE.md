# User Guide

## Overview

The Lacrosse Match Centre plugin displays live competition data from GameDay on your WordPress website. It includes:
- Competition ladder/standings
- Upcoming games schedule
- Recent match results

## Getting Started

### Initial Setup

1. **Install and Activate**
   - See [INSTALL.md](INSTALL.md) for detailed installation instructions

2. **Configure Competition**
   - Go to **Settings → Match Centre**
   - Add your competition details
   - Click **Scrape Data** to fetch latest information

3. **Add Widgets or Blocks**
   - Go to **Appearance → Widgets** for classic widgets
   - Or add blocks in the Gutenberg editor

## Using the Admin Interface

### Settings Page

Access: **Settings → Match Centre**

#### General Settings

**Cache Duration**
- How long data is cached before refreshing
- Default: 3600 seconds (1 hour)
- Minimum: 60 seconds
- Recommended: 1800-3600 seconds for active competitions

#### Managing Competitions

**Add Competition**
1. Click **Add Competition**
2. Enter Competition ID (from GameDay URL)
3. Enter Competition Name
4. Set Current Round number
5. Set Max Rounds (total rounds in season)
6. Select as current competition
7. Click **Save Settings**

**Discover Competitions (Optional)**
- Use the **Discover Competitions** panel to list all available competitions
- Select a season, then click **Use This** to add a competition automatically

**Primary Team(s) (Optional)**
- Click **Load Teams** after scraping
- Select one or more **Primary Team(s)** for team-specific blocks

**Edit Competition**
- Modify any field
- Click **Save Settings**

**Remove Competition**
- Click **Remove** button
- Click **Save Settings**

**Scrape Data**
- Click **Scrape Data** button for a competition
- Wait for completion (30-60 seconds)
- Check data status below the button

### Data Status

After scraping, the admin panel shows:
- ✓ Files that exist
- ✗ Files that are missing
- Last update time
- File sizes

### Cache Management

**Clear Cache Button**
- Clears all cached data
- Forces fresh data load on next request
- Use after scraping new data

## Using Widgets

### Competition Ladder Widget

**Purpose**: Display competition standings

**Settings**:
- **Title**: Widget heading (default: "Competition Ladder")
- **Competition**: Which competition to display (or use default)

**Display**:
- Team position
- Played, Won, Lost, Drawn
- Points
- Sortable columns
- Responsive table

### Upcoming Games Widget

**Purpose**: Show scheduled future matches

**Settings**:
- **Title**: Widget heading (default: "Upcoming Games")
- **Competition**: Which competition to display
- **Number of games**: How many to show (1-20)

**Display**:
- Round number
- Date and time
- Home vs Away teams
- Venue
- Chronologically ordered

### Recent Results Widget

**Purpose**: Display completed matches with scores

**Settings**:
- **Title**: Widget heading (default: "Recent Results")
- **Competition**: Which competition to display
- **Number of results**: How many to show (1-20)

**Display**:
- Round number
- Date
- Teams and scores
- Venue
- Reverse chronological order (newest first)

## Using Blocks

Blocks are available in the Gutenberg editor for the same data as widgets.

### Available Blocks
- Ladder
- Upcoming Games
- Match Results
- Results + Upcoming (carousel)
- Team Results
- Team Upcoming Games

### Block Settings
- **Title**: Custom heading
- **Competition ID**: Optional, defaults to current competition
- **Limits**: Results/upcoming count (1-20)
- **Team Name**: Optional override for team blocks

### Team Blocks Behavior
- If no team name is specified, the block renders a section for each Primary Team
- If multiple Primary Teams are selected, each section gets its own carousel controls

## Updating Data

### Manual Update

1. Go to **Settings → Match Centre**
2. Click **Scrape Data** for the competition
3. Wait for completion
4. Data updates automatically on frontend

### Update Frequency

**Recommended**:
- **During active season**: Scrape after each round completes
- **Weekly**: For regular season updates
- **As needed**: When you know results are available

### Automatic Updates (Optional)

See [DEPLOYMENT.md](DEPLOYMENT.md) for setting up:
- WordPress Cron automation
- Server cron jobs
- WP-CLI integration

## Managing Multiple Competitions

### Adding Multiple Competitions

1. Add each competition separately
2. Select one as "current competition" (default for widgets)
3. Each competition gets separate data files

### Widget Configuration

**Option 1**: Use default competition
- Set one competition as current
- All widgets use this by default

**Option 2**: Specify per widget
- Each widget can select its competition
- Mix different competitions on same page

## Customization

### Changing Widget Titles

Edit in widget settings:
- Go to **Appearance → Widgets**
- Click on the widget
- Change "Title" field
- Click **Save**

### Styling

The plugin includes responsive CSS. To customize:

1. **Via Theme Customizer**:
   - Add custom CSS in **Appearance → Customize → Additional CSS**

2. **Via Child Theme**:
   - Override styles in your child theme's style.css
   - Use class names:
     - `.lmc-ladder-widget`
     - `.lmc-upcoming-widget`
     - `.lmc-results-widget`

3. **Example Customizations**:
```css
/* Change ladder colors */
.lmc-ladder-table thead {
    background: #your-color;
}

/* Adjust widget spacing */
.lmc-game {
    margin-bottom: 20px;
}

/* Change font sizes */
.lmc-team-home,
.lmc-team-away {
    font-size: 16px;
}
```

## Best Practices

### Data Updates

✅ **Do**:
- Update current round number as season progresses
- Scrape data after each round completes
- Check data status after scraping
- Clear cache if data seems stale

❌ **Don't**:
- Scrape too frequently (server load)
- Set cache duration too low
- Delete data files manually

### Widget Placement

✅ **Recommended**:
- Sidebar for all widgets
- Footer for upcoming games
- Dedicated page for full ladder

❌ **Avoid**:
- Too many widgets on one page
- Displaying same widget multiple times

### Performance

✅ **Tips**:
- Use appropriate cache duration
- Limit results shown (5-10 items)
- Don't scrape during peak traffic times

## Troubleshooting

### Common Issues

**No Data Showing**
- Solution: Scrape data from admin panel
- Clear cache and refresh

**Outdated Data**
- Solution: Scrape fresh data
- Check current round is correct

**Widgets Not Displaying**
- Solution: Check widget area is active
- Verify competition has data

**Slow Loading**
- Solution: Increase cache duration
- Reduce number of items shown

### Getting Help

1. Check data status in admin
2. Review browser console for errors
3. Check WordPress debug.log
4. Verify competition ID is correct

## Tips & Tricks

### Competition ID

Find it in GameDay URL:
```
https://websites.mygameday.app/comp_info.cgi?c=0-1064-0-646414-0
                                                  ^^^^^^^^^^^^^^^
                                               This is it!
```

### Round Updates

Update "Current Round" in settings as the season progresses to ensure accurate data scraping.

### Cache Strategy

- **Active season**: 1-2 hours (3600-7200 seconds)
- **Off-season**: 24 hours (86400 seconds)
- **Match day**: 30-60 minutes (1800-3600 seconds)

### Data Validation

After scraping, verify:
- Ladder shows current standings
- Upcoming games are actually upcoming
- Results show recent matches

## Advanced Features

### Multiple Sidebars

Place different widgets in different sidebars:
- Ladder in main sidebar
- Upcoming games in footer
- Results in page-specific sidebar

### Shortcodes (Future)

Currently, only widgets are supported. Shortcode support may be added in future versions.

### REST API (Future)

Data access via REST API may be added for advanced integrations.

## FAQ

**Q: How often should I scrape data?**
A: Once per round is sufficient. After each round completes, scrape to get latest results.

**Q: Can I display multiple competitions?**
A: Yes! Add multiple competitions and select different ones for each widget.

**Q: Will this slow down my site?**
A: No. Data is cached, so widgets load quickly after initial scraping.

**Q: Can I customize the appearance?**
A: Yes! Add custom CSS via your theme or the WordPress Customizer.

**Q: What if GameDay changes their website?**
A: The scraper may need updates. Check for plugin updates regularly.

**Q: Is this legal?**
A: Yes. We're only scraping publicly available data for display on your site.

## Support

For technical support:
- Review documentation files
- Check WordPress debug logs
- Verify system requirements
- Test with default WordPress theme
