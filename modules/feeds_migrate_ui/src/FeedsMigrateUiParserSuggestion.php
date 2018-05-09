<?php

namespace Drupal\feeds_migrate_ui;

use Drupal\migrate_plus\DataParserPluginManager;
use GuzzleHttp\Psr7\Stream;

/**
 * Class FeedsMigrateUiParserSuggestion.
 *
 * @package Drupal\feeds_migrate_ui
 */
class FeedsMigrateUiParserSuggestion {

  const PARSER_SIMPLE_XML = 'simple_xml';
  const PARSER_JSON = 'json';

  /**
   * @var \Drupal\migrate_plus\DataParserPluginManager
   */
  protected $parserManager;

  /**
   * @var \Drupal\migrate_plus\DataFetcherPluginManager
   */
  protected $fetcherManager;

  /**
   * FeedsMigrateUiParserSuggestion constructor.
   */
  public function __construct(DataParserPluginManager $parser_manager) {
    $this->parserManager = $parser_manager;
    $this->fetcherManager = \Drupal::service('plugin.manager.migrate_plus.data_fetcher');
  }

  public function getSuggestedParser($data) {
    $headers = get_headers($data);
    foreach ($headers as $header) {
      if (strpos($header, 'Content-Type') !== FALSE) {
        if (strpos($header, 'xml')) {
          return $this->parserManager->createInstance(self::PARSER_SIMPLE_XML);
        }
        elseif (strpos($header, 'json')) {
          return $this->parserManager->createInstance(self::PARSER_JSON);
        }
      }
    }
    return FALSE;
  }

  public function getSuggestedSelectors($fetcher_plugin_id, $url) {
    /** @var \Drupal\migrate_plus\DataFetcherPluginInterface $fetcher_plugin */
    $fetcher_plugin = $this->fetcherManager->createInstance($fetcher_plugin_id);
    $contents = $fetcher_plugin->getResponseContent($url);
    if ($contents instanceof Stream) {
      $contents = $contents->getContents();
    }

    switch ($this->getSuggestedParser($url)) {
      case 'simple_xml':
        return array_keys($this->getSuggestedSelectorsXml($contents));

      case 'json':
        return array_keys($this->getSuggestedSelectorsJson($contents));

    }
  }

  protected function getSuggestedSelectorsXml($contents, &$suggestions = [], $path = '') {
    $xml = simplexml_load_string($contents);
    if (empty($path)) {
      $path = '/' . $xml->getName();
    }
    else {
      $path .= '/' . $xml->getName();
    }
    $suggestions[$path] = isset($suggestions[$path]) ? $suggestions[$path] + 1 : 1;

    foreach ($xml as $id => $element) {
      if ($element->count()) {
        $this->getSuggestedSelectorsXml($element->saveXML(), $suggestions, $path);
      }
    }
    return $suggestions;
  }

  protected function getSuggestedSelectorsJson($contents) {
    return [];
  }

}
