<?php

$arguments = isset($argv[2]) ? $argv[2] : '[]';
$arguments = json_decode($arguments, true);

if (isset($arguments['workspace']['keys'])) {
    unset($arguments['workspace']['keys']);
}

if (isset($arguments['workspace']['netrc'])) {
    unset($arguments['workspace']['netrc']);
}

$vargs = $arguments['vargs'];
$baseApiUrl = isset($vargs['base_api_url']) ? $vargs['base_api_url'] : 'http://ci-reports.framgia.vn/api/queues';

$retryTimes = 10;
$sleepSeconds = 5;

if (!empty($baseApiUrl)) {
    // Import report and get queue_id
    $queueId = null;
    $token = null;

    for ($i = 0; $i < $retryTimes; $i++) {
        $testPingResult = apiCall('http://ci-reports.framgia.vn/test.php', false);
        $createReportResult = apiCall($baseApiUrl, true, $arguments, ['Content-Type: application/json']);
        $queueResult = json_decode($createReportResult, true);

        if (!empty($queueResult) && isset($queueResult['status']) && $queueResult['status']) {
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

            if (!empty($result) && isset($result['status']) && $result['status']) {
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
