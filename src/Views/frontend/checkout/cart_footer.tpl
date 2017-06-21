{extends file="parent:frontend/checkout/cart_footer.tpl"}


{* Basket sum *}
{block name='frontend_checkout_cart_footer_field_labels_sum_value'}
    {if $sBasket.moptAvalaraAmountMagnifier > 0.0 }
        {assign var="sBasketAmount" value=($sBasket.Amount - $sBasket.moptAvalaraAmountMagnifier)}
        <div class="entry--value block">
            {$sBasketAmount|currency}{s name="Star" namespace="frontend/listing/box_article"}{/s}
        </div>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}
                        
{* Add LandedCost and Incurance line *}
{block name='frontend_checkout_cart_footer_field_labels_shipping' append}
    {if $sBasket.moptAvalaraInsuranceCost > 0.0 }
        <li class="list--entry block-group entry--dhl">
            <div class="entry--label block">
                {s namespace='frontend/MoptAvalara/messages' name='insurance'}{/s}
            </div>

            <div class="entry--value block">
                {$sBasket.moptAvalaraInsuranceCost|currency}{s name="Star" namespace="frontend/listing/box_article"}{/s}
            </div>
        </li>
    {/if}
    
    {if $sBasket.moptAvalaraLandedCost > 0.0 }
        <li class="list--entry block-group entry--dhl">
            <div class="entry--label block">
                {s namespace='frontend/MoptAvalara/messages' name='landedCost'}{/s}
            </div>

            <div class="entry--value block">
                {$sBasket.moptAvalaraLandedCost|currency}{s name="Star" namespace="frontend/listing/box_article"}{/s}
            </div>
        </li>
    {/if}
{/block}
