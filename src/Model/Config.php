<?php

namespace Shopware\Plugins\MoptAvalara\Model;

/**
 * Description of Config
 *
 */
class Config extends AbstractModel
{
    public $IsLiveMode;
    public $ApiUsername;
    public $ApiPasswort;
    
    public function getIsLiveMode()
    {
        return $this->IsLiveMode;
    }

    public function setIsLiveMode($isLiveMode)
    {
        $this->IsLiveMode = $isLiveMode;
    }
    
    public function getApiUsername()
    {
        return $this->ApiUsername;
    }

    public function getApiPasswort()
    {
        return $this->ApiPasswort;
    }

    public function setApiUsername($ApiUsername)
    {
        $this->ApiUsername = $ApiUsername;
    }

    public function setApiPasswort($ApiPasswort)
    {
        $this->ApiPasswort = $ApiPasswort;
    }


}
