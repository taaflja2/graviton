<?php
/**
 * ResponseListener for parsing Accept header
 */

namespace Graviton\RestBundle\Listener;

use Graviton\RestBundle\Event\RestEvent;
use Jaeger\Scope;
use Jaeger\SpanContext;
use const OpenTracing\Formats\HTTP_HEADERS;
use OpenTracing\Span;
use OpenTracing\Tracer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://swisscom.ch
 */
class TracingRequestListener
{

    /**
     * @var Tracer
     */
    private $tracer;

    /**
     * @var Scope
     */
    private $mainSpan;

    /**
     * TracingRequestListener constructor.
     *
     * @param Tracer $tracer tracer
     */
    public function __construct(Tracer $tracer)
    {
        $this->tracer = $tracer;
    }

    /**
     * Validate the json input to prevent errors in the following components
     *
     * @param RestEvent $event Event
     *
     * @return void|null
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest() || $this->mainSpan instanceof Scope) {
            return;
        }

        $parentSpan = $this->extractTracing($event->getRequest());

        $spanOptions = [
            'finish_span_on_close' => true
        ];
        if ($parentSpan instanceof SpanContext) {
            $spanOptions['child_of'] = $parentSpan;
        }

        $this->mainSpan = $this->tracer->startActiveSpan(
            $event->getRequest()->getMethod().' '.$event->getRequest()->getPathInfo(),
            $spanOptions
        );
    }

    /**
     * release the lock
     *
     * @param FilterResponseEvent $event response listener event
     *
     * @return void
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        if ($this->mainSpan instanceof Scope) {
            $this->mainSpan->getSpan()->finish();
            $this->mainSpan->close();
        }

        //$this->tracer->flush();
    }

    /**
     * extract tracing id
     *
     * @param Request $request request
     *
     * @return mixed
     */
    private function extractTracing(Request $request)
    {
        $headers = [];
        foreach ($request->headers->keys() as $headerName) {
            $headers[$headerName] = $request->headers->get($headerName, null);
        }

        return $this->tracer->extract(HTTP_HEADERS, $headers);
    }
}
