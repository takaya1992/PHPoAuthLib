<?php

namespace OAuth\OAuth2\Service;

use OAuth\Common\Exception\Exception;
use OAuth\OAuth2\Token\StdOAuth2Token;
use OAuth\Common\Http\Exception\TokenResponseException;
use OAuth\Common\Http\Uri\Uri;
use OAuth\Common\Consumer\CredentialsInterface;
use OAuth\Common\Http\Client\ClientInterface;
use OAuth\Common\Storage\TokenStorageInterface;
use OAuth\Common\Http\Uri\UriInterface;

class Mixi extends AbstractService
{
    /**
     * TODO:
     * mixi www url - used to build dialog urls
     */
    const WWW_URL = 'https://www.mixi.jp/';

    /**
     * Defined scopes
     *
     * If you don't think this is scary you should not be allowed on the web at all
     *
     * @link http://developer.mixi.co.jp/connect/mixi_graph_api/api_auth/#toc-authorization-code
     */
    const SCOPE_READ_PROFILE  = 'r_profile';
    const SCOPE_READ_UPDATES  = 'r_updates';
    const SCOPE_READ_VOICE    = 'r_voice';
    const SCOPE_WRITE_VOICE   = 'w_voice';
    const SCPOE_WRITE_SHARE   = 'w_share';
    const SCOPE_READ_PHOTO    = 'r_photo';
    const SCOPE_WRITE_PHOTO   = 'w_photo';
    const SCOPE_READ_MESSAGE  = 'r_message';
    const SCOPE_WRITE_MESSAGE = 'w_message';
    const SCOPE_WRITE_DIARY   = 'w_diary';

    public function __construct(
        CredentialsInterface $credentials,
        ClientInterface $httpClient,
        TokenStorageInterface $storage,
        $scopes = array(),
        UriInterface $baseApiUri = null
    ) {
        parent::__construct($credentials, $httpClient, $storage, $scopes, $baseApiUri);

        if (null === $baseApiUri) {
            $this->baseApiUri = new Uri('https://api.mixi-platform.com/2/');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthorizationEndpoint()
    {
        return new Uri('https://mixi.jp/connect_authorize.pl');
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenEndpoint()
    {
        return new Uri('https://secure.mixi-platform.com/2/token');
    }

    /**
     * {@inheritdoc}
     */
    protected function parseAccessTokenResponse($responseBody)
    {
        $data = json_decode($responseBody);

        if (null === $data || !is_object($data)) {
            throw new TokenResponseException('Unable to parse response.');
        } elseif (isset($data->error)) {
            throw new TokenResponseException('Error in retrieving token: "' . $data->error . '"');
        }

        $token = new StdOAuth2Token();
        $token->setAccessToken($data->access_token);
        $token->setLifeTime($data->expires_in);

        if (isset($data->refresh_token)) {
            $token->setRefreshToken($data->refresh_token);
            unset($data->refresh_token);
        }

        unset($data->access_token);
        unset($data->expires_in);

        $token->setExtraParams(get_object_vars($data));

        return $token;
    }

}
