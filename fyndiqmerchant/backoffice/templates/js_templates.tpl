
{literal}

<script id="message-box" type="text/x-handlebars-template">
<div class="{{classnames}}">
    <p>{{message}}</p>
</div>
</script>

<script id="category-tree" type="text/x-handlebars-template">
<ul class="fm-category-tree">
    {{#each categories}}
        {{#with this}}
            <li>
                {{level}}
                {{#with category}}
                    <a href="#" data-category_id="{{id_category}}">{{name}}</a>
                {{/with}}
            </li>
        {{/with}}
    {{/each}}
</ul>
</script>

<script id="product-list" type="text/x-handlebars-template">
{{#if products}}
    <ul class="fm-product-list">
        {{#each products}}
            {{#with this}}
            <li>
                <div class="product">
                    <h4 class="title">{{name}} <span class="reference">({{reference}})</span></h4>
                    <div class="image">
                        {{#if image}}
                            <img src="{{image}}">
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
</script>

{/literal}
