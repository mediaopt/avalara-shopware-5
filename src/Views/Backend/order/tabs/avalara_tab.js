// {namespace name="backend/mopt_avalara/order/view/order/detail"}
// {block name="backend/order/view/detail/window"}
// {$smarty.block.parent}
Ext.define('Shopware.apps.moptAvalara.Order.view.order.tabs.avalara_tab', {
    override: 'Shopware.apps.Order.view.detail.Window',
    extend: 'Ext.form.Panel',
    
    /**
     * Contains all snippets for the view component
     * @object
     */
    avalaraSnippets: {
        resetUpdateFlag: "{s namespace='frontend/MoptAvalara/messages' name='resetUpdateFlag'}Reset update flag{/s}",
        commitOrder: "{s namespace='frontend/MoptAvalara/messages' name='commitOrder'}Commit order to Avalara{/s}",
        cancelCommitOrder: "{s namespace='frontend/MoptAvalara/messages' name='cancelCommitOrder'}Cancel Tax{/s}",
        actions: "{s namespace='frontend/MoptAvalara/messages' name='actions'}Actions{/s}",
        information: "{s namespace='frontend/MoptAvalara/messages' name='information'}Information{/s}",
        docCode: "{s namespace='frontend/MoptAvalara/messages' name='docCode'}Document code{/s}",
        transactionStatus: "{s namespace='frontend/MoptAvalara/messages' name='transactionStatus'}Transaction status{/s}",
        incoterms: "{s namespace='frontend/MoptAvalara/messages' name='incoterms'}Incoterms{/s}",
        landedCost: "{s namespace='frontend/MoptAvalara/messages' name='landedCost'}Customs duties and fees{/s}",
        insurance: "{s namespace='frontend/MoptAvalara/messages' name='insurance'}Insurance{/s}",
        exemptionCode: "{s namespace='frontend/MoptAvalara/messages' name='exemptionCode'}Exemption code{/s}",
        success: '{s name=success}success{/s}',
        error: '{s name=error}error{/s}'
    },
    
    avalaraUrls: {
        loadData: '{url controller=AttributeData action=loadData}',
        resetUpdateFlag: '{url controller=MoptAvalara action=resetUpdateFlag}',
        commitOrder: '{url controller=MoptAvalara action=commitOrder}',
        cancelOrder: '{url controller=MoptAvalara action=cancelOrder}'
    },
    
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
        var me = this,
            exemptionCode = '',
            userId = me.record.getCustomerStore.first().get('id'),
            docCode = '',
            transactionType = '',
            incoterms = '',
            landedCost = 0,
            insurance = 0
        ;
        Ext.Ajax.request({
            url: me.avalaraUrls.loadData,
            params: {
                _foreignKey: me.record.get('id'),
                _table: 's_order_attributes'
            },
            async: false,
            success: function(responseData, request) {
                var response = Ext.JSON.decode(responseData.responseText);
                docCode = response.data.__attribute_mopt_avalara_doc_code;
                transactionType = response.data.__attribute_mopt_avalara_transaction_type;
                incoterms = response.data.__attribute_mopt_avalara_incoterms;
                landedCost = response.data.__attribute_mopt_avalara_landedcost;
                insurance = response.data.__attribute_mopt_avalara_insurance;
            }
        });

        Ext.Ajax.request({
            url: me.avalaraUrls.loadData,
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
        
        var actionPanel = me.createActionPanel(transactionType);
        var itemsArray = [
            me.createInformationPanel(exemptionCode, transactionType, incoterms, docCode, landedCost, insurance)
        ];
        if (actionPanel) {
            itemsArray.push(actionPanel);
        }
        
        return Ext.create('Ext.container.Container', {
            defaults: me.defaults,
            title: 'Avalara',
            layout: {
                type: 'hbox'
            },
            style: 'padding: 5px',
            items: itemsArray
        });
    },
    
    createActionPanel: function (transactionType) {
        if (null === transactionType) {
            return null;
        }
        var me = this;
        var items = [{
            xtype: 'button',
            text: me.avalaraSnippets.resetUpdateFlag,
            width: 130,
            handler: function () {
                Ext.Ajax.request({
                    url: me.avalaraUrls.resetUpdateFlag,
                    method: 'POST',
                    params: { id: me.record.get('id')},
                    headers: { 'Accept': 'application/json'},
                    success: function (response)
                    {
                        var jsonData = Ext.JSON.decode(response.responseText);
                        Shopware.Notification.createGrowlMessage(
                                jsonData.success ? me.avalaraSnippets.success : me.avalaraSnippets.error, 
                                jsonData.message, 
                                'Avalara');
                    }

                });
            }
        }];

        switch (transactionType) {
            case 'SalesOrder':
            case '0':
                items.push({
                    xtype: 'button',
                    text: me.avalaraSnippets.commitOrder,
                    width: 130,
                    handler: function () {
                        Ext.Ajax.request({
                            url: me.avalaraUrls.commitOrder,
                            method: 'POST',
                            params: { id: me.record.get('id')},
                            headers: { 'Accept': 'application/json'},
                            success: function (response)
                            {
                                var jsonData = Ext.JSON.decode(response.responseText);
                                Shopware.Notification.createGrowlMessage(
                                        jsonData.success ? me.avalaraSnippets.success : me.avalaraSnippets.error,
                                        jsonData.message, 
                                        'Avalara');
                            }

                        });
                    }
                });
                break;
                
            case 'SalesInvoice':
            case '1':
                items.push({
                    xtype: 'button',
                    text: me.avalaraSnippets.cancelCommitOrder,
                    width: 130,
                    handler: function () {
                        Ext.Ajax.request({
                            url: me.avalaraUrls.cancelOrder,
                            method: 'POST',
                            params: { id: me.record.get('id')},
                            headers: { 'Accept': 'application/json'},
                            success: function (response)
                            {
                                var jsonData = Ext.JSON.decode(response.responseText);
                                Shopware.Notification.createGrowlMessage(
                                        jsonData.success ? me.avalaraSnippets.success : me.avalaraSnippets.error,
                                        jsonData.message, 
                                        'Avalara');
                            }

                        });
                    }
                });
                break;
            
            case 'DocVoided':
                break;
                
            default: 
                
        }
        
        return Ext.create('Ext.form.Panel', {
            title: me.avalaraSnippets.actions,
            items: items,
            width: '45%',
            height: 130,
            bodyPadding: '5px 10px',
            layout: {
                type: 'vbox',
                defaultMargins: '5 0'
            }
        });
    },
    
    createInformationPanel: function (exemptionCode, transactionType, incoterms, docCode, landedCost, insurance) {
        var me = this;
        return Ext.create('Ext.form.Panel', {
            title: me.avalaraSnippets.information,
            items: [
                {
                    xtype: 'displayfield',
                    fieldLabel: me.avalaraSnippets.docCode,
                    value: docCode ? docCode : '-',
                    labelWidth: 170
                },
                {
                    xtype: 'displayfield',
                    fieldLabel: me.avalaraSnippets.transactionStatus,
                    value: transactionType ? transactionType : '-',
                    labelWidth: 170
                },
                {
                    xtype: 'displayfield',
                    fieldLabel: me.avalaraSnippets.incoterms,
                    value: incoterms ? incoterms : '-',
                    labelWidth: 170
                },
                {
                    xtype: 'displayfield',
                    fieldLabel: me.avalaraSnippets.landedCost,
                    value: landedCost ? landedCost : '-',
                    labelWidth: 170
                },
                {
                    xtype: 'displayfield',
                    fieldLabel: me.avalaraSnippets.insurance,
                    value: insurance ? insurance : '-',
                    labelWidth: 170
                },
                {
                    xtype: 'displayfield',
                    fieldLabel: me.avalaraSnippets.exemptionCode,
                    value: exemptionCode ? exemptionCode : '-',
                    labelWidth: 170
                }
            ],
            width: '50%',
            height: 190,
            style: 'margin-right: 10px;',
            bodyPadding: '5px 10px'
        });
    }
});
// {/block}
