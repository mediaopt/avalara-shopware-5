{extends file="parent:frontend/checkout/cart_footer.tpl"}


{* Basket sum *}
{block name='frontend_checkout_cart_footer_field_labels_sum_value'}
    {if $sBasket.moptAvalaraCustomsDuties > 0.0 }
        <div class="entry--value block">
            {$sBasket.AmountWithoutLandedCost|currency}{s name="Star" namespace="frontend/listing/box_article"}{/s}
        </div>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}
                        
{* Add LandedCost and Incurance line *}
{block name='frontend_checkout_cart_footer_field_labels_shipping'}
    {$smarty.block.parent}
    {if $sBasket.moptAvalaraInsuranceCost > 0.0 }
        <li class="list--entry block-group entry--avalara">
            <div class="entry--label block">
                {s namespace='frontend/MoptAvalara/messages' name='insurance'}{/s}
            </div>

            <div class="entry--value block">
                {$sBasket.moptAvalaraInsuranceCost|currency}{s name="Star" namespace="frontend/listing/box_article"}{/s}
            </div>
        </li>
    {/if}
    
    {if $sBasket.moptAvalaraLandedCost > 0.0 }
        <li class="list--entry block-group entry--avalara">
            <div class="entry--label block">
                {s namespace='frontend/MoptAvalara/messages' name='landedCost'}{/s}
            </div>

            <div class="entry--value block">
                {$sBasket.moptAvalaraLandedCost|currency}{s name="Star" namespace="frontend/listing/box_article"}{/s}
            </div>
        </li>
    {/if}
{/block}
