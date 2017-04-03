<?php

namespace Tests\Integrational;

use PubNub\Endpoints\PubSub\Publish;
use PubNub\Exceptions\PubNubServerException;
use PubNub\Models\Consumer\PNPublishResult;
use PubNub\Models\ResponseHelpers\PNEnvelope;
use PubNub\PNConfiguration;
use PubNub\PubNub;


class PublishTest extends \PubNubTestCase
{
    /**
     * @param Publish $publish
     */
    private function assertSuccess($publish)
    {
        $result = $publish->sync();

        $this->assertInstanceOf(PNPublishResult::class, $result);
        $this->assertGreaterThan(14847319130820201, $result->getTimetoken());

        $envelope = $publish->envelope();
        $this->assertInstanceOf(PNEnvelope::class, $envelope);
        $this->assertInstanceOf(PNPublishResult::class, $envelope->getResult());
        $this->assertGreaterThan(14847319130820201, $envelope->getResult()->getTimetoken());

        $this->assertEquals($result->getTimetoken(), $envelope->getResult()->getTimetoken());

        $publish->clear();

        $result2 = $publish->sync();
        $this->assertNotEquals($result->getTimetoken(), $result2->getTimetoken());
    }

    /**
     * @param Publish $publish
     * @param $message
     */
    private function assertSuccessPublishGet($publish, $message)
    {
        $this->assertSuccess($publish->channel('blah')->message($message));
    }

    /**
     * @param Publish $publish
     * @param $message
     */
    private function assertSuccessPublishPost($publish, $message)
    {
        $this->assertSuccess($publish->channel('blah')->setUsePost(true)->message($message));
    }

    public function testPublishMixedViaGet()
    {
        $this->assertSuccessPublishGet($this->pubnub->publish(), 'hi');
        $this->assertSuccessPublishGet($this->pubnub->publish(), 5);
        $this->assertSuccessPublishGet($this->pubnub->publish(), 3.14);
        $this->assertSuccessPublishGet($this->pubnub->publish(), false);
        $this->assertSuccessPublishGet($this->pubnub->publish(), ['hey', 'hey2', 'hey3']);
        $this->assertSuccessPublishGet($this->pubnub->publish(), ['hey' => 31, 'hey2' => true, 'hey3' =>['ok']]);
    }

    public function testPublishMixedViaPost()
    {
        $this->assertSuccessPublishPost($this->pubnub->publish(), 'hi');
        $this->assertSuccessPublishPost($this->pubnub->publish(), 5);
        $this->assertSuccessPublishPost($this->pubnub->publish(), 3.14);
        $this->assertSuccessPublishPost($this->pubnub->publish(), false);
        $this->assertSuccessPublishPost($this->pubnub->publish(), ['hey', 'hey2', 'hey3']);
        $this->assertSuccessPublishPost($this->pubnub->publish(), ['hey' => 31, 'hey2' => true, 'hey3' =>['ok']]);
    }

    public function testPublishMixedViaGetEncrypted()
    {
        $this->assertSuccessPublishGet($this->pubnub_enc->publish(), 'hi');
        $this->assertSuccessPublishGet($this->pubnub_enc->publish(), 5);
        $this->assertSuccessPublishGet($this->pubnub_enc->publish(), 3.14);
        $this->assertSuccessPublishGet($this->pubnub_enc->publish(), false);
        $this->assertSuccessPublishGet($this->pubnub_enc->publish(), ['hey', 'hey2', 'hey3']);
        $this->assertSuccessPublishGet($this->pubnub_enc->publish(), ['hey' => 31, 'hey2' => true, 'hey3' =>['ok']]);
    }

    public function testPublishMixedViaPostEncrypted()
    {
        $this->assertSuccessPublishPost($this->pubnub_enc->publish(), 'hi');
        $this->assertSuccessPublishPost($this->pubnub_enc->publish(), 5);
        $this->assertSuccessPublishPost($this->pubnub_enc->publish(), 3.14);
        $this->assertSuccessPublishPost($this->pubnub_enc->publish(), false);
        $this->assertSuccessPublishPost($this->pubnub_enc->publish(), ['hey', 'hey2', 'hey3']);
        $this->assertSuccessPublishPost($this->pubnub_enc->publish(), ['hey' => 31, 'hey2' => true, 'hey3' =>['ok']]);
    }

    // TODO: fix test
    public function xtestPublishWithMeta()
    {
        $this->assertSuccess($this->pubnub->publish()->channel('blah')->message('hey')
            ->setMeta([
                'a' => 2,
                'b' => 'qwer'
        ]));
    }

    public function testPublishDoNotStore()
    {
        $this->assertSuccess($this->pubnub->publish()->channel('blah')->message('hey')->setShouldStore(true));
    }

    public function testServerSideErrorSync()
    {
        $this->expectException(PubNubServerException::class);
        $this->expectExceptionMessage("Server responded with an error and the status code is 400");

        $pnconf = PNConfiguration::demoKeys();
        $pnconf->setPublishKey("fake");

        $pubnub = new PubNub($pnconf);

        $pubnub->publish()->channel('blah')->message('hey')->sync();
    }

    public function testServerSideErrorEnvelope()
    {
        $pnconf = PNConfiguration::demoKeys();
        $pnconf->setPublishKey("fake");

        $pubnub = new PubNub($pnconf);

        $envelope = $pubnub->publish()->channel('blah')->message('hey')->envelope();

        $this->assertNull($envelope->getResult());
        $this->assertEquals(400, $envelope->getStatus()->getStatusCode());

        /** @var PubNubServerException $exception */
        $exception = $envelope->getStatus()->getException();

        $this->assertEquals(400, $exception->getStatusCode());
        $this->assertEquals("Server responded with an error and the status code is 400", $exception->getMessage());

        $body = $exception->getBody();
        $this->assertEquals(0, $body[0]);
        $this->assertEquals("Invalid Key", $body[1]);
    }
}
