<?php
namespace Graviton\RestBundle\Mapper;

/**
 * EntityService
 *
 * @category GravitonRestBundle
 * @package  Graviton
 * @author   Manuel Kipfer <manuel.kipfer@swisscom.com>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://swisscom.com
 */
class EntityService
{
    private $arrMap = array();
    private $router;
    
    public function __construct($router)
    {
        $this->router = $router;
    }
    
    public function add($id, $value)
    {
        $this->arrMap[$id] = $value;
        
        return $this;
    }
    
    public function remove($id)
    {
        unset($this->arrMap[$id]);
        
        return $this;
    }
    
    public function match($id, $byValue = false)
    {
        $retVal = false;
        
        if (!$byValue) {
            $retVal = $this[$id];
        } else {
            $retVal = array_find($id);
        }
        
        return $retVal;
    }
    
    public function generateUrl($entity, $urlParams)
    {
        $service = $this->arrMap[$id].'_'.strtolower($action);
        $url = $this->router->generate($service, $urlParams);
        
        return $this->$url;
    }
}
