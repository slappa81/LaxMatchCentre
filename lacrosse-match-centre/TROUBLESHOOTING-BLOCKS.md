# Block Troubleshooting Steps

## After updating the plugin:

1. **Clear all caches:**
   - WordPress cache (if using a caching plugin)
   - Browser cache (Ctrl+Shift+Delete)
   - Hard refresh (Ctrl+F5 or Cmd+Shift+R)

2. **Check debug.log for these messages:**
   ```
   LMC: Blocks class initialized
   LMC Blocks: Assets registered
   LMC Blocks: Registering blocks...
   LMC Blocks: Ladder block registered
   LMC Blocks: Upcoming block registered
   LMC Blocks: Results block registered
   LMC Blocks: All blocks registered successfully
   ```

3. **When you edit a page, check browser console (F12):**
   - Look for: "Lacrosse Match Centre blocks loading..."
   - Look for: "Lacrosse Match Centre blocks registered successfully!"
   - Check for any JavaScript errors

4. **In the block editor:**
   - Click the "+" button to add a block
   - Look for "Lacrosse Match Centre" category at the top
   - Search for "Lacrosse" or "Ladder" or "Upcoming" or "Results"

5. **If blocks still don't appear:**
   - Try viewing page source and search for "lacrosse-match-centre-blocks"
   - Check if the JavaScript file is loaded: `/wp-content/plugins/lacrosse-match-centre/assets/blocks.js`
   - Check browser Network tab (F12 > Network) to see if blocks.js loads with a 200 status

## Common Issues:

- **Permalink flush**: Go to Settings > Permalinks > Save Changes
- **Plugin conflicts**: Temporarily disable other plugins
- **Theme conflicts**: Try default WordPress theme temporarily
- **File permissions**: Ensure blocks.js and blocks.css are readable

## Manual Test:

Open browser console and type:
```javascript
wp.blocks.getBlockTypes().filter(b => b.name.includes('lacrosse'))
```

This should return an array with 3 blocks if they're registered correctly.
