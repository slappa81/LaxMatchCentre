# Competition Discovery Issue - Root Cause Found

## Problem Summary
The "Discover Competitions" feature returns "No competitions found" even though the HTTP request succeeds (200 OK, 126KB response).

## Root Cause
The HTML page being scraped **does not contain a competition dropdown**. The page structure is:

```html
<!-- Season selector (works) -->
<select name="seasonID" id="id_seasonID">
    <option value="...&seasonID=6045050">2026</option>
    <option value="...&seasonID=6042193">2025</option>
</select>

<!-- Competition selector (EMPTY - only placeholder) -->
<select id="comp-anchors" class="comp-select-drop">
    <option>Jump to Competition</option>
    <!-- No actual competition options here! -->
</select>

<!-- Competition content area (empty in initial HTML) -->
<div class="comps"></div>
```

The competition dropdown that the scraper is looking for (`<select name="client" id="compselectbox">`) **does not exist on this page**.

## Why This Happens
The page at `comp_info.cgi?c=0-1064-0-0-0&a=FIXTURE` is a **JavaScript-rendered page**. The competition list is loaded dynamically after the initial HTML loads, likely via AJAX or JavaScript. PHP cURL only retrieves the initial HTML, not JavaScript-generated content.

## Solutions

### Option 1: Extract from Existing Data (RECOMMENDED)
Since you're already successfully scraping fixture data, extract competition names from your existing JSON files:

```php
// Scan data/*.json files
// Extract competition_id and competition_name fields
// Display these as "discovered" competitions
```

**Pros:**
- Works immediately
- Shows what you've actually configured
- No web scraping needed

**Cons:**
- Only shows competitions you've already set up

###Option 2: Find the Real API Endpoint
Use browser Developer Tools (Network tab) to find the actual AJAX endpoint GameDay uses to load competitions, then call that endpoint instead.

**Pros:**
- Would discover all available competitions
- Original intended functionality

**Cons:**
- Requires reverse-engineering GameDay's API
- API may change or require authentication
- More complex implementation

### Option 3: Manual Configuration Only
Remove the discovery feature entirely and require manual competition configuration.

**Pros:**
- Simplest approach
- You already know your competition IDs

**Cons:**
- No discovery convenience feature

## Recommendation
Implement **Option 1** - it provides useful functionality (showing configured competitions) without the complexity of scraping JavaScript-rendered content.

The discovery feature can show:
- Competitions you've already configured in settings
- Competitions with existing data files
- Allow quick copying of competition IDs/names

This is more useful than trying to scrape a dynamic page that wasn't designed for server-side scraping.
