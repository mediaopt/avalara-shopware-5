// {block name="backend/order/view/list/list"}
// {$smarty.block.parent}
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
        var docCode = '';
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
                docCode = response.data.__attribute_mopt_avalara_doc_code;
                orderChanged = parseInt(response.data.__attribute_mopt_avalara_order_changed) == 1;
            }
        });

        if (docCode) {
            output = '<img src="{link file="images/avalara.png"}" alt="Avalara" data-qtip="Committed to Avalara" />';
        }
        
        if (orderChanged) {
            output = '<img src="{link file="images/avalara_warning.png"}" alt="Avalara" data-qtip="Please check if your order has to be updated with Avalara." />';
        }
        
        return output;
    }
});
// {/block}