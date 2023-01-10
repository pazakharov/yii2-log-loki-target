<?php

namespace pazakharov\yii2;

use yii\log\Logger;
use yii\log\Target;
use yii\di\Instance;
use yii\helpers\Json;
use yii\helpers\VarDumper;
use yii\httpclient\Client;
use yii\log\LogRuntimeException;

class LokiTarget extends Target
{

    /**
     * httpClient
     *
     * @var Client|null
     */
    public $client;

    /**
     * Loki url
     *
     * @var string
     */
    public $lokiUrl;

    public $label = '';

    /**
     * formatMessageCallback
     *
     * @var callable|null
     */
    public $formatMessageCallback;

    /**
     * init
     *
     * @return void
     */
    public function init()
    {
        parent::init();
        $this->client = Instance::ensure($this->client, Client::class);
    }

    /**
     * export
     *
     * @return void
     */
    public function export()
    {
        $data = [];
        foreach ($this->messages as $message) {
            $data[] = $this->formatMessage($message);
        }
        $request = [
            'streams' => [
                [
                    'stream' => [
                        'label' => $this->label,
                    ]
                ],
            ],
            'values' => $data,
        ];
        $this->requestToLoki($request);
    }

    /**
     * formatMessage
     *
     * @param  mixed $message
     * @return array
     */
    public function formatMessage($message)
    {
        if (is_callable($this->formatMessageCallback)) {
            return call_user_func($this->formatMessageCallback, $message);
        }

        list($text, $level, $category, $timestamp) = $message;
        if (!is_string($text)) {
            // exceptions may not be serializable if in the call stack somewhere is a Closure
            if ($text instanceof \Exception || $text instanceof \Throwable) {
                $text = (string) $text;
            } else {
                $text = VarDumper::export($text);
            }
        }
        return [
            number_format($timestamp * 1000000000, 0, '.', ''),
            json_encode([
                'level' => Logger::getLevelName($level),
                'category' => $category,
                'log_time' => $timestamp,
                'prefix' => $this->getMessagePrefix($message),
                'message' => $text,
            ])
        ];
    }

    /**
     * requestToLoki
     *
     * @param  array $data
     * @return void
     * @throws LogRuntimeException
     */
    public function requestToLoki(array $data)
    {
        try {
            $response = $this->client->post($this->lokiUrl, $data)->send();
        } catch (\Throwable $th) {
            throw $th;
        }
        if (!$response->isOk) {
            throw new LogRuntimeException('Unable to export log to Loki');
        }
    }
}
