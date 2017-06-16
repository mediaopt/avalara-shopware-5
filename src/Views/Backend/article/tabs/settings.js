//{namespace name="backend/mopt_avlara/article/view/article/detail"}
//{block name="backend/article/view/detail/base" append}
Ext.define('Shopware.apps.moptAvalara.Article.view.detail.Base', {
    override: 'Shopware.apps.Article.view.detail.Base',
    /**
     * @Override
     */
    createRightElements: function ()
    {
        var me = this;
        var result = me.callParent(arguments);
        result.push(me.getArticleMapping());
        return result;
    },
    getArticleMapping: function ()
    {
        var me = this;
        return Ext.create('Ext.form.FieldSet', {
            title: 'Avalara',
            anchor: '100%',
            defaults: me.defaults,
            items: [{
                    xtype: 'textfield',
                    fieldLabel: 'Taxcode',
                    name: '__attribute_mopt_avalara_taxcode'
                },
                {
                    xtype: 'textfield',
                    fieldLabel: 'Harmonized Classification Code (hsCode)',
                    name: '__attribute_mopt_avalara_hscode'
                }
            ]
        });
    }
});
//{/block}