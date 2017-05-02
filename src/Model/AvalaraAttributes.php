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

    function setDocType($DocType)
    {
        $this->DocType = $DocType;
    }

    function setCommit($Commit)
    {
        $this->Commit = $Commit;
    }

    function setDetailLevel($DetailLevel)
    {
        $this->DetailLevel = $DetailLevel;
    }

    function setExemptionNo($ExemptionNo)
    {
        $this->ExemptionNo = $ExemptionNo;
    }

    function setPosLaneCode($PosLaneCode)
    {
        $this->PosLaneCode = $PosLaneCode;
    }

    function setReferencecode($Referencecode)
    {
        $this->Referencecode = $Referencecode;
    }
}