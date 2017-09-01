<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright derksen mediaopt GmbH
 */

namespace Shopware\Plugins\MoptAvalara\Service;

use Shopware\Plugins\MoptAvalara\Adapter\AdapterInterface;

/**
 * @author derksen mediaopt GmbH
 * @package Shopware\Plugins\MoptAvalara\Service
 */
abstract class AbstractService
{
    /**
     *
     * @var \Shopware\Plugins\MoptAvalara\Adapter\AdapterInterface
     */
    protected $adapter;
    
    /**
     *
     * @param AdapterInterface $adapter
     */
    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }
    
    /**
     * get adapter
     *
     * @return \Shopware\Plugins\MoptAvalara\Adapter\AdapterInterface
     */
    public function getAdapter()
    {
        return $this->adapter;
    }
}
