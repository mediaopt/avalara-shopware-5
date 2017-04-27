//{namespace name="backend/mopt_avlara/article/view/article/detail"}
//{block name="backend/article/view/detail/base" append}
    Ext.define('Shopware.apps.moptAvalara.Article.view.detail.Base', {
        override: 'Shopware.apps.Article.view.detail.Base',
        extend: 'Ext.form.field.Text',
        /**
         * @Override
         */
        createRightElements: function () {
            var me = this;
            var result = me.callParent(arguments);
            result.push(me.getArticleMapping());
            me.attributeForm = Ext.create('Shopware.attribute.Form', {
                table: 's_articles_attributes'
            });
            me.attributeForm.loadAttribute(me.get('moptAvalaraTaxcode'));
            return result;
        },
        getArticleMapping: function () {
            return Ext.create('Ext.form.field.Text', {
                fieldLabel: 'Avalara Taxcode',
                name: 'attribute[moptAvalaraTaxcode]',
                labelWidth: 155,
                anchor: '100%'
            });
        }
    });
//{/block}