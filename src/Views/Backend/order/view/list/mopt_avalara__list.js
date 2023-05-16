// {block name="backend/order/view/list/list"}
// {$smarty.block.parent}
Ext.define('Shopware.apps.moptAvalara.Order.view.list.List', {
    override: 'Shopware.apps.Order.view.list.List',
    
    /**
     * Contains all snippets for the view component
     * @object
     */
    avalaraSnippets: {
        statusSalesOrder: "{s namespace='frontend/MoptAvalara/messages' name='statusSalesOrder'}The order has to be commited to Avalara.{/s}",
        orderChanged: "{s namespace='frontend/MoptAvalara/messages' name='orderChanged'}The order was changed and may not coincide with the transaction you sent to Avalar!{/s}",
        statusSalesInvoice: "{s namespace='frontend/MoptAvalara/messages' name='statusSalesInvoice'}Committed to Avalara.{/s}",
        statusDocVoided: "{s namespace='frontend/MoptAvalara/messages' name='statusDocVoided'}The order has been voided in Avalara.{/s}",
        statusUnknown: "{s namespace='frontend/MoptAvalara/messages' name='statusUnknown'}Avalara transaction status of this order is unknown. Please, open the order detail page to retrieve information from Avalara.{/s}"
    },
    
    avalaraUrls: {
        loadData: '{url controller=AttributeData action=loadData}',
        statusSalesOrder: "{link file='images/avalara_not_commited.png'}",
        orderChanged: "{link file='images/avalara_warning.png'}",
        statusSalesInvoice: "{link file='images/avalara.png'}",
        statusDocVoided: "{link file='images/avalara_voided.png'}",
        statusUnknown: "{link file='images/avalara_unknown.png'}"
    },
    
    getColumns: function () {
        var me = this;
        var result = me.callParent(arguments);
        result.splice(10,0,me.moptAvalaraCreateColumn());
        
        return result;
    },
    
    moptAvalaraCreateColumn: function () {
        var me = this;
        var columns = 
            {
                header: 'Avalara',
                renderer: me.moptAvalaraListColumnRender,
                width: '30'
            };
           
        return columns;
    },
    
    moptAvalaraListColumnRender: function (value, metaData, record) {
        var me = this,
            output = '',
            transactionType = '',
            docCode = '',
            orderChanged = false
        ;
        
        Ext.Ajax.request({
            url: me.avalaraUrls.loadData,
            params: {
                _foreignKey: record.get('id'),
                _table: 's_order_attributes'
            },
            async: false,
            success: function(responseData, request) {
                var response = Ext.JSON.decode(responseData.responseText);
                docCode = response.data.__attribute_mopt_avalara_doc_code;
                transactionType = response.data.__attribute_mopt_avalara_transaction_type;
                orderChanged = parseInt(response.data.__attribute_mopt_avalara_order_changed) === 1;
            }
        });

        switch (transactionType) {
            case 'SalesOrder':
            case '0':
                output = '<img src="' + me.avalaraUrls.statusSalesOrder + '" data-qtip="' + me.avalaraSnippets.statusSalesOrder + '" />';
                break;
                
            case 'SalesInvoice':
            case '1':
                output = (orderChanged)
                    ? '<img src="' + me.avalaraUrls.orderChanged + '" data-qtip="' + me.avalaraSnippets.orderChanged + '" />'
                    : '<img src="' + me.avalaraUrls.statusSalesInvoice + '" data-qtip="' + me.avalaraSnippets.statusSalesInvoice + '" />'
                ;
                break;
                
            case 'DocVoided':
                output = '<img src="' + me.avalaraUrls.statusDocVoided + '" data-qtip="' + me.avalaraSnippets.statusDocVoided + '" />';
                break;
                
            default:
                if (docCode) {
                    output = '<img src="' + me.avalaraUrls.statusUnknown + '" data-qtip="' + me.avalaraSnippets.statusUnknown + '" />';
                }
                break;
        }

        return output;
    }
});
// {/block}