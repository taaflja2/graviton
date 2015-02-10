<?php
/**
 * Graviton\I18nBundle\Document\Language
 *
 * PHP Version 5
 *
 * @category I18nBundle
 * @package  Graviton
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://swisscom.ch
 */

namespace Graviton\I18nBundle\Document;

/**
 * Graviton\I18nBundle\Document\Language
 *
 * @category I18nBundle
 * @package  Graviton
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://swisscom.ch
 */
class Language implements TranslatableDocumentInterface
{
    /**
     * construct language document
     *
     * @return string[]
     */
    public function getTranslatableFields()
    {
        return array('name');
    }

    /**
     * @var string $id
     */
    protected $id;

    /**
     * @var string $name
     */
    protected $name;

    /**
     * Get id
     *
     * @return string $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id
     *
     * @param string $id language tag value
     *
     * @return void
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Get Name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set Name
     *
     * @param string $name name
     *
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}
