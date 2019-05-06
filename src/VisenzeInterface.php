<?php

namespace Drupal\visenze;

/**
 * Provides an interface for Visenze access.
 */
interface VisenzeInterface {

  /**
   * Any errors that we got.
   */
  public function getErrors();

  /**
   * Did the request get a valid answer?
   */
  public function isValid();

  /**
   * Get data from visenze.
   */
  public function getData($image_url);

}
