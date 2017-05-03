<?php
namespace Shopware\Plugins\MoptAvalara\Model;

class AvalaraAttributes extends AbstractModel
{
    //required
    public $DocType;
    //optional
    public $Commit;
    public $DetailLevel;
    public $ExemptionNo;
    public $PosLaneCode;
    public $Referencecode;
    
    function getDocType()
    {
        return $this->DocType;
    }

    function getCommit()
    {
        return $this->Commit;
    }

    function getDetailLevel()
    {
        return $this->DetailLevel;
    }

    function getExemptionNo()
    {
        return $this->ExemptionNo;
    }

    function getPosLaneCode()
    {
        return $this->PosLaneCode;
    }

    function getReferencecode()
    {
        return $this->Referencecode;
    }

    /**
     * 
     * @param string $DocType
     * @return AvalaraAttributes
     */
    function setDocType($DocType)
    {
        $this->DocType = $DocType;
        return $this;
    }

    /**
     * 
     * @param string $Commit
     * @return AvalaraAttributes
     */
    function setCommit($Commit)
    {
        $this->Commit = $Commit;
        return $this;
    }

    /**
     * 
     * @param string $DetailLevel
     * @return AvalaraAttributes
     */
    function setDetailLevel($DetailLevel)
    {
        $this->DetailLevel = $DetailLevel;
        return $this;
    }

    /**
     * 
     * @param string $ExemptionNo
     * @return AvalaraAttributes
     */
    function setExemptionNo($ExemptionNo)
    {
        $this->ExemptionNo = $ExemptionNo;
        return $this;
    }

    /**
     * 
     * @param string $PosLaneCode
     * @return AvalaraAttributes
     */
    function setPosLaneCode($PosLaneCode)
    {
        $this->PosLaneCode = $PosLaneCode;
        return $this;
    }

    /**
     * 
     * @param string $Referencecode
     * @return AvalaraAttributes
     */
    function setReferencecode($Referencecode)
    {
        $this->Referencecode = $Referencecode;
        return $this;
    }
}