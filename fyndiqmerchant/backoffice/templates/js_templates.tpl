
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
                {{#with product}}
                <h4 class="title">{{name}}</h4>
                {{/with}}
                <div class="image">
                    <img src="{{image}}">
                </div>
                {{#with product}}
                <div class="prices">
                    <div class="price">
                        <label>Price:</label>
                        <input type="text">
                    </div>
                    <div class="price">
                        <label>Fyndiq Price:</label>
                        <input type="text">
                    </div>
                </div>
                <div class="quantities">
                    <div>Qty: 3</div>
                    <div>Exported Qty:6</div>
                    <div>Fyndiq Qty: 4</div>
                </div>
                {{/with}}
            </li>
            {{/with}}
        {{/each}}
    </ul>
{{else}}
    Category is empty.
{{/if}}
</script>

{/literal}
