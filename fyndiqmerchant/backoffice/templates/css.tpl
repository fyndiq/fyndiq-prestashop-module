
<style type="text/css">

#fm-container {
    position: relative;
    width: 920px;
    margin: 0 auto;
    overflow: hidden;
}

#fm-logo {
    display: block;
    margin: 0 auto;
    width: 200px;
}

#fm-container label {
    width: auto;
}

.fm-form {
    margin: 20px 0;
}

.fm-form .submit::-moz-focus-inner {
    border: 0;
}
.fm-form .submit {
    border: 1px solid #a5a5a5;
    padding: 4px 8px;
    font-size: 13px;

    -webkit-border-radius: 4px;
    -moz-border-radius: 4px;
    border-radius: 4px;

    color: black;
    cursor: pointer;
    outline: none;

    background: #ffffff; /* Old browsers */
    background: -moz-linear-gradient(top,  #ffffff 0%, #e5e5e5 100%); /* FF3.6+ */
    background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#ffffff), color-stop(100%,#e5e5e5)); /* Chrome,Safari4+ */
    background: -webkit-linear-gradient(top,  #ffffff 0%,#e5e5e5 100%); /* Chrome10+,Safari5.1+ */
    background: -o-linear-gradient(top,  #ffffff 0%,#e5e5e5 100%); /* Opera 11.10+ */
    background: -ms-linear-gradient(top,  #ffffff 0%,#e5e5e5 100%); /* IE10+ */
    background: linear-gradient(to bottom,  #ffffff 0%,#e5e5e5 100%); /* W3C */
    filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#ffffff', endColorstr='#e5e5e5',GradientType=0 ); /* IE6-9 */
}
.fm-form .submit:active {
    background: #e5e5e5; /* Old browsers */
    background: -moz-linear-gradient(top,  #e5e5e5 0%, #ffffff 100%); /* FF3.6+ */
    background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#e5e5e5), color-stop(100%,#ffffff)); /* Chrome,Safari4+ */
    background: -webkit-linear-gradient(top,  #e5e5e5 0%,#ffffff 100%); /* Chrome10+,Safari5.1+ */
    background: -o-linear-gradient(top,  #e5e5e5 0%,#ffffff 100%); /* Opera 11.10+ */
    background: -ms-linear-gradient(top,  #e5e5e5 0%,#ffffff 100%); /* IE10+ */
    background: linear-gradient(to bottom,  #e5e5e5 0%,#ffffff 100%); /* W3C */
    filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#e5e5e5', endColorstr='#ffffff',GradientType=0 ); /* IE6-9 */
}

.fm-form .submit.important-action {
    color: white;
    text-shadow: 0px 1px 1px rgba(0, 0, 0, 0.6);
    font-weight: bold;
    border-color: #de981f;

    background: #fdc500; /* Old browsers */
    background: -moz-linear-gradient(top,  #fdc500 0%, #f39e09 100%); /* FF3.6+ */
    background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#fdc500), color-stop(100%,#f39e09)); /* Chrome,Safari4+ */
    background: -webkit-linear-gradient(top,  #fdc500 0%,#f39e09 100%); /* Chrome10+,Safari5.1+ */
    background: -o-linear-gradient(top,  #fdc500 0%,#f39e09 100%); /* Opera 11.10+ */
    background: -ms-linear-gradient(top,  #fdc500 0%,#f39e09 100%); /* IE10+ */
    background: linear-gradient(to bottom,  #fdc500 0%,#f39e09 100%); /* W3C */
    filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#fdc500', endColorstr='#f39e09',GradientType=0 ); /* IE6-9 */
}
.fm-form .submit.important-action:active {
    background: #f39e09; /* Old browsers */
    background: -moz-linear-gradient(top,  #f39e09 0%, #fdc500 100%); /* FF3.6+ */
    background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#f39e09), color-stop(100%,#fdc500)); /* Chrome,Safari4+ */
    background: -webkit-linear-gradient(top,  #f39e09 0%,#fdc500 100%); /* Chrome10+,Safari5.1+ */
    background: -o-linear-gradient(top,  #f39e09 0%,#fdc500 100%); /* Opera 11.10+ */
    background: -ms-linear-gradient(top,  #f39e09 0%,#fdc500 100%); /* IE10+ */
    background: linear-gradient(to bottom,  #f39e09 0%,#fdc500 100%); /* W3C */
    filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#f39e09', endColorstr='#fdc500',GradientType=0 ); /* IE6-9 */
}

.fm-form label {
    text-align: left;
}

.fm-form div {
    overflow: hidden;
}


.fm-loading-overlay {

    /* http://robertnyman.com/2010/01/11/css-background-transparency-without-affecting-child-elements-through-rgba-and-filters/ */
    background: rgb(0, 0, 0);
    background: rgba(0, 0, 0, 0.8);
    -ms-filter: "progid:DXImageTransform.Microsoft.gradient(startColorstr=#CC000000, endColorstr=#CC000000)";
    filter:~"progid:DXImageTransform.Microsoft.gradient(startColorstr=#CC000000, endColorstr=#CC000000)";

    position: absolute;
    width: 100%;
    height: 100%;
    z-index: 999;
}

.fm-loading-overlay img {
    width: 128px;
    height: 128px;
    display: block;
    margin: 150px auto;
}


#fm-main-panel {
    float: left;
    width: 660px;
}

#fm-sidebar {
    float: left;
    width: 245px;
    margin-left: 15px;
}


.fm-api-unavailable {
    text-align: center;
    font-size: 14px;
}


.fm-category-tree {
    margin: 0;
    padding: 0;
}
.fm-category-tree a.active {
    font-weight: bold;
}


.fm-product-list-container {
    margin: 10px 0;
}


.fm-product-list-controls {
    overflow: hidden;
    margin: 10px 0;
}
.fm-product-list-controls .select-all {
    float: left;
    margin: 0;
}
.fm-product-list-controls .submit-buttons {
    float: right;
    margin: 0;
}


.fm-product-list {
    margin: 0;
    padding: 2px;
    background-color: #e5e5e5;
}
.fm-product-list > li {
    overflow: hidden;
    background-color: #f5f5f5;
    margin-bottom: 3px;
    padding: 3px;
}
.fm-product-list li:last-child {
    margin-bottom: 0;
}

.fm-product-list .product {
    position: relative;
}

.fm-product-list .product .title {
    margin-bottom: 5px;
}
.fm-product-list .product .title h4 {
    font-size: 14px;
    margin: 0;
}
.fm-product-list .product .title h4 .reference {
    font-size: 13px;
    font-weight: normal;
}

.fm-product-list .product .select {
    float: left;
    margin: 15px 5px 0;
}

.fm-product-list .product .image {
    float: left;
    width: 50px;
    height: 50px;
}
.fm-product-list .product .image label {
    display: block;
    width: 100%;
    height: 100%;
    padding: 0;
}
.fm-product-list .product .image label img {
    display: block;
    margin: 0 auto;
    max-width: 50px;
    max-height: 50px;
}

.fm-product-list .product .prices {
    float: left;
    margin-left: 10px;
}

.fm-product-list .product .quantities {
    float: left;
    margin-left: 20px;
}


.fm-product-list .product .prices .price {
    margin-bottom: 2px;
}

.fm-product-list .product .prices label {
    display: block;
    width: 90px;
    text-align: right;
    float: left;
}

.fm-product-list .product .prices input {
    display: block;
    float: left;
    width: 100px;
}

.fm-product-list .product .expand {
    position: absolute;
    right: 0;
    bottom: 0;
    width: 28px;
    height: 28px;
}
.fm-product-list .product .expand img {
    display: block;
    width: 28px;
    height: 28px;
}
.fm-product-list .product .expand.inactive {
    opacity: 0.2;
    filter: alpha(opacity=20);
}

.fm-product-list .combinations {
    padding-left: 8px;
    margin-top: 3px;
}

.fm-product-list .combinations > li {
    overflow: hidden;
    margin-bottom: 1px;
    padding: 1px;
    background: #e5e5e5;
}

.fm-product-list .combinations .select {
    float: left;
    margin: 10px 5px 0;
}

.fm-product-list .combinations .image {
    float: left;
    width: 30px;
    height: 30px;
}
.fm-product-list .combinations .image img {
    display: block;
    margin: 0 auto;
    max-width: 30px;
    max-height: 30px;
}

</style>
