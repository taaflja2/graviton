<?php
/**
 * FilterResponseListener for adding a ETag header.
 */

namespace Graviton\CacheBundle\Listener;

use Doctrine\ODM\MongoDB\DocumentRepository;
use Graviton\CacheBundle\Document\Etag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * FilterResponseListener for adding a ETag header.
 *
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://swisscom.ch
 */
class ETagResponseListener
{

    /**
     * @var DocumentRepository
     */
    private $repository;

    public function __construct(
        DocumentRepository $repository
    )
    {
        $this->repository = $repository;
    }

    /**
     * add a ETag header to the response
     *
     * @param FilterResponseEvent $event response listener event
     *
     * @return void
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if ($event->getRequest()->getMethod() != Request::METHOD_GET) {
            // only do on GET
            return;
        }

        $controller = $event->getRequest()->attributes->get('_controller', null);
        $queryString = $event->getRequest()->server->get('QUERY_STRING', null);

        /**
         * the "W/" prefix is necessary to qualify it as a "weak" Etag.
         * only then a proxy like nginx will leave the tag alone because a strong cannot
         * match if gzip is applied.
         */
        $eTag = 'W/'.sha1($event->getResponse()->getContent());

        if ($controller !== null) {

            $controllerParts = explode(':', $controller);

            $eTagId = sha1($controller.'-'.(string)$queryString);

            $eTagObj = new Etag();
            $eTagObj->setId($eTagId);
            $eTagObj->setController($controllerParts[0]);
            $eTagObj->setAction($controllerParts[1]);
            $eTagObj->setQueryString(sha1($queryString));
            $eTagObj->setETag($eTag);

            $this->repository->getDocumentManager()->persist($eTagObj);
            $this->repository->getDocumentManager()->flush($eTagObj);
        }

        $event->getResponse()->headers->set('ETag', $eTag);
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        if ($event->getRequest()->getMethod() != Request::METHOD_GET) {
            // only do on GET
            return;
        }

        $ifNoneMatch = $event->getRequest()->headers->get('if-none-match', null);
        if (null === $ifNoneMatch) {
            return;
        }

        $eTagObj = $this->getBasicEtagObject($event->getRequest());
        if (null === $eTagObj) {
            return;
        }

        $savedEtag = $this->getSavedEtag($eTagObj);

        if ($savedEtag instanceof Etag && $savedEtag->getETag() == $ifNoneMatch) {
            echo "matched!"; die;
        }

    }

    private function getBasicEtagObject(Request $request)
    {
        $controller = $request->attributes->get('_controller', null);
        $queryString = $request->server->get('QUERY_STRING', null);

        if ($controller === null) {
            return null;
        }

        $controllerParts = explode(':', $controller);
        $eTagId = sha1($controller.'-'.(string)$queryString);

        $eTagObj = new Etag();
        $eTagObj->setId($eTagId);
        $eTagObj->setController($controllerParts[0]);
        $eTagObj->setAction($controllerParts[1]);
        $eTagObj->setQueryString(sha1($queryString));

        return $eTagObj;
    }

    private function getSavedEtag(Etag $eTagObj)
    {
        return $this->repository->findOneBy(['_id' => $eTagObj->getId()]);
    }
}
