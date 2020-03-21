<?php

error_reporting(E_ALL);
$VERSION = 'v2.0';

$API_URL_ENDPOINT = 'https://api.cloud4wi.com';

/*  Enter here your API key  */
$API_AUTH_KEY = '';

/*  Enter here your API secret  */
$API_AUTH_SECRET = '';

$LIMIT = 1000;
$OFFSET = 0;
$API_URL_VERSION = '/v2'; // leave blank for v1
$API_URL_METHOD = '/users';
$API_CALL = $API_URL_ENDPOINT . $API_URL_VERSION . $API_URL_METHOD;

$PAGE_COUNT = 0;

/*  Get total count for the pagination.
 *  I can get the count regardless of limit/offset
 *  then I can ask only for the first element
 */

$ENTITY_COUNT_PARAMS = array(
    'limit' => 1000,
    'offset' => 0,
    'api_version' => $VERSION,
    'api_key' => $API_AUTH_KEY,
    'api_secret' => $API_AUTH_SECRET,
    'deleted' => false
);

try {
    $totalEntityCount = sendRequest($API_CALL, $ENTITY_COUNT_PARAMS);

    if (!$totalEntityCount) {
        die('Cloud4Wi API not responding');
    }

    if (isset($totalEntityCount['count'])) {
        $entityCount = $totalEntityCount['count'];
    } else {
        $entityCount = 0;
    }
} catch (Exception $e) {
    echo $e->getMessage();
    exit;
}

/*  JSON header  */
header('Content-Type: application/json; charset=utf-8');

/*  If entity count > 0 loop through the data  */

if ($entityCount > 0) {
    $PAGE_COUNT = ceil($entityCount / $ENTITY_COUNT_PARAMS['limit']);
    for ($i = 1; $i <= $PAGE_COUNT; $i++) {
        $API_PARAMS = array(
            'limit' => $LIMIT,
            'offset' => $OFFSET,
            'api_version' => $VERSION,
            'api_key' => $API_AUTH_KEY,
            'api_secret' => $API_AUTH_SECRET
        );

        try {
            $req = sendRequest($API_CALL, $ENTITY_COUNT_PARAMS);
            $OFFSET = $OFFSET + $LIMIT;
            c4wPrintCsvContent($req);
        } catch (Exception $e) {
            echo $e->getMessage();
            exit;
        }
    }
}

exit;

function sendRequest($API_CALL, $API_PARAMS) {

    $returnArray = array();
    $url = $API_CALL;
    $url .= '?' . http_build_query($API_PARAMS);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    $result = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        echo 'cURL error: ' . curl_error($ch);
    }

    curl_close($ch);

    /*  If http code is 200 OR 'Success' return results, else return false  */
    if ($httpcode == 200) {
        $returnArray = json_decode($result, true);
        return $returnArray;
    } else {
        throw new Exception('API Error : HTTP ' . $httpcode);
        return false;
    }
}

/*  Set $header = true if you want to print the header
 *  Set $delimiters and $enclosure to change CSV format
 */

function c4wPrintCsvContent($r, $header = true, $delimiter = ",", $enclosure = '"') {

    /*  Set $fields array below if you want to customize the format of your data  */

    $fields = array(
        'firstName' => 'First name',
        'lastName' => 'Last name',
        'email' => 'Email',
        'username' => 'Username'
    );

    if (isset($r[0]['data'])) {
        $data = $r[0]['data'];
    } else if (isset($r['data'])) {
        $data = $r['data'];
    } else {
        return null;
    }

    if ($header) {
        $out = fopen('php://output', 'w');
        fputcsv($out, $fields, $delimiter, $enclosure);
        fclose($out);
    }

    foreach ($data as $d) {

        $content = array();

        foreach (array_keys($fields) as $f) {
            if (isset($d[$f])) {
                $content[$f] = $d[$f];
            } else {
                $content[$f] = null;
            }
        }

        $out = fopen('php://output', 'w');
        fputcsv($out, $content, $delimiter, $enclosure);
        fclose($out);
    }
}

?>
