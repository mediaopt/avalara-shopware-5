<?php
namespace Shopware\Plugins\MoptAvalara\Model;
/**
 * $Id: $
 */
abstract class AbstractModel
{
    /**
     * create array based on current values
     *
     * @return array 
     */
    public function toArray()
    {
        $return = array();

        foreach ($this as $key => $value) {
            if (!is_array($value)) {
                $return[$key] = $value;
            } else {
                $return[$key] = array();
                foreach ($value as $element) {
                    $return[$key][] = $element->toArray();
                }
            }
        }
        return $return;
    }
    
    /**
     * to string
     * @return string
     */
    public function __toString()
    {
        return json_encode($this->toArray());
    }
}
