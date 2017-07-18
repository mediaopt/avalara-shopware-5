{extends file="parent:frontend/address/form.tpl"}

{block name='frontend_address_form_input_zip_and_city'}
    {if $MoptAvalaraAddressChanges['PostalCode'] || $MoptAvalaraAddressChanges['City']}
    <div class="mopt_avalara__address_change">
        <span class="zipcode">
            {if $MoptAvalaraAddressChanges['PostalCode']}{$sUserDataOld['zipcode']}{/if}
            {if $MoptAvalaraAddressChanges['City']}{$sUserDataOld['city']}{/if}
        </span>
    </div>
    {/if}
    {$smarty.block.parent}
{/block}

{block name='frontend_address_form_input_street'}
    {if $MoptAvalaraAddressChanges['Line1']}
    <div class="mopt_avalara__address_change">
        <span class="zipcode">
            {$sUserDataOld['street']}
        </span>
    </div>
    {/if}
    {$smarty.block.parent}
{/block}