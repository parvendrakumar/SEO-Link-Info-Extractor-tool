
<!DOCTYPE html>
<html>
<head>
    <title>Advanced URL & Link Info Extractor</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f0f0f0; }
        table { width: 100%; border-collapse: collapse; background: #fff; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #333; color: #fff; }
        tr:nth-child(even) { background: #f9f9f9; }
        input[type="text"] { width: 400px; padding: 10px; }
        button { padding: 10px 20px; margin-left: 5px; }
    </style>
</head>
<body>

<h2>Advanced Link Info Extractor</h2>

<form method="post">
    <input type="text" name="url" placeholder="Enter full URL (https://...)" required>
    <button type="submit">Fetch Info</button>
</form>

<?php
function get_http_status($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $status;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $url = trim($_POST["url"]);

    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        echo "<p><strong>Invalid URL.</strong></p>";
        exit;
    }

    $html = @file_get_contents($url);
    if (!$html) {
        echo "<p><strong>Could not fetch content.</strong></p>";
        exit;
    }

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML($html);
    libxml_clear_errors();

    $baseHost = parse_url($url, PHP_URL_HOST);
    $links = $dom->getElementsByTagName("a");

    echo "<h3>Found " . $links->length . " links:</h3>";
    echo "<table>
            <tr>
                <th>#</th>
                <th>Anchor Text</th>
                <th>Href</th>
                <th>Rel</th>
                <th>Target</th>
                <th>Type</th>
                <th>HTTP Status</th>
            </tr>";

    $index = 1;
    foreach ($links as $link) {
        $href = trim($link->getAttribute("href"));
        if (empty($href) || $href === "#") continue;

        // Make href absolute if relative
        if (!parse_url($href, PHP_URL_SCHEME)) {
            $href = rtrim($url, '/') . '/' . ltrim($href, '/');
        }

        $anchorText = trim($link->textContent);
        $rel = $link->getAttribute("rel");
        $target = $link->getAttribute("target");

        // Determine internal/external
        $linkHost = parse_url($href, PHP_URL_HOST);
        $type = ($linkHost == $baseHost || $linkHost == "") ? "Internal" : "External";

        // HTTP status (optional, slow)
        $status = get_http_status($href);

        echo "<tr>
                <td>{$index}</td>
                <td>" . htmlspecialchars($anchorText) . "</td>
                <td><a href='" . htmlspecialchars($href) . "' target='_blank'>{$href}</a></td>
                <td>{$rel}</td>
                <td>{$target}</td>
                <td>{$type}</td>
                <td>{$status}</td>
              </tr>";
        $index++;
    }

    echo "</table>";
}
?>

</body>
</html>
