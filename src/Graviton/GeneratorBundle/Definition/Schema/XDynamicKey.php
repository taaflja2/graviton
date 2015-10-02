<?php
/**
 * Part of JSON definition: "target.fields.x-dynamic-key"
 */
namespace Graviton\GeneratorBundle\Definition\Schema;

/**
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://swisscom.ch
 */
class XDynamicKey
{
    /**
     * @var string
     */
    private $documentId;

    /**
     * @var string
     */
    private $repositoryMethod;

    /**
     * @var string
     */
    private $refMethod = 'getRef';

    /**
     * @return string
     */
    public function getDocumentId()
    {
        return $this->documentId;
    }

    /**
     * @param string $documentId document-id field
     * @return $this
     */
    public function setDocumentId($documentId)
    {
        $this->documentId = $documentId;
        return $this;
    }

    /**
     * @return string
     */
    public function getRepositoryMethod()
    {
        return $this->repositoryMethod;
    }

    /**
     * @param string $repositoryMethod repository-method field
     * @return $this
     */
    public function setRepositoryMethod($repositoryMethod)
    {
        $this->repositoryMethod = $repositoryMethod;
        return $this;
    }

    /**
     * @return string
     */
    public function getRefMethod()
    {
        return $this->refMethod;
    }

    /**
     * @param string $refMethod reference the dynamic field is resolved through
     * @return $this
     */
    public function setRefMethod($refMethod)
    {
        $this->refMethod = $refMethod;
        return $this;
    }
}
