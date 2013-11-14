
{literal}

<script type="text/x-handlebars-template" class="handlebars-template" id="message-box">
<div class="{{classnames}}">
    <p>{{message}}</p>
</div>
</script>

<script type="text/x-handlebars-template" class="handlebars-template" id="category-tree">
<ul class="fm-category-tree">
    {{#each categories}}
        {{#with this}}
            <li>
                {{level}}
                <a href="#" data-category_id="{{id}}">{{name}}</a>
            </li>
        {{/with}}
    {{/each}}
</ul>
</script>

<script type="text/x-handlebars-template" class="handlebars-partial" id="product-list-controls">
    <div class="fm-product-list-controls">
        <div class="select-all">
            <label><input type="checkbox" class="checkbox"> <span>Select all</span></label>
        </div>
        <div class="submit-buttons">
            <input class="submit" type="submit" value="Export with combinations">
            <input class="submit" type="submit" value="Export combinations as products">
        </div>
    </div>
</script>

<script type="text/x-handlebars-template" class="handlebars-template" id="product-list">
{{> product-list-controls}}
{{#if products}}
    <ul class="fm-product-list">
        {{#each products}}
            {{#with this}}
            <li>
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
                            <img src="{{image}}">
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
                            <img src="{{../../module_path}}backoffice/templates/images/down-arrow.png"
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
                        <li>
                            <div class="image">
                                <img src="{{image}}">
                            </div>
                            <div class="attributes">
                                {{#each attributes}}
                                    {{name}} - {{value}},
                                {{/each}}
                            </div>
                            <div>
                                Price: {{price}}
                                Qty: {{quantity}}
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
{{> product-list-controls}}
</script>

{/literal}
