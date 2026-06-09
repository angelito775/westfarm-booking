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
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        #panorama {
            width: 100%;
            height: 100vh;
        }

        /* Top bar with current location */
        .top-bar {
            position: absolute;
            top: 15px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 100;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .location-badge {
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(10px);
            color: white;
            padding: 10px 24px;
            border-radius: 30px;
            font-size: 18px;
            font-weight: 600;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        /* Location navigator sidebar */
        .location-nav {
            position: absolute;
            top: 80px;
            left: 15px;
            z-index: 100;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .nav-btn {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            color: #333;
            padding: 10px 18px;
            border: 2px solid transparent;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.15);
            text-align: left;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-btn:hover {
            background: white;
            border-color: #667eea;
            transform: translateX(4px);
            box-shadow: 0 4px 16px rgba(102, 126, 234, 0.3);
        }

        .nav-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
            box-shadow: 0 4px 16px rgba(102, 126, 234, 0.4);
        }

        /* Scene-switching hotspots */
        .scene-hotspot {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .scene-hotspot-inner {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8px);
            color: #333;
            padding: 8px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.25);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .scene-hotspot-inner:hover {
            transform: scale(1.15);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.5);
        }

        /* Compass-like minimap indicator */
        .minimap {
            position: absolute;
            bottom: 80px;
            right: 20px;
            z-index: 100;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .minimap-title {
            color: rgba(255, 255, 255, 0.6);
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
            text-align: center;
        }

        .minimap-dots {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
            max-width: 200px;
        }

        .minimap-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            border: 2px solid rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .minimap-dot:hover {
            background: rgba(255, 255, 255, 0.6);
            transform: scale(1.3);
        }

        .minimap-dot.active {
            background: #667eea;
            border-color: white;
            box-shadow: 0 0 10px rgba(102, 126, 234, 0.6);
        }

        .minimap-dot .dot-label {
            position: absolute;
            bottom: 18px;
            left: 50%;
            transform: translateX(-50%);
            color: white;
            font-size: 10px;
            font-weight: 600;
            white-space: nowrap;
            opacity: 0;
            transition: opacity 0.2s;
            pointer-events: none;
            background: rgba(0, 0, 0, 0.7);
            padding: 3px 8px;
            border-radius: 6px;
        }

        .minimap-dot:hover .dot-label {
            opacity: 1;
        }
    </style>
</head>
<body>

<div class="top-bar">
    <div class="location-badge" id="currentLocation">🌉 Bridge View</div>
</div>

<div class="location-nav" id="locationNav"></div>

<div class="minimap" id="minimap" style="display: none;">
    <div class="minimap-title">Locations</div>
    <div class="minimap-dots" id="minimapDots"></div>
</div>

<div id="panorama"></div>

<!-- Pannellum JS -->
<script src="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.js"></script>

<script>
// ============================================
// CONFIGURATION — Define all 360° locations
// ============================================
const locations = {
    bridge: {
        image: "360photo.JPG",
        title: "🌉 Bridge View",
        icon: "🌉"
    },
    villaLounge: {
        image: "VillaLounge.JPG",
        title: "🏡 Villa Lounge",
        icon: "🏡"
    }
};

// Define which locations connect to which
const connections = {
    bridge:      ["villaLounge"],
    villaLounge: ["bridge"]
};

// Scene configuration for Pannellum multi-scene
const scenes = {};

Object.keys(locations).forEach(key => {
    const loc = locations[key];

    // Build scene hotspots for each connected location
    const sceneHotspots = connections[key].map(targetKey => {
        const target = locations[targetKey];
        return {
            "pitch": 0,
            "yaw": key === "bridge" ? 300 : 180,
            "type": "scene",
            "sceneId": targetKey,
            "text": target.icon + " Go to " + target.title.replace(/[^\w\s]/g, '').trim(),
            "cssClass": "scene-hotspot",
            "targetPitch": -10,
            "targetYaw": key === "bridge" ? 180 : 300,
            "targetHfov": 100,
            "createTooltipFunc": function(hotSpotDiv, args) {
                const inner = document.createElement('div');
                inner.className = 'scene-hotspot-inner';
                inner.innerHTML = args;
                hotSpotDiv.appendChild(inner);
                hotSpotDiv.style.width = 'auto';
                hotSpotDiv.style.height = 'auto';
            },
            "createTooltipArgs": target.icon + " " + target.title.replace(/[^\w\s]/g, '').trim()
        };
    });

    scenes[key] = {
        "title": loc.title,
        "type": "equirectangular",
        "panorama": loc.image,
        "pitch": 0,
        "yaw": 0,
        "hfov": 110,
        "autoLoad": key === "bridge",
        "showControls": false,
        "hotSpots": sceneHotspots
    };
});

// ============================================
// Initialize multi-scene viewer
// ============================================
const viewer = pannellum.viewer('panorama', {
    "default": {
        "firstScene": "bridge",
        "sceneFadeDuration": 1000,
        "autoLoad": true,
        "autoRotate": -1,
        "autoRotateInactivityDelay": 5000,
        "showControls": false,
        "keyboardZoom": false,
        "mouseZoom": true,
        "compass": false,
        "northOffset": 0,
        "crossOrigin": "anonymous"
    },
    "scenes": scenes
});

// ============================================
// UI Updates on scene change
// ============================================
viewer.on('scenechange', function(sceneId) {
    updateUI(sceneId);
});

viewer.on('load', function() {
    const sceneId = viewer.getScene();
    if (sceneId) updateUI(sceneId);
});

function updateUI(sceneId) {
    const loc = locations[sceneId];
    if (!loc) return;

    // Update top badge
    document.getElementById('currentLocation').textContent = loc.title;

    // Update nav buttons
    document.querySelectorAll('.nav-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.scene === sceneId);
    });

    // Update minimap
    document.querySelectorAll('.minimap-dot').forEach(dot => {
        dot.classList.toggle('active', dot.dataset.scene === sceneId);
    });
}

// ============================================
// Build Location Navigation Sidebar
// ============================================
const navContainer = document.getElementById('locationNav');

Object.keys(locations).forEach(key => {
    const loc = locations[key];
    const btn = document.createElement('button');
    btn.className = 'nav-btn';
    btn.dataset.scene = key;
    btn.innerHTML = loc.icon + ' ' + key.charAt(0).toUpperCase() + key.slice(1).replace(/([A-Z])/g, ' $1');
    btn.addEventListener('click', () => {
        viewer.loadScene(key, 0, 0, 110);
    });
    navContainer.appendChild(btn);
});

// ============================================
// Build Minimap Dots
// ============================================
const minimap = document.getElementById('minimap');
const dotsContainer = document.getElementById('minimapDots');
const locationKeys = Object.keys(locations);

if (locationKeys.length > 2) {
    minimap.style.display = 'block';
}

locationKeys.forEach(key => {
    const loc = locations[key];
    const dot = document.createElement('div');
    dot.className = 'minimap-dot';
    dot.dataset.scene = key;

    const label = document.createElement('span');
    label.className = 'dot-label';
    label.textContent = loc.title;
    dot.appendChild(label);

    dot.addEventListener('click', () => {
        viewer.loadScene(key, 0, 0, 110);
    });
    dotsContainer.appendChild(dot);
});

// ============================================
// Custom Controls
// ============================================
const controlsContainer = document.createElement('div');
controlsContainer.style.cssText = 'position: absolute; bottom: 20px; left: 50%; transform: translateX(-50%); z-index: 100; display: flex; gap: 8px; background: rgba(0,0,0,0.6); backdrop-filter: blur(10px); padding: 10px 16px; border-radius: 30px;';

const controlButtons = [
    { label: '◀', action: () => viewer.setYaw(viewer.getYaw() - 25, 500) },
    { label: '▶', action: () => viewer.setYaw(viewer.getYaw() + 25, 500) },
    { label: '🔼', action: () => viewer.setPitch(Math.min(90, viewer.getPitch() + 15), 500) },
    { label: '🔽', action: () => viewer.setPitch(Math.max(-90, viewer.getPitch() - 15), 500) },
    { label: '➕', action: () => viewer.setHfov(Math.max(30, viewer.getHfov() - 10), 300) },
    { label: '➖', action: () => viewer.setHfov(Math.min(120, viewer.getHfov() + 10), 300) },
    { label: '🔄', action: () => { const s = viewer.getScene(); if(s) viewer.loadScene(s, 0, 0, 110); } },
    { label: '⛶', action: () => viewer.toggleFullscreen() }
];

controlButtons.forEach(ctrl => {
    const btn = document.createElement('button');
    btn.textContent = ctrl.label;
    btn.style.cssText = 'width: 36px; height: 36px; border: none; border-radius: 50%; background: rgba(255,255,255,0.15); color: white; font-size: 14px; cursor: pointer; transition: all 0.2s ease; display: flex; align-items: center; justify-content: center;';
    btn.addEventListener('mouseenter', () => { btn.style.background = 'rgba(102, 126, 234, 0.8)'; btn.style.transform = 'scale(1.1)'; });
    btn.addEventListener('mouseleave', () => { btn.style.background = 'rgba(255,255,255,0.15)'; btn.style.transform = 'scale(1)'; });
    btn.addEventListener('click', ctrl.action);
    controlsContainer.appendChild(btn);
});

document.body.appendChild(controlsContainer);

// ============================================
// Keyboard Navigation
// ============================================
document.addEventListener('keydown', (e) => {
    switch(e.key) {
        case 'ArrowLeft':  viewer.setYaw(viewer.getYaw() - 15, 200); break;
        case 'ArrowRight': viewer.setYaw(viewer.getYaw() + 15, 200); break;
        case 'ArrowUp':    viewer.setPitch(Math.min(90, viewer.getPitch() + 10), 200); break;
        case 'ArrowDown':  viewer.setPitch(Math.max(-90, viewer.getPitch() - 10), 200); break;
        case '+': case '=': viewer.setHfov(Math.max(30, viewer.getHfov() - 10), 200); break;
        case '-':           viewer.setHfov(Math.min(120, viewer.getHfov() + 10), 200); break;
        case 'f':           viewer.toggleFullscreen(); break;
        case 'r':           const s = viewer.getScene(); if(s) viewer.loadScene(s, 0, 0, 110); break;
    }
});
</script>

</body>
</html>
