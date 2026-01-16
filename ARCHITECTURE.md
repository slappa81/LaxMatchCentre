# Architecture Diagram

## System Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                       SportsTG Website                          │
│                  (Public Competition Data)                       │
└───────────────────────────┬─────────────────────────────────────┘
                            │ HTTPS Request
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│                  WordPress Plugin with                          │
│                   Built-in PHP Scraper                          │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  LMC_Scraper Class                                       │  │
│  │  • Fetch ladder data via WordPress HTTP API             │  │
│  │  • Fetch fixtures data                                   │  │
│  │  • Parse HTML using DOMDocument & XPath                  │  │
│  │  • Separate upcoming vs completed games                  │  │
│  │  • Generate JSON files                                   │  │
│  └──────────────────────────────────────────────────────────┘  │
└───────────────────────────┬─────────────────────────────────────┘
                            │ Write JSON
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│                     Data Directory                              │
│  • ladder-{comp_id}.json      - Competition standings           │
│  • fixtures-{comp_id}.json    - All season fixtures             │
│  • upcoming-{comp_id}.json    - Future games                    │
│  • results-{comp_id}.json     - Completed games                 │
└───────────────────────────┬─────────────────────────────────────┘
                            │ Read JSON
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│                  WordPress Installation                         │
│  wp-content/plugins/lacrosse-match-centre/                      │
│                                                                  │
│  ┌────────────────────────────────────────────────────────┐    │
│  │  Main Plugin (lacrosse-match-centre.php)              │    │
│  │  • Registers widgets                                   │    │
│  │  • Loads dependencies                                  │    │
│  │  • Enqueues styles                                     │    │
│  └────────────────────────────────────────────────────────┘    │
│                                                                  │
│  ┌────────────────────────────────────────────────────────┐    │
│  │  Data Handler (class-lmc-data.php)                     │    │
│  │  • Read JSON files                                     │    │
│  │  • Cache with Transient API                            │    │
│  │  • Return formatted data                               │    │
│  └────────────────────────────────────────────────────────┘    │
│                            ↓                                     │
│  ┌────────────────────────────────────────────────────────┐    │
│  │  WordPress Transient Cache                             │    │
│  │  • lmc_ladder_data                                     │    │
│  │  • lmc_upcoming_games                                  │    │
│  │  • lmc_results                                         │    │
│  │  • Expires after configured duration                   │    │
│  └────────────────────────────────────────────────────────┘    │
│                            ↓                                     │
│  ┌────────────────────────────────────────────────────────┐    │
│  │  Widgets                                               │    │
│  │  ┌──────────────────────────────────────────────┐     │    │
│  │  │  Ladder Widget                               │     │    │
│  │  │  • Display standings table                   │     │    │
│  │  └──────────────────────────────────────────────┘     │    │
│  │  ┌──────────────────────────────────────────────┐     │    │
│  │  │  Upcoming Games Widget                       │     │    │
│  │  │  • Display future matches                    │     │    │
│  │  └──────────────────────────────────────────────┘     │    │
│  │  ┌──────────────────────────────────────────────┐     │    │
│  │  │  Results Widget                              │     │    │
│  │  │  • Display completed matches                 │     │    │
│  │  └──────────────────────────────────────────────┘     │    │
│  └────────────────────────────────────────────────────────┘    │
│                            ↓                                     │
│  ┌────────────────────────────────────────────────────────┐    │
│  │  Admin Interface (class-lmc-admin.php)                 │    │
│  │  • Settings page                                       │    │
│  │  • Configuration options                               │    │
│  │  • Scraper controls (one-click scraping)               │    │
│  │  • Cache management                                    │    │
│  │  • Data status display                                 │    │
│  └────────────────────────────────────────────────────────┘    │
└───────────────────────────┬─────────────────────────────────────┘
                            │ Render HTML
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│                    WordPress Frontend                           │
│  • Pages with widgets in sidebar/footer                         │
│  • Styled with CSS                                              │
│  • Responsive design                                            │
└───────────────────────────┬─────────────────────────────────────┘
                            │ HTTP Response
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│                      Website Visitors                           │
│  • View ladder standings                                        │
│  • See upcoming games                                           │
│  • Check recent results                                         │
└─────────────────────────────────────────────────────────────────┘
```

## Data Flow Details

### 1. Data Collection Phase
```
SportsTG → PHP Scraper (via WordPress HTTP API) → JSON Files
```
- **Trigger**: Admin clicks "Scrape" button in WordPress admin
- **Method**: AJAX request to WordPress backend
- **Output**: 4 JSON files per competition (ladder, fixtures, upcoming, results)
- **Location**: `wp-content/plugins/lacrosse-match-centre/data/` directory

### 2. Data Presentation Phase
```
Plugin → Read JSON → Cache → Widgets → Frontend
```
- **Performance**: Cached for 1 hour (default, configurable)
- **Flexibility**: 3 different widget types
- **Configuration**: Admin settings panel

## Component Interactions

### PHP Scraper Components
```
LMC_Scraper class
  ├─ get_ladder($comp_id, $round_num)
  │   └─ WordPress HTTP API → DOMDocument parsing
  ├─ get_round_fixtures($comp_id, $round_num, $pool_num)
  │   └─ WordPress HTTP API → DOMDocument parsing
  ├─ fetch_all_fixtures($comp_id, $comp_name, $current_round, $max_rounds)
  │   └─ Loop through rounds → get_round_fixtures()
  ├─ get_upcoming_games($fixtures_data)
  └─ get_recent_results($fixtures_data)
