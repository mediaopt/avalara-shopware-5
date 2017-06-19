//{block name="backend/order/view/list/list" append}
Ext.define('Shopware.apps.moptAvalara.Order.view.list.List', {
    override: 'Shopware.apps.Order.view.list.List',
    
    getColumns: function () {
        var me = this;
        result = me.callParent(arguments);
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
        var output = '';
        var transactionType = '';
        var orderChanged = false;
        Ext.Ajax.request({
            url: '{url controller=AttributeData action=loadData}',
            params: {
                _foreignKey: record.get('id'),
                _table: 's_order_attributes'
            },
            async: false,
            success: function(responseData, request) {
                var response = Ext.JSON.decode(responseData.responseText);
                transactionType = response.data.__attribute_mopt_avalara_transaction_type;
                orderChanged = parseInt(response.data.__attribute_mopt_avalara_order_changed) == 1;
            }
        });

        switch (transactionType) {
            case 'SalesOrder':
                output = '<img src="{link file="images/avalara_not_commited.png"}" alt="Avalara" data-qtip="The order has to be commited to Avalara." />';
                break;
                
            case 'SalesInvoice':
                output = (orderChanged)
                    ? '<img src="{link file="images/avalara_warning.png"}" alt="Avalara" data-qtip="The order was changed and may not coincide with the transaction you sent to Avalar!" />'
                    : '<img src="{link file="images/avalara.png"}" alt="Avalara" data-qtip="Committed to Avalara" />'
                ;
                break;
                
            case 'DocVoided':
                output = '<img src="{link file="images/avalara_voided.png"}" alt="Avalara" data-qtip="The order has been voided in Avalara." />';
                break;
        }

        return output;
    }
});
//{/block}