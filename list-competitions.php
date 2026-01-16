<?php
/**
 * Test script to list all available competitions from GameDay
 * 
 * This standalone script can be used to fetch competition IDs without WordPress
 * 
 * Usage: php list-competitions.php <association_id>
 * Example: php list-competitions.php 1064
 */

// Association ID from command line
$association_id = isset($argv[1]) ? $argv[1] : '1064';

echo "Fetching competitions for association ID: {$association_id}\n";
echo str_repeat("=", 80) . "\n\n";

// Fetch the page
$comp_id = "0-{$association_id}-0-0-0";
$url = "https://websites.mygameday.app/comp_info.cgi?c={$comp_id}&a=FIXTURE";

echo "Fetching from: {$url}\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$html = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    echo "Error: " . curl_error($ch) . "\n";
    curl_close($ch);
    exit(1);
}

curl_close($ch);

if ($http_code !== 200) {
    echo "Error: HTTP {$http_code}\n";
    exit(1);
}

if (empty($html)) {
    echo "Error: Empty response\n";
    exit(1);
}

// Parse HTML
libxml_use_internal_errors(true);
$dom = new DOMDocument();
$dom->loadHTML($html);
$xpath = new DOMXPath($dom);

// Find the competition dropdown
$select = $xpath->query("//select[@id='compselectbox']");

if ($select->length === 0) {
    // Try alternative selector
    $select = $xpath->query("//select[@name='client']");
}

if ($select->length === 0) {
    echo "Error: Competition dropdown not found in HTML\n";
    echo "This could mean:\n";
    echo "1. The association ID is invalid\n";
    echo "2. The GameDay website structure has changed\n";
    echo "3. There are no competitions for this association\n";
    exit(1);
}

// Get all options
$options = $xpath->query(".//option", $select->item(0));
$competitions = array();

foreach ($options as $option) {
    $value = $option->getAttribute('value');
    $name = trim($option->textContent);
    
    // Skip placeholder options
    if (empty($value) || 
        preg_match('/^0-\d+-0-0-0$/', $value) ||  // Skip placeholder like 0-1064-0-0-0
        $option->hasAttribute('disabled') ||
        stripos($name, 'Select a Competition') !== false) {
        continue;
    }
    
    // Clean up name (remove leading nbsp and whitespace)
    $name = preg_replace('/^[\s\xC2\xA0]+/', '', $name);
    
    $competitions[] = array(
        'id' => $value,
        'name' => $name
    );
}

if (empty($competitions)) {
    echo "No competitions found.\n";
    exit(0);
}

// Display results
echo "Found " . count($competitions) . " competitions:\n\n";

// Calculate column widths
$max_id_len = 20;
$max_name_len = 50;

foreach ($competitions as $comp) {
    $max_id_len = max($max_id_len, strlen($comp['id']));
    $max_name_len = max($max_name_len, strlen($comp['name']));
}

// Print header
$separator = "+" . str_repeat("-", $max_id_len + 2) . "+" . str_repeat("-", $max_name_len + 2) . "+";
echo $separator . "\n";
printf("| %-{$max_id_len}s | %-{$max_name_len}s |\n", "Competition ID", "Competition Name");
echo $separator . "\n";

// Print rows
foreach ($competitions as $comp) {
    printf("| %-{$max_id_len}s | %-{$max_name_len}s |\n", $comp['id'], $comp['name']);
}

echo $separator . "\n\n";

echo "To use a competition:\n";
echo "1. Copy the Competition ID\n";
echo "2. Go to WordPress admin → Settings → Match Centre\n";
echo "3. Add a new competition and paste the ID\n\n";
