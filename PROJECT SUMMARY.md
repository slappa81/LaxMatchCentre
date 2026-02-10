# LaxMatchCentre
Lacrosse Match Centre

This project provides a complete WordPress plugin for displaying lacrosse league statistics, with a built-in scraper that fetches data directly from GameDay.

## Problem

The Williamstown Lacrosse Club needed a way to:
1. Automatically fetch game information and statistics from GameDay
2. Display ladders, upcoming games, and results on their WordPress website
3. Keep the data updated without manual file transfers or complex setups

## Solution Overview

An all-in-one WordPress plugin:

### Integrated PHP Scraper
- Fetches competition data directly from GameDay
- Uses WordPress HTTP API for reliable connections
- Parses HTML using native PHP DOMDocument and XPath
- No external dependencies or Node.js required
- One-click scraping from WordPress admin interface

### WordPress Plugin Features
- Reads and caches scraped data
- Provides widgets and Gutenberg blocks for displaying different aspects of the data
- Includes admin interface for configuration and scraping
- Uses caching for performance
- Supports multiple competitions
- Supports team-specific views with multi-team selection
- Includes competition discovery via admin, WP-CLI, and script

## Key Features
✅ **Integrated Scraper** - Built-in PHP scraper, no external tools needed  
✅ **One-Click Updates** - Scrape data with a button click in WordPress admin  
✅ **Automated Data Collection** - Scraper fetches data from GameDay, this must be contained within the Wordpress Plugin 
✅ **Multiple Competitions** - Support for multiple competitions   
✅ **Ladder Display** - Competition standings with points, wins, losses  
✅ **Upcoming Games** - Shows future matches with dates and venues  
✅ **Results Display** - Shows completed matches with scores  
✅ **Blocks + Widgets** - Gutenberg blocks alongside classic widgets  
✅ **Team Blocks** - Team Results and Team Upcoming with multi-team sections  
✅ **Competition Discovery** - Find GameDay competition IDs automatically  
✅ **Responsive Design** - Works on desktop, tablet, and mobile  
✅ **Easy Configuration** - Admin panel for settings, including manual data collection and scraping interval, and competition selection.  
✅ **Performance Optimized** - Built-in caching system  
✅ **Secure** - Follows WordPress security best practices  
✅ **Well Documented** - Comprehensive guides and examples 

## Technical Stack

**Plugin:**
- PHP 7.0+
- WordPress 5.0+
- WordPress HTTP API
- DOMDocument & XPath for HTML parsing
- CSS3
- JavaScript (Gutenberg blocks + frontend block helpers)
- WordPress Widgets API
- WordPress Block API
- WordPress Transient API (caching)
- WordPress Settings API
- WP-CLI (optional)

## Maintenance

### Regular Tasks
- **Update current round** - Update in plugin settings as season progresses
- **Fetch data** - Click "Scrape" button when you need fresh data
- **Cache management** - Clear cache if needed via admin panel

### Automation Options
- **WordPress Cron** - Can be implemented for automatic updates (future enhancement)
- **Server Cron with WP-CLI** - Trigger scraping via command line
## Benefits

### For Administrators
- Easy setup and configuration
- Automated data updates
- No manual data entry required
- Clear admin interface

### For Website Visitors
- Always current information
- Clean, professional display
- Mobile-friendly interface
- Fast loading times (caching)

### For Developers
- Well-documented code
- Modular structure
- Easy to extend
- Standard WordPress practices

## Use Cases

1. **Sports Club Websites** - Display your team's competition data
2. **League Websites** - Show standings and fixtures for entire league
3. **Fan Sites** - Keep supporters informed of latest results
4. **Multi-Sport Sites** - Can work with any sport on GameDay

## Security

✅ Input validation and sanitization  
✅ Output escaping (XSS prevention)  
✅ Nonce verification on forms  
✅ Capability checks for admin functions  
✅ Error logging (no sensitive data exposure)  
✅ Secure file operations  
✅ No known vulnerabilities in dependencies  

## Performance

- **Caching** - WordPress Transient API (default 1 hour)
- **File-based storage** - No database overhead
- **Minimal queries** - Efficient data loading
- **Lazy loading** - Data loaded only when needed
- **Optimized CSS** - Minimal styling overhead

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Accessibility

- Semantic HTML
- Proper heading hierarchy
- Keyboard navigable
- Screen reader friendly
- WCAG 2.1 compliant

## Documentation

### User Documentation
- **README.md** - Complete guide
- **INSTALL.md** - Quick installation
- **BLOCKS-GUIDE.md** - Gutenberg blocks overview
- **TESTING.md** - Testing procedures
- **DEPLOYMENT.md** - Various deployment scenarios

### Technical Documentation
- **FEATURES.md** - Feature details
- **COMPETITION-DISCOVERY.md** - Competition ID discovery
- **TEAM-SELECTION-FEATURE.md** - Team selection and team blocks
- Inline code comments
- Example data files
- Setup helper scripts

## Testing

### Validation Performed
✅ JavaScript syntax validation  
✅ PHP syntax validation (all files)  
✅ JSON structure validation  
✅ CSS validation  
✅ Security vulnerability scanning  
✅ Code review completed  
✅ WordPress coding standards  

### Test Coverage
- Scraper functionality
- Data file generation
- WordPress plugin activation
- Widget display
- Admin interface
- Cache system
- Responsive design
- Error handling

- **Built for:** Williamstown Lacrosse Club
- **Author:** Michael Kindred - michael.kindred@outlook.com
- **Inspired by:** [sportstg-api](https://github.com/AussieGuy0/sportstg-api) by AussieGuy0
- **License:** MIT (free to use, modify, distribute)