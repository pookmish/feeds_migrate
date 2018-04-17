<?php

namespace Drupal\feeds_migrate\Plugin\feeds_migrate\authentication;

use Drupal\feeds_migrate\AuthenticationFormPluginBase;

/**
 * Provides basic authentication for the HTTP resource.
 *
 * @AuthenticationForm(
 *   id = "oauth2",
 *   title = @Translation("Oauth2"),
 *   parent = "oauth2"
 * )
 */
class Oauth2 extends AuthenticationFormPluginBase {

}
