// {namespace name="backend/mopt_avalara/order/view/order/detail"}
// {block name="backend/order/view/detail/window"}
// {$smarty.block.parent}
Ext.define('Shopware.apps.moptAvalara.Order.view.order.tabs.avalara_tab', {
    override: 'Shopware.apps.Order.view.detail.Window',
    extend: 'Ext.form.Panel',
    /**
     * @Override
     * Creates the main tab panel which displays the different tabs for the order sections.
     * To extend the tab panel this function can be override.
     *
     * @return Ext.tab.Panel
     */
    createTabPanel: function () {
        var me = this, result;
        result = me.callParent(arguments);
        result.add(me.createAvalaraTab());
        return result;
    },
    createAvalaraTab: function () {
        var me = this;
        var exemptionCode = '';
        var userId = me.record.getCustomerStore.first().get('id');
        var docCode = '';
        Ext.Ajax.request({
            url: '{url controller=AttributeData action=loadData}',
            params: {
                _foreignKey: me.record.get('id'),
                _table: 's_order_attributes'
            },
            async: false,
            success: function(responseData, request) {
                var response = Ext.JSON.decode(responseData.responseText);
                docCode = response.data.__attribute_mopt_avalara_doc_code;
            }
        });

        Ext.Ajax.request({
            url: '{url controller=AttributeData action=loadData}',
            params: {
                _foreignKey: userId,
                _table: 's_user_attributes'
            },
            async: false,
            success: function(responseData, request) {
                var response = Ext.JSON.decode(responseData.responseText);
                exemptionCode = response.data.__attribute_mopt_avalara_exemption_code;
            }
        });

        return Ext.create('Ext.container.Container', {
            defaults: me.defaults,
            title: 'Avalara',
            layout: {
                type: 'hbox'
            },
            style: 'padding: 5px',
            items: [
                Ext.create('Ext.form.Panel', {
                    title: 'Information',
                    items: [
                        {
                            xtype: 'displayfield',
                            fieldLabel: 'Exemption code',
                            value: exemptionCode ? exemptionCode : '-',
                            labelWidth: 130
                        },
                        {
                            xtype: 'displayfield',
                            fieldLabel: 'Document code',
                            value: docCode ? docCode : '-',
                            labelWidth: 130
                        }
                    ],
                    width: '45%',
                    height: 130,
                    style: 'margin-right: 10px;',
                    bodyPadding: '5px 10px'
                }),
                Ext.create('Ext.form.Panel', {
                    title: 'Actions',
                    items: [
                        {
                            xtype: 'button',
                            text: 'Cancel Tax',
                            width: 130,
                            handler: function () {
                                Ext.Ajax.request({
                                    url: '{url controller="MoptAvalara" action="cancelOrder"}',
                                    method: 'POST',
                                    params: { id: me.record.get('id')},
                                    headers: { 'Accept': 'application/json'},
                                    success: function (response)
                                    {
                                        var jsonData = Ext.JSON.decode(response.responseText);
                                        Shopware.Notification.createGrowlMessage(
                                                (jsonData.success ? '{s name=success}success{/s}' : '{s name=error}error{/s}'), 
                                                jsonData.message, 
                                                'Avalara');
                                    }

                                });
                            }
                        },
                        {
                            xtype: 'button',
                            text: 'Update order',
                            width: 130,
                            handler: function () {
                                Ext.Ajax.request({
                                    url: '{url controller="MoptAvalara" action="updateOrder"}',
                                    method: 'POST',
                                    params: { id: me.record.get('id')},
                                    headers: { 'Accept': 'application/json'},
                                    success: function (response)
                                    {
                                        var jsonData = Ext.JSON.decode(response.responseText);
                                        Shopware.Notification.createGrowlMessage(
                                                (jsonData.success ? '{s name=success}success{/s}' : '{s name=error}error{/s}'), 
                                                jsonData.message, 
                                                'Avalara');
                                    }

                                });
                            }
                        },
                        {
                            xtype: 'button',
                            text: 'Reset update flag',
                            width: 130,
                            handler: function () {
                                Ext.Ajax.request({
                                    url: '{url controller="MoptAvalara" action="resetUpdateFlag"}',
                                    method: 'POST',
                                    params: { id: me.record.get('id')},
                                    headers: { 'Accept': 'application/json'},
                                    success: function (response)
                                    {
                                        var jsonData = Ext.JSON.decode(response.responseText);
                                        Shopware.Notification.createGrowlMessage(
                                                (jsonData.success ? '{s name=success}success{/s}' : '{s name=error}error{/s}'), 
                                                jsonData.message, 
                                                'Avalara');
                                    }

                                });
                            }
                        }
                    ],
                    width: '45%',
                    height: 130,
                    bodyPadding: '5px 10px',
                    layout: {
                        type: 'vbox',
                        defaultMargins: '5 0'
                    }
                })
            ]
        });
    }
});
// {/block}
