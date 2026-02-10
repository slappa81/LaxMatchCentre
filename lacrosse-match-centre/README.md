# Lacrosse Match Centre - WordPress Plugin

A complete WordPress plugin for displaying lacrosse league data from GameDay with a built-in PHP scraper.

## 🚀 Features

- **📊 Competition Ladder** - Display current season standings
- **📅 Upcoming Games** - Show scheduled matches  
- **🏆 Recent Results** - Display completed games with scores
- **🔄 Integrated Scraper** - Built-in PHP scraper fetches data directly from GameDay
- **⚡ Data Caching** - Efficient WordPress Transient caching
- **🧩 Blocks + Widgets** - Gutenberg blocks and classic widgets
- **🧑‍🤝‍🧑 Team Blocks** - Team Results and Team Upcoming with multi-team sections
- **🧭 Competition Discovery** - Find competition IDs from GameDay automatically
- **🎠 Results Carousel** - Results/upcoming blocks auto-scroll to latest
- **🎨 Responsive Design** - Mobile-friendly widgets
- **⚙️ Easy Configuration** - Simple admin settings page
- **🏅 Multiple Competitions** - Support for multiple competitions

## 📋 Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- PHP Extensions: DOMDocument, JSON (usually enabled by default)
- Server must allow outbound HTTP requests

## 🔧 Installation

### Quick Install

1. **Download** or clone this repository
2. **Upload** the `lacrosse-match-centre` folder to `/wp-content/plugins/`
3. **Activate** the plugin through WordPress admin → Plugins
4. **Configure** via Settings → Match Centre

See [INSTALL.md](INSTALL.md) for detailed installation instructions.

## 📖 Quick Start

### 1. Add Competition

1. Go to **Settings → Match Centre**
2. Click **Add Competition**
3. Fill in:
    - Competition ID (from GameDay URL)
   - Competition Name
   - Current Round
   - Max Rounds
4. Select "Use as current competition"
5. Click **Save Settings**

### 2. Scrape Data

1. Click **Scrape Data** button
2. Wait 30-60 seconds for completion
3. Check data status

### 2b. (Optional) Configure Primary Teams

1. Click **Load Teams** in the competition row
2. Select one or more **Primary Team(s)**
3. Click **Save Settings**

### 3. Add Widgets

1. Go to **Appearance → Widgets**
2. Add desired widgets:
   - LMC: Competition Ladder
   - LMC: Upcoming Games
   - LMC: Recent Results
3. Configure and save

### 4. (Optional) Add Blocks

1. Edit a page or post in the block editor
2. Search for "Lacrosse" blocks
3. Insert Ladder, Results, Upcoming, Team Results, Team Upcoming, or Results + Upcoming
4. Configure block settings in the sidebar

## 🎯 Finding Competition ID

Competition ID is in the GameDay URL:

```
https://websites.mygameday.app/comp_info.cgi?c=0-1064-0-646414-0
                                                                  ^^^^^^^^^^^^^^^
                                                              Competition ID
```

## 🔌 Widget Configuration

### Competition Ladder Widget

- **Title**: Custom widget title
- **Competition**: Select specific competition or use default

### Upcoming Games Widget

- **Title**: Custom widget title
- **Competition**: Select competition
- **Number of games**: How many to display (1-20)

### Recent Results Widget

- **Title**: Custom widget title  
- **Competition**: Select competition
- **Number of results**: How many to display (1-20)

## ⚙️ Settings

### Cache Duration
- Default: 3600 seconds (1 hour)
- Minimum: 60 seconds
- Recommended: 1800-3600 for active competitions

### Competition Management
- Add multiple competitions
- Edit competition details
- Remove competitions
- One-click data scraping
- View data status

## 📁 File Structure

```
lacrosse-match-centre/
├── lacrosse-match-centre.php    # Main plugin file
├── includes/
│   ├── class-lmc-scraper.php    # Scraper class
│   ├── class-lmc-data.php       # Data handler
│   ├── class-lmc-admin.php      # Admin interface
│   ├── class-lmc-blocks.php     # Block registration/rendering
│   ├── class-lmc-ladder-widget.php
│   ├── class-lmc-upcoming-widget.php
│   ├── class-lmc-results-widget.php
│   └── class-lmc-cli.php        # WP-CLI commands
├── assets/
│   ├── style.css                # Widget styles
│   ├── blocks.js                # Block editor scripts
│   ├── blocks.css               # Block styles
│   └── blocks-frontend.js       # Frontend block helpers
├── data/                        # JSON data files (auto-created)
├── INSTALL.md
├── USER-GUIDE.md
├── BLOCKS-GUIDE.md
├── COMPETITION-DISCOVERY.md
├── API-REFERENCE.md
├── CHANGELOG.md
└── LICENSE
```

