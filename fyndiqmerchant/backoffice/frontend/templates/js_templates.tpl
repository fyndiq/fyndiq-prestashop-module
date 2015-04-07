{literal}

<script type="text/x-handlebars-template" class="handlebars-template" id="fm-loading-overlay">
<div class="fm-loading-overlay">
    <img src="{{paths.shared}}frontend/images/ajax-loader.gif" alt="Loading animation">
</div>
</script>

<script type="text/x-handlebars-template" class="handlebars-template" id="fm-message-overlay">
<div class="fm-message-overlay fm-{{type}}">
    <img class="close" src="{{paths.shared}}frontend/images/icons/close-icon.png" alt="Close" title="Close message">
    <h3>{{title}}</h3>
    <p>{{message}}</p>
</div>
</script>

<script type="text/x-handlebars-template" class="handlebars-template" id="fm-modal-overlay">
<div class="fm-modal-overlay">
    <div class="container">
        <div class="content"></div>
    </div>
</div>
</script>

<script type="text/x-handlebars-template" class="handlebars-partial" id="fm-product-price-warning-controls">
<div class="controls">
    <button class="fm-button cancel" name="cancel" data-modal-type="cancel">
        <img src="{{paths.shared}}frontend/images/icons/cancel.png">
        Cancel
    </button>
    <button class="fm-button accept" name="accept" data-modal-type="accept">
        <img src="{{paths.shared}}frontend/images/icons/accept.png">
        Accept
    </button>
</div>
</script>

