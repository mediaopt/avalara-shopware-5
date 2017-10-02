<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright derksen mediaopt GmbH
 */

namespace Shopware\Plugins\MoptAvalara\Logger;

use GuzzleHttp\Message\MessageInterface;
use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Message\ResponseInterface;

/**
 * copy of GuzzleHttp\Subscriber\Log\Formatter with slight changes (thx private properties...)
 *
 * Formats log messages using variable substitutions for requests, responses,
 * and other transactional data.
 *
 * The following variable substitutions are supported:
 *
 * - {request}:      Full HTTP request message
 * - {response}:     Full HTTP response message
 * - {ts}:           Timestamp
 * - {host}:         Host of the request
 * - {method}:       Method of the request
 * - {url}:          URL of the request
 * - {host}:         Host of the request
 * - {protocol}:     Request protocol
 * - {version}:      Protocol version
 * - {resource}:     Resource of the request (path + query + fragment)
 * - {hostname}:     Hostname of the machine that sent the request
 * - {code}:         Status code of the response (if available)
 * - {phrase}:       Reason phrase of the response  (if available)
 * - {error}:        Any error messages (if available)
 * - {req_header_*}: Replace `*` with the lowercased name of a request header to add to the message
 * - {res_header_*}: Replace `*` with the lowercased name of a response header to add to the message
 * - {req_headers}:  Request headers
 * - {res_headers}:  Response headers
 * - {req_body}:     Request body
 * - {res_body}:     Response body
 *
 * @author derksen mediaopt GmbH
 * @package Shopware\Plugins\MoptAvalara\Logger
 */
class Formatter
{

    /**
     * Apache Common Log Format.
     * @var string
     */
    const CLF = '{hostname} {req_header_User-Agent} - [{ts}] "{method} {resource} {protocol}/{version}" {code} {res_header_Content-Length}';
    
    /**
     * Apache Common Log Format.
     * @var string
     */
    const DEBUG = "processing time: {processingTime}s\n>>>>>>>>\n{request}\n<<<<<<<<\n{response}\n--------\n{error}";
    
    /**
     * Apache Common Log Format.
     * @var string
     */
    const SHORT = '[{ts}] "{method} {resource} {protocol}/{version}" {code}';

    /** 
     * @var string Template used to format log messages
     */
    protected $template;
    
    /** 
     * @var string Standart template
     */
    protected $standardTemplate;

    /**
     * @param string $template Log message template
     */
    public function __construct($template = self::CLF)
    {
        $this->setStandardTemplate($template);
        $this->template = $template ? : self::CLF;
    }

    /**
     * public template setter
     * @param string $template
     */
    public function setTemplate($template)
    {
        $this->template = $template;
    }

    /**
     * @return string
     */
    public function getStandardTemplate()
    {
        return $this->standardTemplate;
    }

    /**
     * @param string $standardTemplate
     */
    public function setStandardTemplate($standardTemplate)
    {
        $this->standardTemplate = $standardTemplate;
    }

    /**
     * Will reset a log template
     */
    public function resetTemplate()
    {
        $this->setTemplate($this->getStandardTemplate());
    }

    /**
     * Returns a formatted message
     *
     * @param RequestInterface  $request    Request that was sent
     * @param ResponseInterface $response   Response that was received
     * @param \Exception        $error      Exception that was received
     * @param array             $customData Associative array of custom template data
     *
     * @return string
     */
    public function format(
        RequestInterface $request,
        ResponseInterface $response = null,
        \Exception $error = null,
        array $customData = []
    ) {
    
        $cache = $customData;

        return preg_replace_callback(
            '/{\s*([A-Za-z_\-\.0-9]+)\s*}/',
            function (array $matches) use ($request, $response, $error, &$cache) {

                if (isset($cache[$matches[1]])) {
                    return $cache[$matches[1]];
                }

                    $result = '';
                switch ($matches[1]) {
                    case 'request':
                        $result = $this->maskConfidentialData((string) $request);
                        break;
                    case 'response':
                        $result = $response;
                        break;
                    case 'req_headers':
                        $result = trim($request->getMethod() . ' '
                                . $request->getResource()) . ' HTTP/'
                                . $request->getProtocolVersion() . "\r\n"
                                . $this->headers($request);
                        break;
                    case 'res_headers':
                        $result = $response ?
                        sprintf(
                            'HTTP/%s %d %s',
                            $response->getProtocolVersion(),
                            $response->getStatusCode(),
                            $response->getReasonPhrase()
                        ) . "\r\n" . $this->headers($response) : 'NULL';
                        break;
                    case 'req_body':
                        $result = $request->getBody();
                        break;
                    case 'res_body':
                        $result = $response ? $response->getBody() : 'NULL';
                        break;
                    case 'ts':
                        $result = gmdate('c');
                        break;
                    case 'method':
                        $result = $request->getMethod();
                        break;
                    case 'url':
                        $result = $request->getUrl();
                        break;
                    case 'resource':
                        $result = $request->getResource();
                        break;
                    case 'req_version':
                        $result = $request->getProtocolVersion();
                        break;
                    case 'res_version':
                        $result = $response ? $response->getProtocolVersion() : 'NULL';
                        break;
                    case 'host':
                        $result = $request->getHost();
                        break;
                    case 'hostname':
                        $result = gethostname();
                        break;
                    case 'code':
                        $result = $response ? $response->getStatusCode() : 'NULL';
                        break;
                    case 'phrase':
                        $result = $response ? $response->getReasonPhrase() : 'NULL';
                        break;
                    case 'error':
                        $result = $error ? $error->getMessage() : 'NULL';
                        break;
                    default:
                        // handle prefixed dynamic headers
                        if (strpos($matches[1], 'req_header_') === 0) {
                            $result = $request->getHeader(substr($matches[1], 11));
                        } elseif (strpos($matches[1], 'res_header_') === 0) {
                            $result = $response ? $response->getHeader(substr($matches[1], 11)) : 'NULL';
                        }
                }

                    $cache[$matches[1]] = $result;
                    return $result;
            },
            $this->template
        );
    }

    protected function headers(MessageInterface $message)
    {
        $result = '';
        foreach ($message->getHeaders() as $name => $values) {
            $result .= $name . ': ' . implode(', ', $values) . "\r\n";
        }

        return trim($result);
    }

    protected function maskConfidentialData($string)
    {
        $string = preg_replace('#Authorization: Basic \w+#', 'Authorization: Basic ***', $string);
        return $string;
    }
}
