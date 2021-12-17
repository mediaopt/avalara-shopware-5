{extends file="parent:frontend/account/index.tpl"}

{block name="frontend_account_sidebar"}
    {if !$MoptAvalaraIsOneTimeAccount}
        {$smarty.block.parent}
    {/if}
{/block}
