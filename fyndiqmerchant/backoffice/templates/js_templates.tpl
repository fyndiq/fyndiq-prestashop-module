
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
                    {{#with this}}
                        <div class="image">
                            <img src="{{image}}">
                        </div>
                        {{#with product}}
                            <p class="name">
                            {{name}}
                            </p>
                        {{/with}}
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
