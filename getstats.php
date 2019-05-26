<?php

$cliArgs = getopt('f:');
$helpText = 'Usage: php '.basename(__FILE__).' -f {filename}';

/**
 * printText Just echo text and exit.
 *
 * @param string $message
 * @param int    $error
 */
function printText(string $message, int $error = 0)
{
    print_r($message."\n");
    exit($error);
}

/**
 * formatBytes Make bytes into human readable units.
 *
 * @param int $size
 * @param int $precision
 */
function formatBytes(int $size, int $precision = 2)
{
    $base = log($size, 1024);
    $suffixes = array('', 'KiB', 'MiB', 'GiB', 'TiB');

    return round(pow(1024, $base - floor($base)), $precision).$suffixes[floor($base)];
}

/**
 * percentage.
 *
 * @param int $num
 * @param int $outof
 *
 * @return int
 */
function percentage(int $num, int $outof)
{
    $percent = ($num / $outof) * 100;

    return number_format($percent, 10, '.', '');
}

/**
 * calcData.
 *
 * @param array $logArray
 */
function getInfo($logArray)
{
    // Initialize array with zero value
    $tally = array(
        'allcount' => 0,
        'data' => 0,
        'reqres' => array(),
        'remhost' => array(),
        'reqone' => 0,
        'reqtwo' => 0,
        'reqthree' => 0,
        'reqfour' => 0,
        'reqfive' => 0,
    );

    foreach ($logArray as $row) {
        // explode row into an array
        $split = explode(' ', $row);

        // Count rows
        $tally['allcount'] = $tally['allcount'] + 1;
        // Data tally
        $tally['data'] = $tally['data'] + $split[count($split) - 1];
        // Count requests to each resource
        ++$tally['reqres'][$split[count($split) - 4]];
        // Count hosts making requests
        ++$tally['remhost'][$split['0']];
        // Tally server response
        switch ($split[count($split) - 2]) {
            case preg_match('/1../', $split[count($split) - 2]) ? true : false:
                ++$tally['reqone'];
                break;
            case preg_match('/2../', $split[count($split) - 2]) ? true : false:
                ++$tally['reqtwo'];
                break;
            case preg_match('/3../', $split[count($split) - 2]) ? true : false:
                ++$tally['reqthree'];
                break;
            case preg_match('/4../', $split[count($split) - 2]) ? true : false:
                ++$tally['reqfour'];
                break;
            case preg_match('/5../', $split[count($split) - 2]) ? true : false:
                ++$tally['reqfive'];
                break;
        }
    }

    return $tally;
}

/*
 * Start main processing of script.
 */
if (empty($cliArgs)) {
    printText($helpText, 1);
}

if (array_key_exists('f', $cliArgs)) {
    if (file_exists($cliArgs['f'])) {
        $logArray = file($cliArgs['f']);
    } else {
        printText('File Not Found!', 1);
    }
} else {
    printText($helpText, 1);
}

if (empty($logArray)) {
    printText('Empty file provided!', 1);
}

$rawStats = getInfo($logArray);
$requestedResource = array_search(max($rawStats['reqres']), $rawStats['reqres']);
$mostReqHost = array_search(max($rawStats['remhost']), $rawStats['remhost']);

$finalText = 'Total Requests: '.$rawStats['allcount']."\n".
    'Total Data Transmitted: '.formatBytes($rawStats['data'], 1)."\n".
    'Most requested resource: '.$requestedResource."\n".
    'Total requests for '.$requestedResource.': '.max($rawStats['reqres'])."\n".
    'Percentage of requests for '.$requestedResource.': '.percentage(max($rawStats['reqres']), $rawStats['allcount'])."\n".
    'Remote host with the most requests: '.$mostReqHost."\n".
    'Total requests from '.$mostReqHost.': '.max($rawStats['remhost'])."\n".
    'Percentage of requests from '.$mostReqHost.': '.percentage(max($rawStats['remhost']), $rawStats['allcount'])."\n".
    'Percentage of 1xx requests: '.percentage($rawStats['reqone'], $rawStats['allcount'])."\n".
    'Percentage of 2xx requests: '.percentage($rawStats['reqtwo'], $rawStats['allcount'])."\n".
    'Percentage of 3xx requests: '.percentage($rawStats['reqthree'], $rawStats['allcount'])."\n".
    'Percentage of 4xx requests: '.percentage($rawStats['reqfour'], $rawStats['allcount'])."\n".
    'Percentage of 5xx requests: '.percentage($rawStats['reqfive'], $rawStats['allcount'])."\n";

printText($finalText, 0);
