<?php

namespace Drupal\Tests\visenze_tagging\Unit;

use Drupal\Component\Serialization\Json;
use Drupal\Tests\UnitTestCase;
use Drupal\visenze_tagging\VisenzeTagging;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

/**
 * @file
 * PHPUnit tests for the Visenze Tagging.
 */

/**
 * @coversDefaultClass \Drupal\visenze_tagging\VisenzeTagging
 */
class VisenzeTaggingTest extends UnitTestCase {

  /**
   * Guzzle http_client service.
   *
   * @var \GuzzleHttp\Client
   */
  protected $guzzleClient;

  /**
   * Mock of http_client service.
   *
   * @var \GuzzleHttp\Client
   */
  protected $mockHttp;

  /**
   * Mock handler.
   *
   * @var \Drupal\Core\State\State
   */
  protected $mockhandler;

  /**
   * Mock of config.factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $mockConfigFactory;

  /**
   * String translation mock.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $stringTranslation;

  /**
   * Set up test environment.
   */
  public function setUp() {
    parent::setUp();

    $this->mockHandler = new MockHandler();
    $handler = HandlerStack::create($this->mockHandler);
    $this->mockHttp = new HttpClient(['handler' => $handler]);
    $this->stringTranslation = $this->getStringTranslationStub();

    $config = [
      'visenze_tagging.settings' => [
        'url' => 'https://virecognition.visenze.com',
        'version' => 'v1',
        'service' => 'image/recognize',
        'access_key' => 'foo',
        'secret_key' => 'bar',
        'tag_groups' => [
          0 => 'fashion_attributes',
        ],
      ],
    ];
    $mockConfigFactory = $this->getConfigFactoryStub($config);

    $this->mockConfigFactory = $mockConfigFactory;
  }

  /**
   * Test the getData method.
   */
  public function testGetData() {

    $uri = "https://products-dev.harpersbazaar.de/dummy.jpg";

    $body = file_get_contents(__DIR__ . '/Mocks/tagged-skirt.json');
    // This sets up the mock client to respond to the first request it gets
    // with an HTTP 200 containing your mock json body.
    $this->mockHandler->append(new Response(200, [], $body));

    $visenze = new VisenzeTagging(
      $this->mockConfigFactory,
      $this->mockHttp,
      $this->stringTranslation
    );

    $visenze_response = $visenze->getData($uri);
    $this->assertNotEmpty($visenze_response);
    $this->assertEmpty($visenze->getErrors());
    $this->assertTrue($visenze->IsValid() === TRUE);
    $this->assertEquals($visenze_response, Json::decode($body));

    $body = file_get_contents(__DIR__ . '/Mocks/some-error-occurred.json');
    $this->mockHandler->append(new Response(200, [], $body));
    $visenze = new VisenzeTagging(
      $this->mockConfigFactory,
      $this->mockHttp,
      $this->stringTranslation
    );

    $visenze_response = $visenze->getData($uri);
    $this->assertNotEmpty($visenze_response);
    $this->assertNotEmpty($visenze->getErrors());
    $this->assertTRUE($visenze->IsValid() === FALSE);
    $this->assertEquals($visenze_response, Json::decode($body));
    $this->assertEquals($visenze->getErrors(), [0 => [0 => "It's Monday"]]);
  }

}
