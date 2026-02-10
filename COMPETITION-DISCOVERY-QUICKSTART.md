# Quick Start: Competition Discovery

## 🎯 Quick Answer

**Yes!** You can now list all available competition IDs from GameDay with friendly names.

## 🚀 Easiest Method: WordPress Admin

1. Go to **Settings → Match Centre**
2. Look for the **"🔍 Discover Competitions"** box
3. Enter your Association ID (e.g., `1064`)
4. Click **"Load Seasons"** and pick a season
5. Click **"Discover Competitions"**
6. Click **"Use This"** next to any competition to add it
7. Click **"Save Settings"**

![Competition Discovery](https://via.placeholder.com/800x400?text=Competition+Discovery+UI)

## 💻 Alternative: Command Line

### Using WP-CLI
```bash
wp lmc list-available-competitions 1064
```

### Using Standalone PHP Script
```bash
php list-competitions.php 1064
```

## 📋 Sample Output

```
+-------------------------+----------------------------------+
| Competition ID          | Competition Name                 |
+-------------------------+----------------------------------+
| 0-1064-0-646414-0       | Men's State League               |
| 0-1064-0-646422-0       | Women's State League             |
| 0-1064-0-646413-0       | U12 Boys - East                  |
| 0-1064-0-646410-0       | U12 Boys - West                  |
| 0-1064-0-646424-0       | U12 Girls                        |
| 0-1064-0-652040-0       | U14 Boys                         |
| 0-1064-0-652041-0       | U14 Girls                        |
| 0-1064-0-646423-0       | U16 Boys                         |
| 0-1064-0-646407-0       | U17 Girls                        |
| 0-1064-0-646406-0       | U18 Boys                         |
| 0-1064-0-646409-0       | Men's Championship League        |
| 0-1064-0-646411-0       | Women's Championship League      |
+-------------------------+----------------------------------+
```

## 🔑 Finding Your Association ID

Look in any GameDay URL:
```
https://websites.mygameday.app/comp_info.cgi?c=0-1064-0-646414-0
                                                  ^^^^
                                            This is your
                                          Association ID
```

Common examples:
- **1064** = Lacrosse Victoria
- **YOUR_ID** = Check your GameDay URL

## ✨ What This Does

- Automatically fetches ALL competitions from GameDay
- Shows competition IDs and friendly names
- One-click to add competitions (admin interface)
- No manual URL hunting needed
- Always up-to-date with current season

## 📚 More Information

See [COMPETITION-DISCOVERY.md](COMPETITION-DISCOVERY.md) for detailed documentation.
