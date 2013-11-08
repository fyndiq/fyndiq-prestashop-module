
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

.fm-form {
    margin: 20px 0;
}

.fm-form .submit {
    border: 1px solid gray;
    padding: 3px;
    font-size: 12px;
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
}

.fm-loading-overlay img {
    width: 128px;
    height: 128px;
    position: absolute;
    left: 50%;
    top: 50%;
    margin-top: -64px;
    margin-left: -64px;
}

#fm-products {
    float: left;
    width: 620px;
}

#fm-sidebar {
    float: left;
    width: 285px;
    margin-left: 15px;
}

.fm-product-list li {
    overflow: hidden;
    background-color: #f5f5f5;
    margin-bottom: 5px;
    padding: 3px;
    border: 1px solid #333;
}

.fm-product-list .image {
    width: 100px;
    float: left;
}

.fm-product-list img {
    display: block;
    margin: 0 auto;
    max-width: 100px;
    max-height: 100px;
}

.fm-product-list p {
    float: left;
    margin-left: 10px;
}

</style>