```

### WordPress Plugin Components
```
lacrosse-match-centre.php (Main)
  ├─ Loads: class-lmc-scraper.php
  ├─ Loads: class-lmc-data.php
  ├─ Loads: class-lmc-admin.php
  ├─ Loads: class-lmc-ladder-widget.php
  ├─ Loads: class-lmc-upcoming-widget.php
  └─ Loads: class-lmc-results-widget.php

class-lmc-scraper.php (Scraping Layer)
  ├─ get_ladder()
  ├─ get_round_fixtures()
  ├─ fetch_all_fixtures()
  ├─ get_upcoming_games()
  └─ get_recent_results()

class-lmc-data.php (Data Layer)
  ├─ get_ladder()
  ├─ get_upcoming_games()
  ├─ get_results()
  └─ clear_cache()

class-lmc-admin.php (Admin Layer)
  ├─ add_admin_menu()
  ├─ register_settings()
  ├─ ajax_scrape_competition()
  └─ render_settings_page()

Widget Classes (Presentation Layer)
  ├─ widget() - Frontend display
  ├─ form() - Widget settings
  └─ update() - Save settings
```

## Technology Stack

### Backend (Scraper)
- **Language**: PHP 7.0+
- **Dependencies**: WordPress HTTP API, DOMDocument, DOMXPath
- **Storage**: File system (JSON)
- **Configuration**: WordPress options

### Frontend (WordPress)
- **Language**: PHP 7.0+
- **Framework**: WordPress 5.0+
- **APIs**: Widgets API, Settings API, Transient API, HTTP API
- **Styling**: CSS3
- **Storage**: Transient cache + file system

## Security Layers

```
Input Layer
  ↓ Sanitization
Processing Layer
  ↓ Validation
Storage Layer
  ↓ Secure file operations
Cache Layer
  ↓ WordPress Transient API
Output Layer
  ↓ Escaping (esc_html, esc_attr)
Display Layer
```

## Performance Optimization

### Caching Strategy
```
First Request:
  Read JSON → Parse → Cache → Display (slower)

Subsequent Requests (within cache period):
  Read Cache → Display (fast)

After Cache Expiry:
  Read JSON → Parse → Update Cache → Display
```

### Cache Duration
- **Default**: 3600 seconds (1 hour)
- **Configurable**: Admin can adjust
- **Per-widget**: Each widget uses cached data

## Deployment Scenarios

### Scenario 1: Simple WordPress Installation
```
WordPress Admin → Click "Scrape" → Data Fetched → Widgets Display
```

### Scenario 2: VPS/Cloud with Cron
```
Server Cron Job → WP-CLI trigger scrape → Automatic updates
```

### Scenario 3: Managed WordPress Hosting
```
WordPress Admin → Manual "Scrape" → Or WordPress Cron (future)
```

## Extensibility Points

### Future Enhancements Can Add:
1. **WordPress Cron Integration** - Automatic scheduled scraping
2. **REST API** - Expose data via WordPress REST API
3. **Shortcodes** - Alternative to widgets
4. **Block Editor** - Gutenberg blocks
5. **Admin Dashboard** - Visual analytics
6. **Multi-league Support** - Enhanced for multiple sports

## Error Handling Flow

```
Operation Attempt
  ↓
Success? ───Yes───→ Return Data
  ↓
  No
  ↓
Log Error (error_log)
  ↓
Return False/Empty
  ↓
Display Error Message (Frontend)
```

## Monitoring Points

### For Administrators
- **Admin Panel**: Data file status
- **Error Logs**: WordPress debug.log
- **Cache Status**: Transient expiry times

### For Developers
- **Scraper Output**: Console messages
- **File Timestamps**: Last modified times
- **Server Logs**: Web server logs
