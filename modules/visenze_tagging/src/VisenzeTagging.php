<?php

namespace Drupal\visenze_tagging;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Component\Serialization\Json;
use Drupal\visenze\VisenzeInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Defines a VisenzeTagging service.
 */
class VisenzeTagging implements VisenzeInterface {

  use StringTranslationTrait;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Guzzle HTTP client definition.
   *
   * @var GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Collection of errors.
   *
   * @var array
   */
  protected $errors = [];

  /**
   * Valid request.
   *
   * @var bool
   */
  protected $valid = FALSE;

  /**
   * VisenzeTagging constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The http client.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ClientInterface $http_client, TranslationInterface $string_translation) {
    $this->config = $config_factory->get('visenze_tagging.settings');
    $this->httpClient = $http_client;
    $this->setStringTranslation($string_translation);
  }

  /**
   * {@inheritdoc}
   */
  public function getErrors() {
    return $this->errors;
  }

  /**
   * {@inheritdoc}
   */
  public function isValid() {
    return $this->valid;
  }

  /**
   * {@inheritdoc}
   */
  public function getData($image_url) {
    $client = $this->httpClient;
    $method = 'POST';

    $url = $this->config->get('url') . '/' . $this->config->get('version') . '/' . $this->config->get('service');
    $options = [
      'auth' => [$this->config->get('access_key'), $this->config->get('secret_key')],
      'multipart' => [
        [
          'name' => 'url',
          'contents' => $image_url,
        ],
      ],
    ];

    foreach ($this->config->get('tag_groups') as $tag_group) {
      $options['multipart'][] = ['name' => 'tag_group', 'contents' => $tag_group];
    }

    try {
      $response = $client->request($method, $url, $options);
      $code = $response->getStatusCode();

      if ($code == 200) {
        $result = $response->getBody()->getContents();
        $result = Json::decode($result);
        if ($result['status'] != 'OK') {
          $this->errors[] = $result['error'];
        }
        else {
          $this->valid = TRUE;
        }
        return $result;
      }
      else {
        $this->errors[] = $this->t('Request for !url (image !image) not successfull.', ['!url' => $url, 'image' => $image_url]);
      }
    }
    catch (GuzzleException $e) {
      watchdog_exception('visenze_tagging', $e);
    }
    return FALSE;
  }

}
