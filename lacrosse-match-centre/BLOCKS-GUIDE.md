# Lacrosse Match Centre - Blocks Guide

## Overview

The Lacrosse Match Centre plugin now includes **Gutenberg blocks** for displaying competition data. Blocks are the modern way to add content in WordPress and offer a better editing experience than traditional widgets.

## Available Blocks

### 1. Lacrosse Ladder Block
Displays the competition ladder/standings table.

**Block Name:** `lacrosse-match-centre/ladder`

**Settings:**
- **Title**: Custom heading for the block (default: "Competition Ladder")
- **Competition ID**: Optional - leave empty to use scraped competition data

**Usage:**
1. Add a new block in the editor
2. Search for "Lacrosse Ladder"
3. Configure the title in the block settings panel
4. Optionally specify a Competition ID

### 2. Upcoming Games Block
Shows upcoming fixtures.

**Block Name:** `lacrosse-match-centre/upcoming`

**Settings:**
- **Title**: Custom heading for the block (default: "Upcoming Games")
- **Competition ID**: Optional - leave empty to use scraped competition data
- **Number of Games**: How many upcoming games to display (1-20, default: 5)

**Usage:**
1. Add a new block in the editor
2. Search for "Upcoming Games"
3. Configure settings in the block settings panel
4. Adjust the number of games with the range slider

### 3. Match Results Block
Displays recent match results.

**Block Name:** `lacrosse-match-centre/results`

**Settings:**
- **Title**: Custom heading for the block (default: "Recent Results")
- **Competition ID**: Optional - leave empty to use scraped competition data
- **Number of Results**: How many results to display (1-20, default: 5)

**Usage:**
1. Add a new block in the editor
2. Search for "Match Results"
3. Configure settings in the block settings panel
4. Adjust the number of results with the range slider

### 4. Results + Upcoming Block
Combines recent results and upcoming fixtures in a single carousel.

**Block Name:** `lacrosse-match-centre/results-upcoming`

**Settings:**
- **Title**: Custom heading (default: "Results & Upcoming")
- **Competition ID**: Optional - leave empty to use scraped competition data
- **Results Limit**: How many results to display (1-20, default: 5)
- **Upcoming Limit**: How many upcoming games to display (1-20, default: 5)

**Usage:**
1. Add a new block in the editor
2. Search for "Results + Upcoming"
3. Configure limits in the block settings panel

### 5. Team Results Block
Displays results for a selected team.

**Block Name:** `lacrosse-match-centre/team-results`

**Settings:**
- **Title**: Custom heading (default: "Team Results")
- **Competition ID**: Optional - leave empty to use scraped competition data
- **Team Name**: Optional - leave empty to use Primary Team(s)
- **Number of Results**: How many results to display (1-20, default: 5)

**Usage:**
1. Add a new block in the editor
2. Search for "Team Results"
3. Configure settings in the block settings panel

### 6. Team Upcoming Games Block
Displays upcoming fixtures for a selected team.

**Block Name:** `lacrosse-match-centre/team-upcoming`

**Settings:**
- **Title**: Custom heading (default: "Team Upcoming Games")
- **Competition ID**: Optional - leave empty to use scraped competition data
- **Team Name**: Optional - leave empty to use Primary Team(s)
- **Number of Games**: How many games to display (1-20, default: 5)

**Usage:**
1. Add a new block in the editor
2. Search for "Team Upcoming Games"
3. Configure settings in the block settings panel

## Adding Blocks to Your Site

### Using the Block Editor

1. **Edit a Page or Post:**
   - Navigate to the page or post where you want to add a block
   - Click the "+" icon to add a new block

2. **Search for the Block:**
   - Type "Lacrosse" or the specific block name
   - Click on the desired block

3. **Configure the Block:**
   - Use the settings panel on the right to configure the block
   - The block preview will update in real-time

4. **Publish:**
   - Click "Update" or "Publish" to save your changes

### Block Categories

All Lacrosse Match Centre blocks appear under the **"Widgets"** category in the block inserter.

## Block Styling

The blocks come with professional styling out of the box:

### Ladder Block
- Clean table layout with alternating row hover effects
- Responsive design that adapts to mobile screens
- Team position, wins, losses, draws, and points columns

### Upcoming Games Block
- Card-based layout for each game
- Shows round number, date, time, teams, and venue
- Clear visual separation between home and away teams

### Results Block
- Card-based layout for completed matches
- Displays scores for both teams
- Shows round number, date, and venue information

### Results + Upcoming Block
- Horizontal carousel for results and upcoming games
- Auto-scrolls to the latest items on load

### Team Blocks
- Each Primary Team renders its own section
- Carousel controls are scoped per team when multiple teams are selected

## Customizing Appearance

