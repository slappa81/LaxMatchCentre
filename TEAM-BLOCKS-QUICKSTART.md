# Quick Start: Team-Specific Blocks

## Setup Workflow

### Step 1: Scrape Competition Data
```
Admin → Settings → Match Centre → Scrape Data (for your competition)
```

### Step 2: Load Teams
```
Click "Load Teams" button → Teams populate in dropdown
```

### Step 3: Select Primary Team(s)
```
Choose one or more teams from dropdown → Save Settings
```

### Step 4: Add Block to Page
```
Block Editor → Search "Team Results" or "Team Upcoming Games" → Insert
```

### Step 5: Customize (Optional)
```
Block Settings → Change title, competition, team, or limit
```

## Block Comparison

| Feature | Competition Results | Team Results |
|---------|-------------------|--------------|
| **Shows** | All competition results | Only selected team's results |
| **Use Case** | General competition overview | Club/team-specific pages |
| **Filtering** | None | Filters by home OR away |
| **Display** | Standard match listing | Team-focused with home/away indicators |

| Feature | Competition Upcoming | Team Upcoming |
|---------|---------------------|---------------|
| **Shows** | All competition games | Only selected team's games |
| **Use Case** | Competition calendar | Club/team fixtures |
| **Filtering** | None | Filters by home OR away |
| **Display** | Standard game listing | Team-focused with opponent emphasis |

## Example Use Cases

### Club Website
- Use **Team Results** on homepage to show recent performance
- Use **Team Upcoming** on fixtures page to show next games
- Use **Competition Ladder** to show league standings

### Multi-Team Site
- Create separate pages for each team
- Use team name override in blocks to display different teams
- Or select multiple primary teams to render sections per team automatically

### News/Blog Posts
- Embed **Team Results** in match reports
- Embed **Team Upcoming** in preview articles
- Override defaults per post as needed

## Tips

✅ **DO:**
- Scrape data before setting up teams
- Refresh teams if you add new fixtures
- Use descriptive block titles
- Test blocks after setup

❌ **DON'T:**
- Forget to save settings after selecting primary team
- Use team blocks without scraping data first
- Hard-code team names (use primary team setting)

## Troubleshooting

**Teams dropdown is empty?**
- Ensure you've scraped competition data
- Click "Load Teams" or "Refresh Teams" button
- Check that fixtures contain team names

**Block shows "No data"?**
- Verify primary team is selected in settings
- Ensure data is scraped for the competition
- Check team name spelling matches exactly

**Want to show different team?**
- Either: Change primary team in settings (affects all blocks)
- Or: Use team name override in individual blocks

**Multiple teams selected?**
- Each team gets its own section in Team Results/Upcoming blocks
- Carousel controls are scoped per team section

## Quick Reference: All Blocks

1. **Ladder** - Competition standings table
2. **Results** - All recent competition results  
3. **Upcoming** - All upcoming competition games
4. **Team Results** ⭐ NEW - Your team's recent results
5. **Team Upcoming** ⭐ NEW - Your team's upcoming games
