<?php

require __DIR__ . '/../vendor/autoload.php';

use ElementaryFramework\WaterPipe\WaterPipe;
use ElementaryFramework\WaterPipe\HTTP\Request\Request;
use ElementaryFramework\WaterPipe\HTTP\Response\Response;
use Pixelbrackets\Html5MiniTemplate\Html5MiniTemplate;

$root = new WaterPipe;

// homepage
// http GET localhost/
$root->get('/', function (Request $request, Response $response) {
    $content = '<h1>LaMetric Notification Broadcast</h1>
      <p>
          1. Register LaMetric devices in <code>../data/subscriptions.json</code><br>
          2. Push message <code>http POST localhost/hook/ message="Hi" icon="26175"</code>
      </p>';
    $template = (new Html5MiniTemplate())
        ->setStylesheet('skeleton')
        ->setStylesheetMode(Html5MiniTemplate::STYLE_INLINE)
        ->setContent($content);
    $response->sendHtml($template->getMarkup());
});

// message endpoint
// http POST localhost/hook message="Hi" icon="26175"
$root->post('/hook', function (Request $request, Response $response) {
    $data = $request->getBody();
    $devices = json_decode((string)file_get_contents(__DIR__ . '/../data/subscriptions.json'), true);
    $client = new GuzzleHttp\Client([
        'verify' => false
    ]);

    $payload = [
        'model' => [
            'frames' => [
                [
                    'icon' => (string) ($data['icon'] ?? '620'),
                    'text' => (string) ($data['message'] ?? '')
                ]
            ],
            'cycles' => '3'
        ]
    ];
    $options = [
        'json' => $payload,
        'auth' => [
            'dev',
            'xxxtokenxxx'
        ],
    ];

    foreach ($devices as $device) {
        $options['auth'][1] = $device['token'];
        $deviceUri = 'https://' . $device['ip'] . ':4343/api/v2/device/notifications';

        $deviceResponse = $client->request(
            'POST',
            $deviceUri,
            $options
        );

        $statusCode = $deviceResponse->getStatusCode();
        if ($statusCode !== 201) {
            $response->sendJson([
                'status' => 'fail',
                'data' => 'Device not available'
            ]);
        }
    }

    $response->sendJson([
        'status' => 'success',
        'data' => $data
    ]);
});

$root->run();