### Using Additional CSS

You can add custom CSS to further customize the blocks:

1. Go to **Appearance > Customize > Additional CSS**
2. Add your custom styles:

```css
/* Example: Change ladder header color */
.lmc-ladder-table thead th {
    background: #your-color !important;
}

/* Example: Adjust game card spacing */
.lmc-game {
    margin-bottom: 20px;
    padding: 20px;
}

/* Example: Change score color in results */
.lmc-result-score {
    color: #your-color !important;
}
```

### Theme Integration

The blocks use semantic CSS classes that can be targeted by your theme:
- `.lmc-ladder-block`, `.lmc-upcoming-block`, `.lmc-results-block` - Main block containers
- `.lmc-results-upcoming-block` - Combined results/upcoming block
- `.lmc-team-results-block`, `.lmc-team-upcoming-block` - Team block containers
- `.lmc-block-title` - Block title/heading
- `.lmc-game`, `.lmc-result` - Individual game/result cards
- `.lmc-no-data` - No data message styling
- `.lmc-carousel`, `.lmc-carousel-btn` - Carousel elements

## Backwards Compatibility

The plugin maintains **full backward compatibility** with existing widget installations:

- All existing widgets will continue to work
- You can find widgets under **Appearance > Widgets** (legacy editor)
- You can gradually migrate from widgets to blocks at your own pace

## Server-Side Rendering

All blocks use **server-side rendering**, which means:
- Data is always fresh when the page is viewed
- No JavaScript required on the front-end
- Better performance and SEO
- Respects caching settings configured in the admin panel

## Block Editor Preview

The block editor shows a **live preview** of your blocks while editing, making it easy to see exactly how they will appear on your site.

## Troubleshooting

### Blocks Not Appearing

1. **Clear Plugin Cache:**
   - Go to the admin panel (Settings > Lacrosse Match Centre)
   - Click "Clear Cache"

2. **Check WordPress Version:**
   - Ensure you're running WordPress 5.0 or higher
   - The block editor (Gutenberg) must be enabled

3. **Re-save Permalinks:**
   - Go to Settings > Permalinks
   - Click "Save Changes" (even without making changes)

### No Data Showing

1. **Scrape Competition Data:**
   - Go to Settings > Lacrosse Match Centre
   - Enter your Competition ID and Name
   - Click "Scrape Data Now"
   - Wait for confirmation message

2. **Check Competition ID:**
   - Ensure the Competition ID is correct
   - Format: 0-{Association}-0-{Competition}-0
   - Example: 0-1064-0-646414-0

3. **Cached Pages:**
   - If blocks show "Unable to load data", hard refresh and clear any page cache
   - Verify the page is not stripping the AJAX nonce from block updates

### Styling Issues

1. **Clear Browser Cache:**
   - Use Ctrl+F5 (or Cmd+Shift+R on Mac) to hard refresh

2. **Check for Theme Conflicts:**
   - Try temporarily switching to a default WordPress theme
   - If blocks work, there may be a theme CSS conflict

3. **Disable Other Plugins:**
   - Temporarily disable other plugins to check for conflicts
   - Re-enable them one by one to identify the issue

## Best Practices

1. **Use Blocks for New Content:**
   - For new pages/posts, use blocks instead of widgets
   - Blocks offer better editing experience and flexibility

2. **Set Appropriate Limits:**
   - Don't display too many games/results on one page
   - Keep limits between 5-10 for best user experience

3. **Add Descriptive Titles:**
   - Use clear, descriptive titles for your blocks
   - Examples: "Next 5 Games", "Latest Results", "2025 Ladder"

4. **Mobile Testing:**
   - Always preview on mobile devices
   - Blocks are responsive but test your specific theme

5. **Regular Data Updates:**
   - Schedule regular data scraping (weekly recommended)
   - Keep competition data current throughout the season

## Technical Details

### Block Registration

Blocks are registered using WordPress's `register_block_type()` function with:
- **API Version**: 2
- **Render Callback**: Server-side PHP rendering
- **Attributes**: Stored as block metadata

### Data Flow

1. User adds block in editor
2. Block settings saved to post content as HTML comments
3. On page load, WordPress calls the render callback
4. PHP renders the block using current competition data
5. Cached data is used (respecting cache duration settings)

### File Structure

```
lacrosse-match-centre/
├── includes/
│   └── class-lmc-blocks.php       # Block registration and rendering
├── assets/
│   ├── blocks.js                  # Block editor JavaScript
│   └── blocks.css                 # Block styles
```

## Support

For issues, questions, or feature requests:
- GitHub: https://github.com/slappa81/LaxMatchCentre
- Email: support@williamstownlacrosse.com

---

**Version**: 1.0.0  
**Last Updated**: February 2026
