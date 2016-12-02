#!/usr/local/bin/php
<?php

if (file_exists('.git')) {
    shell_exec('chmod -R o+rx .git/');
}

$baseApiUrl = 'http://ci-reports.framgia.vn/api/queues';
$resultFile = 'framgia_ci_result.tmp';
$repoArr = explode('/', getenv('DRONE_REPO'));

$arguments = [
    'workspace' => [
        'path' => getenv('DRONE_DIR')
    ],
    'repo' => [
        'owner' => $repoArr[0],
        'name' => $repoArr[1],
        'full_name' => getenv('DRONE_REPO')
    ],
    'build' => [
        'number' => getenv('DRONE_BUILD_NUMBER'),
        'commit' => getenv('DRONE_COMMIT'),
        'branch' => getenv('DRONE_BRANCH'),
        'pull_request_number' => getenv('DRONE_PULL_REQUEST')
    ],
    'job' => [
        'number' => getenv('DRONE_JOB_NUMBER')
    ]
];

$retryTimes = 10;
$sleepSeconds = 5;

// Import report and get queue_id
$queueId = null;
$token = null;

for ($i = 0; $i < $retryTimes; $i++) {
    $createReportResult = apiCall($baseApiUrl, true, $arguments, ['Content-Type: application/json']);

    $queueResult = json_decode($createReportResult, true);

    if (!empty($queueResult) && isset($queueResult['errorCode']) && !$queueResult['errorCode']) {
        $queueId = $queueResult['data']['queueId'];
        $token = $queueResult['data']['token'];
        break;
    } else {
        echo "Api create report failed (" . ($i + 1) . " times)\n";
    }

    sleep($sleepSeconds);
}

// Check queue_id status
if (!empty($queueId)) {
    echo "Queue ID: $queueId\nNow tracking status...\n";

    for ($i = 0; $i < $retryTimes; $i++) {
        sleep($sleepSeconds);

        $checkQueueResult = apiCall($baseApiUrl . '/' . $queueId, false, [], ["token: $token"]);
        $result = json_decode($checkQueueResult, true);

        if (!empty($result) && isset($result['errorCode']) && !$result['errorCode']) {
            echo "{$result['data']['status']}\n";

            if (in_array($result['data']['status'], ['success', 'error'])) {
                break;
            }
        } else {
            echo "Api check queue status failed (" . ($i + 1) . " times)\n";
        }
    }
}

if (file_exists($resultFile)) {
    $file = fopen($resultFile, 'r');
    $result = fread($file, filesize($resultFile));
    unlink($resultFile);
}

function apiCall($url, $isPost = false, $params = [], $headers = [])
{
    try {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($isPost) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
        $result = null;
    }

    return $result;
}
