{extends file="parent:frontend/checkout/confirm.tpl"}

{block name='frontend_checkout_confirm_left_payment_method'}
    {$smarty.block.parent}
    {if $MoptAvalaraLandedCost}
        <p class="payment--method-info">
            <strong class="payment--title">{s namespace='frontend/MoptAvalara/messages' name='landedCost'}{/s}: </strong>
            <span class="payment--description">{$MoptAvalaraLandedCost}</span>
        </p>
    {/if}
{/block}
