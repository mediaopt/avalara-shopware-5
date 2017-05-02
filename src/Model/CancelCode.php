<?php

namespace Shopware\Plugins\MoptAvalara\Model;

/**
 * Description of CancelCode
 *
 */
class CancelCode extends AbstractModel
{
    const UNSPECIFIED = 'Unspecified';
    const POST_FAILED = 'PostFailed';
    const DOC_DELETED = 'DocDeleted';
    const DOC_VOIDED = 'DocVoided';
    const ADJUSTMENT_CANCELLED = 'AdjustmentCancelled';
}
