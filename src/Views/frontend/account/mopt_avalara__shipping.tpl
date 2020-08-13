{extends file="parent:frontend/address/edit.tpl"}

{block name="frontend_address_action_buttons"}
    {if $MoptAvalaraAddressChanges}
        <div class="actions">
            {if !$MoptAvalaraAddressHidden}
                <a class="btn is--primary button-left large left" href="{url controller='checkout' action='confirm'}" title="{s namespace='frontend/MoptAvalara/messages' name='shippingAddressDiscardChanges'}{/s}">
                    {s namespace='frontend/MoptAvalara/messages' name='shippingAddressDiscardChanges'}{/s}
                </a>
            {/if}
            <input type="submit" value="{s namespace='frontend/account/shipping' name='ShippingLinkSend'}{/s}" class="btn is--primary button-right large right" />
        </div>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}
