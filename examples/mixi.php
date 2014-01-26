<?php

/**
 * Example of retrieving an authentication token of the mixi service
 *
 * PHP version 5.4
 *
 * @author     Benjamin Bender <bb@codepoet.de>
 * @author     David Desberg <david@daviddesberg.com>
 * @author     Pieter Hordijk <info@pieterhordijk.com>
 * @author     Takaya Arikawa <tky.c10ver@gmail.com>
 * @copyright  Copyright (c) 2012 The authors
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 */

use OAuth\OAuth2\Service\Mixi;
use OAuth\Common\Storage\Session;
use OAuth\Common\Consumer\Credentials;

/**
 * Bootstrap the example
 */
require_once __DIR__ . '/bootstrap.php';

// Session storage
$storage = new Session();
// Setup the credentials for the requests
$credentials = new Credentials(
    $servicesCredentials['mixi']['key'],
    $servicesCredentials['mixi']['secret'],
    $currentUri->getAbsoluteUri()
);

// Instantiate the mixi service using the credentials, http client and storage mechanism for the token
/** @var $mixiService mixi */
$mixiService = $serviceFactory->createService('mixi', $credentials, $storage, array(Mixi::SCOPE_READ_PROFILE, Mixi::SCOPE_WRITE_VOICE));

if (!empty($_GET['code'])) {
    // This was a callback request from mixi, get the token
    $token = $mixiService->requestAccessToken($_GET['code']);

    // Send a request with it
    $result = json_decode($mixiService->request('/people/@me/@self'), true);

    // Show some of the resultant data
    echo 'Your unique mixi user id is: ' . $result['entry']['id'] . ' and your name is ' . $result['entry']['displayName'];

} elseif (!empty($_GET['go']) && $_GET['go'] === 'go') {
    $url = $mixiService->getAuthorizationUri();
    header('Location: ' . $url);
} else {
    $url = $currentUri->getRelativeUri() . '?go=go';
    echo "<a href='$url'>Login with mixi!</a>";
}
