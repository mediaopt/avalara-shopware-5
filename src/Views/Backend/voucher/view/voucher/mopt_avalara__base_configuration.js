// {namespace name="backend/mopt_avalara/voucher/view/voucher/base_configuration"}
// {block name="backend/voucher/view/voucher/base_configuration"}
// {$smarty.block.parent}
Ext.define('Shopware.apps.moptAvalara.Voucher.view.voucher.BaseConfiguration', {
    override: 'Shopware.apps.Voucher.view.voucher.BaseConfiguration',
    extend: 'Ext.form.field.Text',
    /**
     * @Override
     */
    createRestrictionFormRight:function () {
        var me = this;
        var result = me.callParent(arguments);
        result.push(me.getTaxCodeMapping());
        return result;
    },
    getTaxCodeMapping: function ()
    {
        return Ext.create('Ext.form.field.Text', {
            fieldLabel : 'Avalara Tax-Code',
            name : 'attribute[moptAvalaraTaxcode]',
            labelWidth: 180,
            helpText: 'Attention: set voucher tax-configuration to "standard".'
        });
    }
});
// {/block}
