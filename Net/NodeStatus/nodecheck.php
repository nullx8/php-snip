<?php

function load_env($file)
{
    if (!file_exists($file)) {
        die(json_encode(["error" => ".env not found"]));
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $env = [];
    $parsingHosts = false;
    $hosts = [];

    foreach ($lines as $line) {
        $line = trim($line);

        // Skip comments
        if ($line === "" || strpos($line, '#') === 0) {
            continue;
        }

        // HOSTS array start?
        if (preg_match('/^HOSTS=\(\s*$/', $line)) {
            $parsingHosts = true;
            continue;
        }

        // HOSTS array end?
        if ($parsingHosts && $line === ")") {
            $parsingHosts = false;
            $env['HOSTS'] = $hosts;
            $hosts = [];
            continue;
        }

        // Lines inside the HOSTS array
        if ($parsingHosts) {
            if (preg_match('/"([^"]+)"/', $line, $m)) {
                $hosts[] = trim($m[1]);
            }
            continue;
        }

        // Normal KEY="VALUE" variables
        if (preg_match('/^([^=]+)="?(.*?)"?$/', $line, $m)) {
            $key = trim($m[1]);
            $value = trim($m[2]);
            $env[$key] = $value;
        }
    }

    return $env;
}

// -------------------------------------------------------------

$env = load_env(__DIR__ . "/.env");

// Validate required entries
if (!isset($env['OUTFILE_PREFIX'])) {
    die(json_encode(["error" => "OUTFILE_PREFIX missing in .env"]));
}

if (!isset($env['HOSTS']) || empty($env['HOSTS'])) {
    die(json_encode(["error" => "HOSTS missing or empty in .env"]));
}

$prefix = $env['OUTFILE_PREFIX'];

// Parameter: ?host=name
$requestedHost = isset($_GET['host']) ? trim($_GET['host']) : null;

function get_filename($prefix, $host)
{
    $safeHost = str_replace(['/', ':'], '_', $host);
    return $prefix . $safeHost . ".json";
}

header("Content-Type: application/json");

// Single host request
if ($requestedHost) {
    if (!in_array($requestedHost, $env['HOSTS'])) {
        http_response_code(404);
        die(json_encode(["error" => "Unknown host", "host" => $requestedHost]));
    }

    $file = get_filename($prefix, $requestedHost);

    if (!file_exists($file)) {
        http_response_code(404);
        die(json_encode(["error" => "No data file found", "file" => $file]));
    }

    echo file_get_contents($file);
    exit;
}

// All hosts
$result = [];
foreach ($env['HOSTS'] as $host) {
    $file = get_filename($prefix, $host);

    if (file_exists($file)) {
        $result[$host] = json_decode(file_get_contents($file), true);
    } else {
        $result[$host] = null;
    }
}

echo json_encode($result, JSON_PRETTY_PRINT);
