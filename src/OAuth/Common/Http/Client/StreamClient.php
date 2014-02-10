<?php

namespace OAuth\Common\Http\Client;

use OAuth\Common\Http\Exception\TokenResponseException;
use OAuth\Common\Http\Uri\UriInterface;
use OAuth\Common\Http\Uri\UriFactory;
use OAuth\Common\Http\Uri;

/**
 * Client implementation for streams/file_get_contents
 */
class StreamClient extends AbstractClient
{
    /**
     * Any implementing HTTP providers should send a request to the provided endpoint with the parameters.
     * They should return, in string form, the response body and throw an exception on error.
     *
     * @param UriInterface $endpoint
     * @param mixed        $requestBody
     * @param array        $extraHeaders
     * @param string       $method
     *
     * @return string
     *
     * @throws TokenResponseException
     * @throws \InvalidArgumentException
     */
    public function retrieveResponse(
        UriInterface $endpoint,
        $requestBody,
        array $extraHeaders = array(),
        $method = 'POST'
    ) {
        // Normalize method name
        $method = strtoupper($method);

        $this->normalizeHeaders($extraHeaders);

        if ($method === 'GET' && !empty($requestBody)) {
            throw new \InvalidArgumentException('No body expected for "GET" request.');
        }

        if (!isset($extraHeaders['Content-type']) && $method === 'POST' && is_array($requestBody)) {
            $extraHeaders['Content-type'] = 'Content-type: application/x-www-form-urlencoded';
        }

        $host = 'Host: '.$endpoint->getHost();
        // Append port to Host if it has been specified
        if ($endpoint->hasExplicitPortSpecified()) {
            $host .= ':'.$endpoint->getPort();
        }

        $extraHeaders['Host']       = $host;
        $extraHeaders['Connection'] = 'Connection: close';

        if (is_array($requestBody)) {
            $requestBody = http_build_query($requestBody, '', '&');
        }
        $extraHeaders['Content-length'] = 'Content-length: '.strlen($requestBody);

        $response = $this->getResponse(
            $endpoint,
            $requestBody,
            $extraHeaders,
            $method
        );
        return $response;
    }

    private function generateStreamContext(
        UriInterface $uri,
        $body,
        $headers,
        $method
    ) {
        $opts = array(
            'http' => array(
                'method'           => $method,
                'header'           => implode("\r\n", array_values($headers)),
                'content'          => $body,
                'protocol_version' => '1.1',
                'user_agent'       => $this->userAgent,
                'max_redirects'    => $this->maxRedirects,
                'timeout'          => $this->timeout,
                'ignore_errors'    => true,
            ),
        );

        $uriFactory = new UriFactory();
        $proxyUri = $uriFactory->createProxyUriFromEnv($_ENV, $uri->getScheme());
        if (null === $proxyUri) {
            return stream_context_create($opts);
        }

        if (!empty($proxyUri->getRawUserInfo)) {
            $proxyAuth = base64_encode($proxyUri->getRawUserInfo);
            $opts['http']['header'] .= "\r\n". 'Proxy-Authorization: Basic '. $proxyAuth;
        }

        if ('http' === $proxyUri->getScheme()) {
            $proxyUri->setScheme('tcp');
        }
        if ('https' === $proxyUri->getScheme()) {
            $proxyUri->setScheme('ssl');
        }

        $proxyUri->setUserInfo('');

        $opts['http']['proxy'] = $proxyUri->getAbsoluteUri();
        $opts['http']['request_fulluri'] = true;

        return stream_context_create($opts);
    }

    private function getResponse(
        UriInterface $uri,
        $body,
        $headers,
        $method
    ) {
        $context = $this->generateStreamContext($uri, $body, $headers, $method);
        $response = file_get_contents($uri->getAbsoluteUri(), false, $context);
        $statusCode = $this->getStatusCode($http_response_header);
        if (!$this->isSuccess($statusCode)) {
            throw new TokenResponseException($response);
        }
        return $response;
    }

    private function getStatusCode($responseHeaders)
    {
        preg_match('/HTTP\/1\.[0|1|x] ([0-9]{3})/', $responseHeaders[0], $matches);
        return (int)$matches[1];
    }

    private function isSuccess($statusCode)
    {
        return $statusCode >= 200 && $statusCode < 300 || $statusCode === 304;
    }


}
