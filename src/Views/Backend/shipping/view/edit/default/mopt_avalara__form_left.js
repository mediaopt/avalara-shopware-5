//{namespace name="backend/mopt_avalara/shipping/view/shipping/edit/default"}
//{block name="backend/shipping/view/edit/default/form_left" append}
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
        var me = this;
        return Ext.create('Ext.form.FieldSet', {
            title: 'Avalara',
            anchor: '100%',
            defaults: me.defaults,
            items: [{
                    xtype: 'textfield',
                    fieldLabel: 'Taxcode',
                    name: 'attribute[moptAvalaraTaxcode]'
                },
                {
                    xtype: 'checkbox',
                    fieldLabel: 'Express',
                    name: 'attribute[moptAvalaraExpressShipping]'
                },
                {
                    xtype: 'checkbox',
                    fieldLabel: 'Insured 100%',
                    name: 'attribute[moptAvalaraInsured]'
                }
            ]
        });
    }
});
//{/block}