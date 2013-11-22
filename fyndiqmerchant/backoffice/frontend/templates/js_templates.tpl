
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

<script type="text/x-handlebars-template" class="handlebars-template" id="fm-accept-product-export">
<div class="fm-accept-product-export">
    <h3>Warning!</h3>
    <p>
        Some of the products that you selected have combinations with a different price than the product they belong to.<br>
        Fyndiq does not support different prices on different articles, Fyndiq supports only one price per product and all of its articles.<br>
        If you choose to proceed, we will set one common price for all your combinations.<br>
        We have calculated the recommended price for each product, which you can see below.<br>
        You may choose to alter these values now, before pressing the Accept and Export button.<br>
        Or you may choose to press Cancel to go back and alter your selection of products and combinations.
    </p>

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
                    <h3>{{name}}</b>
                    {{/with}}{{/with}}
                </div>

                <div class="price-info">
                    <div class="highest-price">
                        <b>Highest:</b> {{highest_price}}
                    </div>

                    <div class="lowest-price">
                        <b>Lowest:</b> {{lowest_price}}
                    </div>
                </div>
            </div>

            <div class="final-price">
                <label>Price:</label>
                <input type="text" value="{{highest_price}}">
            </div>
        </li>
    {{/each}}
    </ul>

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
</div>
</script>

<script type="text/x-handlebars-template" class="handlebars-template" id="fm-category-tree">
<ul class="fm-category-tree">
    {{#each categories}}
        {{#with this}}
            <li data-category_id="{{id}}">
                {{level}}
                <a href="#">{{name}}</a>
            </li>
        {{/with}}
    {{/each}}
</ul>
</script>

<script type="text/x-handlebars-template" class="handlebars-partial" id="fm-product-list-controls">
    <div class="fm-product-list-controls">
        <div class="select">
            <button class="fm-button" name="select-all">Select All</button>
            <button class="fm-button" name="deselect-all">Deselect All</button>
        </div>
        <div class="export">
            <button class="fm-button fyndiq" name="export-products">Export Products</button>
        </div>
    </div>
</script>

<script type="text/x-handlebars-template" class="handlebars-template" id="fm-product-list">
{{> fm-product-list-controls}}
{{#if products}}
    <ul class="fm-product-list">
        {{#each products}}
            {{#with this}}
            <li
                data-id="{{id}}"
                data-name="{{name}}"
                data-reference="{{reference}}"
                data-price="{{price}}"
                data-quantity="{{quantity}}"
                data-image="{{image}}"
            >
                <div class="product">
                    <div class="title">
                        <label for="select_product_{{id}}">
                            <h4>{{name}} <span class="reference">({{reference}})</span></h4>
                        </label>
                    </div>

                    <div class="select">
                        <input type="checkbox" id="select_product_{{id}}">
                    </div>

                    <div class="image">
                        {{#if image}}
                        <label for="select_product_{{id}}">
                            <img src="{{image}}" alt="Product image">
                        </label>
                        {{/if}}
                    </div>

                    <div class="prices">
                        <div class="price">
                            <label>Price:</label>
                            <input type="text" value="{{price}}">
                        </div>
                        <div class="price">
                            <label>Fyndiq Price:</label>
                            <input type="text">
                        </div>
                    </div>

                    <div class="quantities">
                        <div>Qty: {{quantity}}</div>
                        <div>Exported Qty: 6</div>
                        <div>Fyndiq Qty: 4</div>
                    </div>

                    <div class="expand
                        {{#unless combinations}}
                            inactive
                        {{/unless}}
                        ">
                        <a href="#">
                            <img src="{{../../module_path}}backoffice/frontend/images/icons/down-arrow.png"
                                alt="Down pointing arrow"
                                {{#if combinations}}
                                    title="Show combinations"
                                {{else}}
                                    title="Product does not have any combinations"
                                {{/if}}
                                >
                        </a>
                    </div>
                </div>
                {{#if combinations}}
                    <ul class="combinations">
                    {{#each combinations}}
                        <li
                            data-id="{{id}}"
                            data-price="{{price}}"
                            data-quantity="{{quantity}}"
                        >
                            <div class="select">
                                <input type="checkbox" class="checkbox" id="select_combination_{{id}}">
                            </div>

                            <div class="image">
                                {{#if image}}
                                <label for="select_combination_{{id}}">
                                    <img src="{{image}}" alt="Product combination image">
                                </label>
                                {{/if}}
                            </div>

                            <div class="data">
                                <ul class="attributes">
                                    {{#each attributes}}
                                        <li>
                                            {{name}}: {{value}}<span class="separator">,&nbsp;</span>
                                        </li>
                                    {{/each}}
                                </ul>

                                <div>
                                    Price: {{price}}
                                    Qty: {{quantity}}
                                </div>
                            </div>
                        </li>
                    {{/each}}
                    </ul>
                {{/if}}
            </li>
            {{/with}}
        {{/each}}
    </ul>
{{else}}
    Category is empty.
{{/if}}
{{> fm-product-list-controls}}
</script>

{/literal}
