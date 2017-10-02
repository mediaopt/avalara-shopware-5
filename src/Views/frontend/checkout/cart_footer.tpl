{extends file="parent:frontend/checkout/cart_footer.tpl"}

{* Add LandedCost and Incurance line *}
{block name='frontend_checkout_cart_footer_field_labels_shipping'}
    {$smarty.block.parent}
    {if $moptAvalaraInsuranceCost > 0.0 }
        <li class="list--entry block-group entry--avalara">
            <div class="entry--label block">
                {s namespace='frontend/MoptAvalara/messages' name='insurance'}{/s}
            </div>

            <div class="entry--value block">
                {$moptAvalaraInsuranceCost|currency}{s name="Star" namespace="frontend/listing/box_article"}{/s}
            </div>
        </li>
    {/if}
    
    {if $moptAvalaraLandedCost > 0.0 }
        <li class="list--entry block-group entry--avalara">
            <div class="entry--label block">
                {s namespace='frontend/MoptAvalara/messages' name='landedCost'}{/s}
            </div>

            <div class="entry--value block">
                {$moptAvalaraLandedCost|currency}{s name="Star" namespace="frontend/listing/box_article"}{/s}
            </div>
        </li>
    {/if}
{/block}
