<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

declare(strict_types=1);

namespace Biblica\Bible\ApiDotBibleApi\Utils;

use Exception;
use Biblica\Util\CacheManager;
use Biblica\Util\StaticLogUtilities;
use Biblica\WordPress\Plugin\OnlineBible\Settings as OnlineBibleSettings;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Response;
use Psr\Cache\CacheException;
use Psr\Cache\InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LogLevel;
use Symfony\Contracts\Cache\ItemInterface;

class API
{
    use StaticLogUtilities;

    private static string $baseUrl = 'https://api.scripture.api.bible/v1/';
    public const CACHE_TAG = 'CacheItems_ApiDotBible';

    public static function httpRequest(string $query, array $parameters = []): ?Response
    {
        try {
            $requestOptions = [
                'query' => $parameters,
                'timeout' => 30,
                'headers' => [
                    'api-key' => OnlineBibleSettings::$bibleApiKey
                ]
            ];
            $client = new Client(['base_uri' => self::$baseUrl]);

            return $client->request('GET', $query, $requestOptions);
        } catch (RequestException $re) {
            self::logException($re);

            if ($re->hasResponse()) {
                return $re->getResponse();
            }

            return null;
        } catch (Exception $e) {
            self::logException($e);

            return null;
        }
    }

    private static function logException(Exception $e): void
    {
        $exceptionInfo = '[EXCEPTION: (' . $e->getCode() . ') ' . $e->getMessage() . '] ';
        $protocol = sanitize_text_field($_SERVER['HTTPS']) === 'on' ? 'https://' : 'http://';
        $host = sanitize_text_field($_SERVER['HTTP_HOST']);
        $uri = sanitize_text_field($_SERVER['REQUEST_URI']);
        $pageUrlInfo = '[PAGE URL: ' . $protocol . $host . $uri . '] ';
        if ($e instanceof RequestException) {
            $requestInfo = '[REQUEST: ' . Message::toString($e->getRequest()) . '] ';
        } else {
            $requestInfo = '';
        }
        if ($e instanceof RequestException && $e->hasResponse()) {
            $responseInfo = '[RESPONSE: ' . Message::toString($e->getResponse()) . '] ';
        } else {
            $responseInfo = '';
        }
        $logMessage = $exceptionInfo . $pageUrlInfo . $requestInfo . $responseInfo;
        self::log(LogLevel::ERROR, $logMessage);

        $trace = '[CALL STACK: ' . $e->getTraceAsString() . '] ';
        self::log(LogLevel::DEBUG, $trace);
    }

    /**
     * @param string $query
     * @param array $parameters
     * @param bool $rawFormat
     * @return mixed|string|null
     */
    public static function call(string $query, array $parameters = [], bool $rawFormat = false)
    {
        $response = self::httpRequest($query, $parameters);

        if ($response === null) {
            return null;
        }

        if (self::hasErrors($response)) {
            return null;
        }

        $responseContent = strval($response->getBody());
        if ($rawFormat === true) {
            $returnValue = $responseContent;
        } else {
            $returnValue = json_decode($responseContent);
        }

        return $returnValue;
    }

    /**
     * Makes an API call with the specified query, e.g. "bible/John/niv" and
     * converts the json response to an object. The serialized object will be cached
     * for 24 hours.
     *
     * @param string $query The query to call.
     * @param array $parameters
     * @param bool $rawFormat If true, return the call response as json. Otherwise return a PHP object.
     * @return object|string|null
     */
    public static function callAndCache(string $query, array $parameters = [], bool $rawFormat = false)
    {
        $makeApiCall = function (ItemInterface $item = null) use ($query, $parameters, $rawFormat) {
            if ($item !== null) {
                try {
                    $item->tag(API::CACHE_TAG);
                } catch (CacheException | InvalidArgumentException $e) {
                    $this->log(LogLevel::ERROR, 'Unable to tag cache item. [EXCEPTION: ' . $e . ']');
                }
            }

            $response = self::call($query, $parameters, $rawFormat);
            if ($response === null && $item !== null) {
                $item->expiresAfter(1);
                self::log(
                    LogLevel::DEBUG,
                    "Invalid api response. Skipping cache: [CACHE KEY: " . self::$baseUrl . $query . "] " .
                    "[QUERY: $query] [PARAMETERS: " . print_r($parameters, true) . "]"
                );
            }

            return $response;
        };

        try {
            $response = CacheManager::getObjectCache()->get(self::$baseUrl . $query, $makeApiCall);
        } catch (Exception $e) {
            self::log(LogLevel::ERROR, 'Cache error: ' . $e);
            $response = $makeApiCall();
        }

        return $response;
    }

    /**
     * Determines if a response has errors.
     *
     * @param ?ResponseInterface $response
     * @return bool Returns true if errors were found in the response.
     */
    public static function hasErrors(?ResponseInterface $response = null): bool
    {
        if ($response === null) {
            return true;
        }
        $httpStatus = $response->getStatusCode();
        $httpReason = $response->getReasonPhrase();
        $httpStatusMessage = '[HTTP STATUS: ' . $httpStatus . ' - ' . $httpReason . '] ';

        $contents = $response->getBody()->getContents();
        $apiResponse = json_decode($contents);

        if (!is_object($apiResponse)) {
            self::log(LogLevel::ERROR, 'Api Error: Error decoding JSON response ' . $httpStatusMessage);
            return true;
        }

        if ($apiResponse->data === null && $apiResponse->error !== null) {
            self::log(
                LogLevel::ERROR,
                "Api Error: [{$apiResponse->statusCode}] " .
                "{$apiResponse->error} - {$apiResponse->message} " . $httpStatusMessage
            );
            return true;
        }

        if ($httpStatus !== 200) {
            self::log(LogLevel::ERROR, 'Api Error: Bad HTTP status ' . $httpStatusMessage);
            return true;
        }

        return false;
    }
}
