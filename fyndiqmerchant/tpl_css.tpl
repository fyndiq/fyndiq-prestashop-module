
<style type="text/css">

#fm-container {
    position: relative;
}

.fm-form {
    margin: 20px 0;
}

.fm-form .submit {
    border: 1px solid gray;
    padding: 3px;
    font-size: 12px;
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

</style>
