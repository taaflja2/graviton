<?php
/**
 * ProxyController
 */

namespace Graviton\ProxyBundle\Controller;

use Graviton\ExceptionBundle\Exception\NotFoundException;
use Graviton\ProxyBundle\Exception\TransformationException;
use Graviton\ProxyBundle\Service\ApiDefinitionLoader;
use Graviton\ProxyBundle\Service\TransformationHandler;
use Graviton\CommonBundle\Component\HttpProxy\Proxy;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use Laminas\Diactoros\Uri;
use Psr\Http\Message\UriInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * general controller for all proxy staff
 *
 * @package Graviton\ProxyBundle\Controller
 * @author  List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    http://swisscom.ch
 */
class ProxyController
{
    /**
     * @var Proxy
     */
    private $proxy;

    /**
     * @var PsrHttpFactory
     */
    private $psrHttpFactory;

    /**
     * @var ApiDefinitionLoader
     */
    private $apiLoader;

    /**
     * @var HttpFoundationFactory
     */
    private $httpFoundationFactory;

    /**
     * @var array
     */
    private $proxySourceConfiguration;

    /**
     * @var TransformationHandler
     */
    private $transformationHandler;

    /**
     * Constructor
     *
     * @param Proxy                 $proxy                    proxy
     * @param ApiDefinitionLoader   $loader                   definition loader
     * @param PsrHttpFactory        $psrHttpFactory           convert HttpFoundation objects to PSR-7
     * @param HttpFoundationFactory $httpFoundationFactory    convert PSR-7 interfaces to HttpFoundation
     * @param TransformationHandler $transformationHandler    transformation handler
     * @param array                 $proxySourceConfiguration Set of sources to be recognized by the controller.
     */
    public function __construct(
        Proxy $proxy,
        ApiDefinitionLoader $loader,
        PsrHttpFactory $psrHttpFactory,
        HttpFoundationFactory $httpFoundationFactory,
        TransformationHandler $transformationHandler,
        array $proxySourceConfiguration
    ) {
        $this->proxy = $proxy;
        $this->apiLoader = $loader;
        $this->psrHttpFactory = $psrHttpFactory;
        $this->httpFoundationFactory = $httpFoundationFactory;
        $this->proxySourceConfiguration = $proxySourceConfiguration;
        $this->transformationHandler = $transformationHandler;
    }

    /**
     * action for routing all requests directly to the third party API
     *
     * @param Request $request request
     *
     * @return \Psr\Http\Message\ResponseInterface|Response
     */
    public function proxyAction(Request $request)
    {
        $api = $this->decideApiAndEndpoint($request->getUri());
        $this->registerProxySources($api['apiName']);
        $this->apiLoader->addOptions($api);

        $url = new Uri($this->apiLoader->getEndpoint($api['endpoint'], true));

        if ($url->getScheme() === null) {
            $url = $url->withScheme($request->getScheme());
        }

        $response = null;
        try {
            $newRequest = Request::create(
                (string) $url,
                $request->getMethod(),
                [],
                [],
                [],
                [],
                $request->getContent(false)
            );
            $newRequest->headers->add($request->headers->all());
            $newRequest->query->add($request->query->all());

            $newRequest = $this->transformationHandler->transformRequest(
                $api['apiName'],
                $api['endpoint'],
                $request,
                $newRequest
            );

            $psrRequest = $this->psrHttpFactory->createRequest($newRequest);

            $psrRequestUri = $psrRequest->getUri();
            $psrRequestUriPort = $url->getPort();
            if (is_numeric($psrRequestUriPort) || is_null($psrRequestUriPort)) {
                $psrRequestUri = $psrRequestUri->withPort($psrRequestUriPort);
            }

            $psrRequest = $psrRequest->withUri($psrRequestUri);

            $psrResponse = $this->proxy->forward($psrRequest)->to($this->getHostWithScheme($url));
            $response = $this->httpFoundationFactory->createResponse($psrResponse);
            $this->cleanResponseHeaders($response->headers);
            $this->transformationHandler->transformResponse(
                $api['apiName'],
                $api['endpoint'],
                $response,
                clone $response
            );
        } catch (ClientException $e) {
            $response = (new HttpFoundationFactory())->createResponse($e->getResponse());
        } catch (ServerException $serverException) {
            $response = (new HttpFoundationFactory())->createResponse($serverException->getResponse());
        } catch (TransformationException | RequestException $e) {
            $message = json_encode(
                ['code' => 404, 'message' => 'HTTP 404 Not found']
            );

            throw new NotFoundHttpException($message, $e);
        }

        return $response;
    }

    /**
     * Removes some headers from the thirdparty API's response. These headers get always invalid by graviton's
     * forwarding and should therefore not be delivered to the client.
     *
     * @param HeaderBag $headers The headerbag holding the thirdparty API's response headers
     *
     * @return void
     */
    protected function cleanResponseHeaders(HeaderBag $headers)
    {
        $headers->remove('transfer-encoding'); // Chunked responses get not automatically re-chunked by graviton
        $headers->remove('trailer'); // Only for chunked responses, graviton should re-set this when chunking

        // Change naming to Graviton output Allow options
        if ($allow = $headers->get('Allow', false)) {
            $headers->set('Access-Control-Allow-Methods', $allow);
            $headers->remove('Allow');
        }
    }

    /**
     * get schema info
     *
     * @param Request $request request
     *
     * @return Response
     */
    public function schemaAction(Request $request)
    {
        $api = $this->decideApiAndEndpoint($request->getUri());
        $this->registerProxySources($api['apiName']);
        $this->apiLoader->addOptions($api);

        $schema = $this->apiLoader->getEndpointSchema(urldecode($api['endpoint']));
        $schema = $this->transformationHandler->transformSchema(
            $api['apiName'],
            $api['endpoint'],
            $schema,
            clone $schema
        );

        return new JsonResponse($schema, 200);
    }

    /**
     * get API name and endpoint from the url (third party API)
     *
     * @param string $url the url
     *
     * @return array
     */
    protected function decideApiAndEndpoint($url)
    {
        $path = parse_url($url, PHP_URL_PATH);

        $pattern = array (
            "@schema\/@",
            "@\/3rdparty\/@",
            "@\/item$@",
        );
        $path = preg_replace($pattern, '', $path);

        //get api name and endpoint
        $apiName = substr($path, 0, strpos($path, '/'));
        $endpoint = str_replace($apiName, '', $path);

        return array (
            "apiName" => $apiName,
            "endpoint" => $endpoint,
        );
    }

    /**
     * Registers configured external services to be proxied.
     *
     * @param string $apiPrefix The prefix of the API
     *
     * @return void
     */
    private function registerProxySources($apiPrefix = '')
    {
        foreach (array_keys($this->proxySourceConfiguration) as $source) {
            foreach ($this->proxySourceConfiguration[$source] as $config) {
                if ($apiPrefix == $config['prefix']) {
                    $this->apiLoader->setOption($config);
                    return;
                }
            }
        }

        $e = new NotFoundException('No such thirdparty API.');
        $e->setResponse(Response::create());
        throw $e;
    }

    /**
     * get host, scheme and port
     *
     * @param string $url the url
     *
     * @return string
     */
    private function getHostWithScheme(UriInterface $url)
    {
        $target = new Uri();
        $target = $target
            ->withScheme($url->getScheme())
            ->withHost($url->getHost())
            ->withPort($url->getPort());

        return (string) $target;
    }
}
