{extends file="parent:frontend/account/order_item_details.tpl"}

{* Shipping costs label *}
{block name="frontend_account_order_item_detail_shipping_costs_label"}
    {$smarty.block.parent}
    {if $offerPosition.moptAvalaraInsurance > 0.0 }
        <p class="is--strong is--nowrap">
            {s namespace='frontend/MoptAvalara/messages' name='insurance'}{/s}:
        </p>
    {/if}
    {if $offerPosition.moptAvalaraLandedCost > 0.0 }
        <p class="is--strong is--nowrap">
            {s namespace='frontend/MoptAvalara/messages' name='landedCost'}{/s}:
        </p>
    {/if}
{/block}

{* Shipping costs *}
{block name="frontend_account_order_item_shippingamount"}
    {$smarty.block.parent}
    {if $offerPosition.moptAvalaraInsurance > 0.0}
        <p class="is--strong">
            {if $offerPosition.currency_position == "32"}
                {$offerPosition.currency_html} {$offerPosition.moptAvalaraInsurance}
            {else}
                {$offerPosition.moptAvalaraInsurance} {$offerPosition.currency_html}
            {/if}
        </p>
    {/if}
    {if $offerPosition.moptAvalaraLandedCost > 0.0}
        <p class="is--strong">
            {if $offerPosition.currency_position == "32"}
                {$offerPosition.currency_html} {$offerPosition.moptAvalaraLandedCost}
            {else}
                {$offerPosition.moptAvalaraLandedCost} {$offerPosition.currency_html}
            {/if}
        </p>
    {/if}
{/block}