<script type="text/x-handlebars-template" class="handlebars-template" id="fm-accept-product-export">
<div class="fm-accept-product-export">
    <h3>Warning!</h3>
    <p>
        Some of the selected products have combinations with a different price than the product price.<br>
        Fyndiq supports only one price per product and all of its articles.
    </p>
    <p>
        Below, we have show the recommended (highest) price for each product.<br>
        You may choose to change the price of each product, before you proceed.
    <p>
        Press Accept to export products now, using the given prices.<br>
        Press Cancel to go back and change the product selection.
    </p>

    {{> fm-product-price-warning-controls}}

    <ul>
    {{#each product_warnings}}
        <li>
            <div class="image">
                {{#with product}}{{#with product}}
                {{#if image}}
                    <img src="{{image}}" alt="Product image">
                {{/if}}
                {{/with}}{{/with}}
            </div>

            <div class="data">
                <div class="title">
                    {{#with product}}{{#with product}}
                    <input type="text" value="{{name}}">
                    {{/with}}{{/with}}
                </div>

                <div class="price-info">
                    <div class="highest-price">
                        Highest: {{highest_price}}
                    </div>

                    <div class="lowest-price">
                        Lowest: {{lowest_price}}
                    </div>
                </div>
            </div>

            <div class="final-price">
                <label>Discount:</label>
                {{#with product}}{{#with product}}
                <input type="text" value="{{fyndiq_percentage}}">
                {{/with}}{{/with}}
            </div>
        </li>
    {{/each}}
    </ul>

    {{> fm-product-price-warning-controls}}
</div>
</script>

<script type="text/x-handlebars-template" class="handlebars-template" id="fm-category-tree">
<ul class="fm-category-tree">
    {{#each categories}}
        {{#with this}}
            <li data-category_id="{{id}}">
                <a href="#" title="Open category">{{name}}</a>
            </li>
        {{/with}}
    {{/each}}
</ul>
</script>

<script type="text/x-handlebars-template" class="handlebars-partial" id="fm-product-list-controls">
    <div class="fm-product-list-controls">
        <div class="export">
            <a class="fm-button disabled fm-delete-products">Remove from Fyndiq</a>
            <a class="fm-button green fm-export-products">Send to Fyndiq</a>
        </div>
    </div>
</script>

<script type="text/x-handlebars-template" class="handlebars-partial" id="fm-product-pagination">
    {{#if pagination}}
    <div class="pages">
        {{{pagination}}}
    </div>
    {{/if}}
</script>

<script type="text/x-handlebars-template" class="handlebars-template" id="fm-product-list">
    <a class="fm-button green fm-update-product-status">Update status</a>
    {{> fm-product-list-controls}}
    <div class="fm-products-list-container">
        {{#if products}}
        <table>
            <thead>
            <tr>
                <th><input id="select-all" type="checkbox"></th>
                <th colspan="2">Product</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody class="fm-product-list">
            {{#each products}}
            {{#with this}}
            <tr
                    data-id="{{id}}"
                    data-name="{{name}}"
                    data-reference="{{reference}}"
                    data-description="{{description}}"
                    data-price="{{price}}"
                    data-quantity="{{quantity}}"
                    data-image="{{image}}"
                    class="product">
                {{#if image}}
                <td class="select center">
                    {{#if reference}}<input type="checkbox" id="select_product_{{id}}">{{/if}}
                </td>
                <td><img src="{{image}}" alt="Product image"></td>
                {{else}}
                <td class="select center"></td>
                <td>No Image</td>
                {{/if}}
                <td>
                    <strong>{{name}}</strong> <span class="shadow">({{reference}})</span>
                    {{#if properties}}<br/>{{properties}}{{/if}}
                    {{#unless reference}}<p class="text-warning">Missing SKU</p>{{/unless}}
                </td>
                <td class="prices">
                    <table>
                        <tr>
                            <th>Price:</th>
                            <td class="pricetag">{{price}}&nbsp;{{currency}}</td>
                        </tr>
                        <tr>
                            <th>Fyndiq Discount:</th>
                            <td><div class="inputdiv"><input{{#unless fyndiq_exported}} disabled="disabled"{{/unless}} type="text" value="{{fyndiq_precentage}}" class="fyndiq_dicsount">%</div><span
                                        id="ajaxFired"></span>
                            </td>
                        </tr>
                        <tr>
                            <th>Expected Price:</th>
                            <td class="price_preview"><span class="price_preview_price">{{expected_price}}</span>&nbsp;{{currency}}</td>
                        </tr>
                    </table>
                </td>
                <td class="quantities text-right">
                    {{quantity}}
                </td>
                <td class="status text-center">
                    <i class="icon {{fyndiq_status}} big"></i>
                </td>
            </tr>
            {{/with}}
            {{/each}}
            </tbody>
        </table>
        {{else}}
        Category is empty.
        {{/if}}
    </div>
    {{> fm-product-pagination}}
    {{> fm-product-list-controls}}
</script>

<script type="text/x-handlebars-template" class="handlebars-template" id="fm-orders-list">
    {{> fm-order-list-controls}}
    {{#if orders}}
    <table>
        <thead>
        <tr>
            <th><input id="select-all" type="checkbox"></th>
            <th colspan="1">Order</th>
            <th>Fyndiq Order</th>
            <th>Price</th>
            <th>Qty</th>
            <th>Status</th>
            <th>Created</th>
        </tr>
        </thead>
        <tbody class="fm-orders-list">
        {{#each orders}}
        {{#with this}}
        <tr data-id="{{order_id}}" data-fyndiqid="{{fyndiq_orderid}}">
            <td class="select center"><input type="checkbox" value="{{fyndiq_orderid}}" name="args[orders][]" id="select_order_{{entity_id}}"></td>
            <td class="center"><a href="{{link}}">{{order_id}}</a></td>
            <td class="center">{{fyndiq_orderid}}</td>
            <td class="center">{{price}}</td>
            <td class="center">{{total_products}}</td>
            <td class="center state">
                {{state}}
            </td>
            <td class="center">{{created_at}} <span class="shadow">({{created_at_time}})</span></td>
        </tr>
        {{/with}}
        {{/each}}
        </tbody>
    </table>
    {{else}}
    Orders is empty.
    {{/if}}
    {{> fm-product-pagination}}
    {{> fm-order-list-controls}}
</script>

<script type="text/x-handlebars-template" class="handlebars-partial" id="fm-order-list-controls">
    <div class="fm-order-list-controls">
        <div class="export">
            <button type="submit" class="fm-button green markasdone">Mark As Done</button>
            <button type="submit" class="fm-button green getdeliverynote">Get Delivery Notes</button>
        </div>
    </div>
</script>

<script type="text/x-handlebars-template" class="handlebars-template" id="fm-order-import-date-content">
    <div class="lastupdated">
        <img src="{{paths.shared}}frontend/images/icons/refresh.png" />
        <span class="last-header">Latest Import</span>
        Today {{import_time}}
    </div>
</script>
{/literal}
