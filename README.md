# Yii2-loki-log-target

Yii2 log target for loki

## Setup
install via composer

```bash
composer require pazakharov/yii2-log-loki-target
```

add the log as target:
```
 'targets' => [
                'loki' => [
                    'class' => \pazakharov\yii2\LokiTarget::class,
                    'levels' => ['warning'],
                    'labels' => ['test', 'loki'],
                    'lokiUrl' => '/api/v1/push',
                    'client' => [
                        'class' => \yii\httpclient\Client::class,
                        'baseUrl' => env('LOKI_HOST')
                    ],
                ],
            ],
```

You can define property 'formatMessageCallback' - callable that format message before sending it to the loki server