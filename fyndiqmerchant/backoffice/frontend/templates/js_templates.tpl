
{literal}

<script type="text/x-handlebars-template" class="handlebars-template" id="fm-loading-overlay">
<div class="fm-loading-overlay">
    <img src="{{module_path}}backoffice/frontend/images/ajax-loader.gif" alt="Loading animation">
</div>
</script>

<script type="text/x-handlebars-template" class="handlebars-template" id="fm-message-overlay">
<div class="fm-message-overlay fm-{{type}}">
    <img class="close" src="{{module_path}}backoffice/frontend/images/icons/close-icon.png" alt="Close" title="Close message">
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
        <img src="{{module_path}}backoffice/frontend/images/icons/cancel.png">
        Cancel
    </button>
    <button class="fm-button accept" name="accept" data-modal-type="accept">
        <img src="{{module_path}}backoffice/frontend/images/icons/accept.png">
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
                        <strong>{{name}}</strong> <span class="shadow">({{id}})</span>
                        {{#if reference}}<br/>SKU: {{reference}}{{/if}}
                        {{#if properties}}<br/>{{properties}}{{/if}}
                    </td>
                    <td class="prices">
                        <table>
                            <tr>
                                <th>Price:</th>
                                <td class="pricetag">{{price}}&nbsp;{{currency}}</td>
                            </tr>
                            <tr>
                                <th>Fyndiq Discount:</th>
                                <td><div class="inputdiv"><input type="text" value="{{fyndiq_precentage}}" class="fyndiq_dicsount">%</div><span
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
                        {{#if fyndiq_exported}}
                        <i class="icon on big"></i>
                        {{else}}
                        <i class="icon noton big"></i>
                        {{/if}}
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
        {{> fm-product-list-controls}}
        {{> fm-product-pagination}}
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
                <td class="center">{{order_id}}</td>
                <td class="center">{{fyndiq_orderid}}</td>
                <td class="center">{{price}}</td>
                <td class="center">{{total_products}}</td>
                <td class="center">{{state}}</td>
                <td class="center">{{created_at}} <span class="shadow">({{created_at_time}})</span></td>
            </tr>
            {{/with}}
            {{/each}}
            </tbody>
        </table>
        {{else}}
        Orders is empty.
        {{/if}}
        {{> fm-order-list-controls}}
        {{> fm-product-pagination}}
    </script>
    <script type="text/x-handlebars-template" class="handlebars-partial" id="fm-order-list-controls">
        <div class="fm-order-list-controls">
            <div class="export">
                <button type="submit" class="fm-button green getdeliverynote">Get Delivery Notes</button>
            </div>
        </div>
    </script>

    <script type="text/x-handlebars-template" class="handlebars-template" id="fm-order-import-date-content">
        <div class="lastupdated">
            <img src="{{module_path}}backoffice/frontend/images/icons/refresh.png" />
            <span class="last-header">Latest Import</span>
            Today {{import_time}}
        </div>
    </script>
{/literal}
