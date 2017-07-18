// {namespace name="backend/mopt_avalara/category/view/category/tabs"}
// {block name="backend/category/view/tabs/settings"}
// {$smarty.block.parent}
Ext.define('Shopware.apps.moptAvalara.Category.view.category.tabs.settings', {
    override: 'Shopware.apps.Category.view.category.tabs.Settings',
    /**
     * @Override
     */
    getItems: function () {
        var me = this;
        var result = me.callParent(arguments);
        result.push(me.getCategoryMapping());
        return result;
    },
    getCategoryMapping: function ()
    {
        var me = this;
        return Ext.create('Ext.form.FieldSet', {
            title: 'Avalara',
            anchor: '100%',
            defaults: me.defaults,
            items: [{
                    xtype: 'textfield',
                    fieldLabel: 'Taxcode',
                    name: 'attribute[moptAvalaraTaxcode]',
                }]
        });
    }
});
// {/block}