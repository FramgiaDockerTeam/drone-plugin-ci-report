<?php

$environments = [
    'DRONE' => getenv('DRONE'),
    'DRONE_REPO' => getenv('DRONE_REPO'),
    'DRONE_BRANCH' => getenv('DRONE_BRANCH'),
    'DRONE_COMMIT' => getenv('DRONE_COMMIT'),
    'DRONE_DIR' => getenv('DRONE_DIR'),
    'DRONE_BUILD_NUMBER' => getenv('DRONE_BUILD_NUMBER'),
    'DRONE_PULL_REQUEST' => getenv('DRONE_PULL_REQUEST'),
    'DRONE_JOB_NUMBER' => getenv('DRONE_JOB_NUMBER'),
    'DRONE_TAG' => getenv('DRONE_TAG'),
    'CI' => getenv('CI'),
    'CI_NAME' => getenv('CI_NAME'),
    'CI_REPO' => getenv('CI_REPO'),
    'CI_BRANCH' => getenv('CI_BRANCH'),
    'CI_COMMIT' => getenv('CI_COMMIT'),
    'CI_BUILD_NUMBER' => getenv('CI_BUILD_NUMBER'),
    'CI_PULL_REQUEST' => getenv('CI_PULL_REQUEST'),
    'CI_JOB_NUMBER' => getenv('CI_JOB_NUMBER'),
    'CI_BUILD_DIR' => getenv('CI_BUILD_DIR'),
    'CI_BUILD_URL' => getenv('CI_BUILD_URL'),
    'CI_TAG' => getenv('CI_TAG')
];

$createReportResult = apiCall('http://33f9c70b.ngrok.io/api/queues/test', true, $environments, ['Content-Type: application/json']);
die;

$arguments = [
    'workspace' => [
        'root' => getenv('DRONE_DIR')
    ],
    'repo' => [
        'name' => getenv('DRONE_REPO')
    ],
    'build' => [
        'number' => getenv('DRONE_BUILD_NUMBER'),
        'commit' => getenv('DRONE_COMMIT'),
        'branch' => getenv('DRONE_BRANCH'),
        'link_url' => getenv('DRONE_PULL_REQUEST')
    ],
    'job' => [
        'number' => getenv('DRONE_JOB_NUMBER')
    ]
];

var_dump($arguments);
die;

$arguments = isset($argv[2]) ? $argv[2] : '[]';
$arguments = json_decode($arguments, true);

if (isset($arguments['workspace']['keys'])) {
    unset($arguments['workspace']['keys']);
}

if (isset($arguments['workspace']['netrc'])) {
    unset($arguments['workspace']['netrc']);
}

$baseApiUrl = 'http://ci-reports.framgia.vn/api/queues';

$retryTimes = 10;
$sleepSeconds = 5;

if (!empty($baseApiUrl)) {
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
            echo "Api create report failed (" . ($i + 1) . " times)\r\n";
        }

        sleep($sleepSeconds);
    }

    // Check queue_id status
    if (!empty($queueId)) {
        echo "Queue ID: $queueId\r\nNow tracking status...\r\n";

        for ($i = 0; $i < $retryTimes; $i++) {
            sleep($sleepSeconds);

            $checkQueueResult = apiCall($baseApiUrl . '/' . $queueId, false, [], ["token: $token"]);
            $result = json_decode($checkQueueResult, true);

            if (!empty($result) && isset($result['errorCode']) && !$result['errorCode']) {
                echo "{$result['data']['status']}\r\n";

                if (in_array($result['data']['status'], ['success', 'error'])) {
                    break;
                }
            } else {
                echo "Api check queue status failed (" . ($i + 1) . " times)\r\n";
            }
        }
    }
} else {
    echo 'base_api_url is required.';
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
