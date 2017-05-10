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
            me.attributeForm.loadAttribute(me.get('moptAvalaraHccode'));
            return result;
        },
        getArticleMapping: function () {
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
                        xtype: 'textfield',
                        fieldLabel: 'Harmonized Classification Code (hcCode)',
                        name: 'attribute[moptAvalaraHccode]'
                    }
                ]
            });
        }
    });
//{/block}