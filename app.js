"use strict";

/*
 * Starting app on DOM ready event.
 */
Ext.onReady(function() {
    Transactions.initialize();
});

/*
 * Validating LUNH algorithm on Card Number.
 * Valid Number is 4111111111111111.
 */
function validateForm() {
    var cardNumber = document.forms["transaction"]["CardNumber"].value;
    var num = cardNumber.replace(/[^\d]/, "");
    var str = "";

    for (var i = num.length - 1; i >= 0; --i) {
        str += i & 1 ? num[i] : (parseInt(num[i]) * 2).toString();
    }

    var sum = str.split("").reduce(function(prev, current) {
        return prev + parseInt(current);
    }, 0);
    if (sum % 10 === 0) {
        return true;
    }

    alert("CardNumber is Not Valid");
    return false;
}

/**
 * Transaction Module Responsible For Displaying
 * And CRUD Operations Performed on Transaction list
 */
var Transactions = {
    ///////////////////////
    // Configurations
    ///////////////////////

    el: "container-wrapper",
    
    api: "router.php",
    
    modelProperties: function() {
        return {
            url: this.api,
            storeId: "transactions",
            restful: true,
            autoLoad: true,
            autoSave: true,
            fields: this.schema
        };
    },

    viewProperties: function() {
        return {
            store: this.model,
            emptyText: "No Transaction to display",
            singleSelect: true,
            reserveScrollOffset: true,
            columns: this.columns()
        };
    },

    panelProperties: function() {
        return {
            id: "transactions",
            renderTo: this.el,
            width: "100%",
            height: 250,
            collapsible: true,
            layout: "fit",
            title: "Transactions History",
            items: this.view
        };
    },

    /*
     * Defining Data Schema for transaction Model.
     */
    schema: ["Created",
        "CardholderName",
        "CardNumber",
        "Status",
        "CVV", {
            name: "ExpiredM",
            type: "int"
        }, {
            name: "ExpiredY",
            type: "int"
        }, {
            name: "Amount",
            type: "float"
        }
    ],

    /*
     * Defining Header for Our List View
     * @return array 
     */
    columns: function() {
        return this.schema.map(function(el) {
            if ("string" === typeof(el))
                return {
                    "header": el,
                    "dataIndex": el
                };

            return {
                "header": el.name,
                "dataIndex": el.name
            };
        });
    },

    //////////////////////////////
    // Methods Definations
    /////////////////////////////
    initialize: function() {
        this.model = new Ext.data.JsonStore({
            url: this.api,
            storeId: "transactions",
            restful: true,
            autoLoad: true,
            fields: this.schema
        });
        this.view = new Ext.list.ListView(this.viewProperties());
        this.panel = new Ext.Panel(this.panelProperties());
        this.eventsHandler();
    },

    // Responsible for Binding events callbacks
    eventsHandler: function() {
        var _this = this;
        this.view.on("selectionchange", function(view) {
            _this.selected = view.last;
        });
        Ext.get("delete").on("click", this.deleteTransaction, this);
        Ext.get("edit").on("click", this.showEditForm, this);
    },

    isItemSelected: function() {
        return this.view.getSelectedRecords().length > 0;
    },

    deleteTransaction: function() {
        if(!this.isItemSelected())
            return;

        var _this = this;
        Ext.Ajax.request({
            method: "DELETE",
            url: _this.api,
            success: function(resp) {
                if (JSON.parse(resp.responseText).success === "ok")
                    return _this.model.load();

                return _this.showAlert(JSON.parse(resp.responseText).error);
            },
            failure: _this.showAlert,
            params: {
                index: _this.selected
            }
        });
    },

    showEditForm: function() {
        if(!this.isItemSelected())
            return;

        var index = this.selected;
        var model = this.model;

        editForm.getForm().setValues({
            "Created": model.getAt(index).get("Created"),
            "CardholderName": model.getAt(index).get("CardholderName"),
            "CardNumber": model.getAt(index).get("CardNumber"),
            "Status": model.getAt(index).get("Status"),
            "CVV": model.getAt(index).get("CVV"),
            "ExpiredM": model.getAt(index).get("ExpiredM"),
            "ExpiredY": model.getAt(index).get("ExpiredY"),
            "Amount": model.getAt(index).get("Amount"),
            "index": index
        });
        editWindow.show();
    },

    showAlert: function(message) {
        //TODO: Replace this with ExtJS Error Model.
        message = message || "Operation Failed Due to unknown Reason";
        alert(message);
    }
};

/*
 * Definig Our Edit Transaction Popup Model
 * @depenency Tranactions
 */
var itemFields = function() {
    var fields = Transactions.schema.map(function(el) {
        var name = ("string" === typeof(el)) ? el : el.name;
        return new Ext.form.TextField({
            id: name,
            fieldLabel: name
        })
    });

    fields.push(new Ext.form.TextField({
        id: "index",
        fieldLabel: "index",
        readOnly: true
    }));

    return fields;
};

/*
 * Populating Form.
 * @depenency Tranactions | itemFields
 */
var editForm = new Ext.form.FormPanel({
    frame: true,
    name: "edit_form",
    items: itemFields(),
    buttons: [{
        text: "Update",
        handler: function() {
            Ext.Ajax.request({
                method: "PUT",
                url: "router.php",
                success: function(resp) {
                    Transactions.model.load();
                },
                failure: Transactions.showAlert,
                params: {
                    data: JSON.stringify(editForm.getForm().getValues())
                }
            });
        }
    }]
});

/*
 * Window Containing Edit Form.
 * @depenency editForm
 */
var editWindow = new Ext.Window({
    id: "edit_window",
    title: "Edit Transaction",
    closable: true,
    width: 380,
    closeAction: "hide",
    height: 350,
    plain: true,
    layout: "fit",
    items: editForm,
});