## 🔒 Security

- ✅ Input validation and sanitization
- ✅ Output escaping (XSS prevention)
- ✅ Nonce verification on AJAX requests
- ✅ Capability checks for admin functions
- ✅ .htaccess protection for data directory
- ✅ WordPress security best practices

## 🎨 Customization

### Custom CSS

Add to your theme or via **Appearance → Customize → Additional CSS**:

```css
/* Customize ladder colors */
.lmc-ladder-table thead {
    background: #your-color;
}

/* Change widget spacing */
.lmc-game {
    margin-bottom: 20px;
}
```

### Available CSS Classes

- `.lmc-ladder-widget` - Ladder widget container
- `.lmc-upcoming-widget` - Upcoming games widget
- `.lmc-results-widget` - Results widget
- `.lmc-ladder-table` - Ladder table
- `.lmc-game` - Individual game card
- `.lmc-result` - Individual result card

## 🔄 Updating Data

### Manual Update
1. Go to Settings → Match Centre
2. Click **Scrape Data**
3. Wait for completion

### Recommended Frequency
- **Active season**: After each round
- **Weekly**: For regular updates
- **As needed**: When results are available

### Automatic Updates (Optional)
See [USER-GUIDE.md](USER-GUIDE.md) for setting up WordPress Cron or server cron jobs.

## 🐛 Troubleshooting

### No Data Showing
- Scrape data from admin panel
- Clear cache
- Check data files exist in `/data/` folder

### Scraping Fails
- Verify Competition ID is correct
- Check GameDay website is accessible
- Increase PHP max_execution_time if needed

### Widgets Not Appearing
- Deactivate and reactivate plugin
- Check for PHP errors in debug.log
- Verify widget area is active

## 📚 Documentation

- **[INSTALL.md](INSTALL.md)** - Detailed installation instructions
- **[USER-GUIDE.md](USER-GUIDE.md)** - Complete user documentation
- **[BLOCKS-GUIDE.md](BLOCKS-GUIDE.md)** - Gutenberg blocks overview
- **[COMPETITION-DISCOVERY.md](COMPETITION-DISCOVERY.md)** - Competition ID discovery
- **[API-REFERENCE.md](API-REFERENCE.md)** - API documentation and WP-CLI commands
- **[CHANGELOG.md](CHANGELOG.md)** - Version history

## 🖥️ WP-CLI Commands

For advanced users and developers, WP-CLI commands are available:

```bash
# List all configured competitions
wp lmc list-competitions

# Discover competitions for an association (optional season)
wp lmc list-available-competitions 1064 --season=2024

# Get details about a specific competition
wp lmc get-competition <competition-id>
```

See [API-REFERENCE.md](API-REFERENCE.md) for complete WP-CLI documentation.

## 🛠️ Technical Details

### Data Flow
1. Admin clicks "Scrape Data"
2. PHP scraper fetches HTML from GameDay
3. DOMDocument parses HTML data
4. JSON files saved to `/data/` directory
5. Widgets read JSON files
6. Data cached with WordPress Transients
7. Display on frontend

### Caching Strategy
- First request: Read JSON → Cache → Display
- Subsequent requests: Read Cache → Display
- After cache expiry: Read JSON → Update Cache

### Data Files
- `ladder-{comp_id}.json` - Competition standings
- `fixtures-{comp_id}.json` - All fixtures
- `upcoming-{comp_id}.json` - Future games
- `results-{comp_id}.json` - Completed matches

## 🤝 Contributing

Contributions welcome! Please:
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📝 License

MIT License - see [LICENSE](LICENSE) file for details.

Free to use, modify, and distribute.

## 👏 Credits

- **Built for**: Williamstown Lacrosse Club
- **Inspired by**: [sportstg-api](https://github.com/AussieGuy0/sportstg-api) by AussieGuy0
- **Data Source**: GameDay (publicly available data)

## 📧 Support

For support:
1. Check documentation files
2. Review troubleshooting section
3. Check WordPress debug.log
4. Verify system requirements

## 🗺️ Roadmap

### Planned Features
- WordPress Cron automation
- REST API endpoints
- Shortcode support
- Player statistics
- Advanced filtering

## ⚠️ Disclaimer

This plugin scrapes publicly available data from GameDay. The plugin author is not affiliated with GameDay. Use responsibly and in accordance with GameDay's terms of service.

---

**Version**: 1.0.0  
**Author**: Michael Kindred  
**Last Updated**: February 10, 2026

---

Made with ❤️ for lacrosse communities
