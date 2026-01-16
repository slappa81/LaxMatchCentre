# Changelog

All notable changes to the Lacrosse Match Centre plugin will be documented in this file.

## [1.0.0] - 2026-01-16

### Added
- Initial release of Lacrosse Match Centre WordPress plugin
- Built-in PHP scraper for fetching data from SportsTG
- Competition ladder widget with standings display
- Upcoming games widget with fixture information
- Recent results widget with match scores
- Admin settings page for competition management
- One-click data scraping from WordPress admin
- WordPress Transient API caching system
- Multiple competition support
- Responsive design for mobile devices
- Dark mode support
- Security features (input sanitization, output escaping, nonce verification)
- Comprehensive documentation (README, INSTALL, USER-GUIDE)

### Features
- Scrape ladder standings from SportsTG
- Scrape all fixtures for a competition
- Automatic separation of upcoming games and completed results
- Configurable cache duration
- AJAX-powered admin interface
- Manual cache clearing
- Data file status display
- Widget customization options
- Competition selection per widget

### Technical
- PHP 7.0+ compatibility
- WordPress 5.0+ compatibility
- Uses WordPress HTTP API for requests
- DOMDocument and XPath for HTML parsing
- WordPress Widgets API integration
- WordPress Settings API integration
- File-based JSON storage
- .htaccess protection for data directory

### Documentation
- Complete README with features and usage
- Installation guide (INSTALL.md)
- User guide (USER-GUIDE.md)
- Architecture documentation
- Project summary
- MIT License

## [Future Versions]

### Planned Features
- WordPress Cron integration for automatic scraping
- REST API endpoint for data access
- Shortcode support
- Gutenberg block support
- Admin dashboard widgets
- Email notifications for scraping results
- Export data functionality
- Import/export settings
- Multi-language support
- Player statistics (if available from SportsTG)
- Team profiles
- Fixture calendar view
- Search functionality
- Filtering options

### Planned Improvements
- Enhanced error handling
- Better scraping reliability
- Performance optimizations
- Additional styling options
- More widget customization
- Batch operations for multiple competitions
- Scheduling UI for automatic updates
- Data validation and cleanup
- Backup and restore functionality

---

## Version History

- **1.0.0** (2026-01-16): Initial release

---

## Upgrade Notes

### From Pre-1.0 Versions
This is the first release. No upgrade path needed.

---

## Breaking Changes

### Version 1.0.0
None - Initial release

---

## Known Issues

### Version 1.0.0
- Scraping all rounds can take 30-60 seconds for long competitions
- Some SportsTG competitions may have different HTML structures requiring parser updates
- No automatic scheduling yet - must scrape manually or set up external cron

---

## Credits

- Inspired by [sportstg-api](https://github.com/AussieGuy0/sportstg-api) by AussieGuy0
- Built for Williamstown Lacrosse Club
- Developed with WordPress best practices
- Uses WordPress core APIs exclusively

---

For more information, see the [README.md](README.md) file.
