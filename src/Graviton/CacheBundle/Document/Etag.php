<?php
/**
 * FilterResponseListener for adding a ETag header.
 */

namespace Graviton\CacheBundle\Document;

/**
 * FilterResponseListener for adding a ETag header.
 *
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://swisscom.ch
 */
class Etag
{
    private $id;

    private $controller;

    private $action;

    private $queryString;

    private $eTag;

    /**
     * get Id
     *
     * @return mixed Id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * set Id
     *
     * @param mixed $id id
     *
     * @return void
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * get Controller
     *
     * @return mixed Controller
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * set Controller
     *
     * @param mixed $controller controller
     *
     * @return void
     */
    public function setController($controller)
    {
        $this->controller = $controller;
    }

    /**
     * get Action
     *
     * @return mixed Action
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * set Action
     *
     * @param mixed $action action
     *
     * @return void
     */
    public function setAction($action)
    {
        $this->action = $action;
    }

    /**
     * get QueryString
     *
     * @return mixed QueryString
     */
    public function getQueryString()
    {
        return $this->queryString;
    }

    /**
     * set QueryString
     *
     * @param mixed $queryString queryString
     *
     * @return void
     */
    public function setQueryString($queryString)
    {
        $this->queryString = $queryString;
    }

    /**
     * get ETag
     *
     * @return mixed ETag
     */
    public function getETag()
    {
        return $this->eTag;
    }

    /**
     * set ETag
     *
     * @param mixed $eTag eTag
     *
     * @return void
     */
    public function setETag($eTag)
    {
        $this->eTag = $eTag;
    }



}
