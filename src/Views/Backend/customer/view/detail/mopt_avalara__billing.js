//{namespace name="backend/mopt_avalara/customer/view/customer/detail"}
//{block name="backend/customer/view/detail/billing" append}
Ext.define('Shopware.apps.moptAvalara.Customer.view.detail.Billing', {
    override: 'Shopware.apps.Customer.view.detail.Billing',
    extend: 'Ext.form.field.Text',
    /**
     * @Override
     */
    createBillingFormRight:function () {
        var me = this;
        var result = me.callParent(arguments);
        result.push(me.getExemptionCodeMapping());
        return result;
    },
    getExemptionCodeMapping: function ()
    {
        return Ext.create('Ext.form.field.Text', {
            fieldLabel : 'Avalara Exemption Code',
            name : 'attribute[moptAvalaraExemptionCode]',
            labelWidth: 155,
            anchor: '100%'
        });
    }
});
//{/block}