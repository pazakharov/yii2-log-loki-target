<?php

namespace pazakharov\yii2\tests\unit;

use Yii;
use stdClass;
use yii\log\Logger;
use yii\log\Dispatcher;
use yii\httpclient\Client;
use yii\httpclient\Request;
use yii\httpclient\Response;
use pazakharov\yii2\LokiTarget;

class LokiTargetTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    // tests
    public function testSendLogsToLoki()
    {
        $catcher = new stdClass();
        $response = (object)['isOk' => true];
        $request = $this->make(Request::class, [
            'send' => function () use ($response) {
                return $response;
            }
        ]);
        $client = $this->make(Client::class, [
            'post' => function ($url, $data) use ($request, $catcher) {
                $catcher->url = $url;
                $catcher->data = $data;
                return new Response();
                return $request;
            }
        ]);
        $lokiUrl = '/api/v1';
        $logger = \Yii::getLogger();
        $logger->dispatcher = Yii::createObject([
            'class' => Dispatcher::class,
            'targets' => [
                'loki' => [
                    'class' => LokiTarget::class,
                    'levels' => ['warning'],
                    'label' => 'test',
                    'lokiUrl' => $lokiUrl,
                    'client' => $client,
                ],
            ],
        ]);
        $this->tester->assertInstanceOf(Logger::class, $logger);
        $time = 1424865393.0105;
        // forming message data manually in order to set time
        $messsageData = [
            'test',
            Logger::LEVEL_WARNING,
            'message',
            $time,
            [],
        ];
        $logger->messages[] = $messsageData;
        $logger->flush(true);
        $this->assertEquals($lokiUrl, $catcher->url);
        $this->assertIsArray($catcher->data);
    }
}
