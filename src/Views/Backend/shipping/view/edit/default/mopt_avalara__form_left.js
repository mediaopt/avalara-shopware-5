// {namespace name="backend/mopt_avalara/shipping/view/shipping/edit/default"}
// {block name="backend/shipping/view/edit/default/form_left"}
// {$smarty.block.parent}
Ext.define('Shopware.apps.moptAvalara.Shipping.view.edit.default.FormLeft', {
    override: 'Shopware.apps.Shipping.view.edit.default.FormLeft',
    extend: 'Ext.form.field.Text',
    /**
     * @Override
     */
    getFormElements: function () {
        var me = this;
        var result = me.callParent(arguments);
        result.push(me.getShippingMapping());
        return result;
    },
    getShippingMapping: function ()
    {
        return Ext.create('Ext.form.field.Text', {
            fieldLabel : 'Avalara Taxcode',
            name : 'attribute[moptAvalaraTaxcode]',
            labelWidth: 155,
            anchor: '100%'
        });
    }
});
// {/block}