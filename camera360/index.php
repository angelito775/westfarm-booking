<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Westfarm Resort 360 View</title>

    <!-- Pannellum CSS -->
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.css"/>

    <style>
        html, body {
            margin: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

        #panorama {
            width: 100%;
            height: 100vh;
        }
    </style>
</head>
<body>

<div id="panorama"></div>

<!-- Pannellum JS -->
<script src="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.js"></script>

<script>
pannellum.viewer('panorama', {
    "type": "equirectangular",
    "panorama": "360photo.jpg",
    "autoLoad": true,
    "showControls": true
});
</script>

</body>
</html>