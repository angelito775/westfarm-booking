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

        .location-info {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            font-family: Arial, sans-serif;
            font-size: 16px;
            z-index: 100;
        }

        .pnlm-hotspot-base {
            cursor: pointer !important;
        }

        .location-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 25px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            white-space: nowrap;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .location-button:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4);
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }

        .location-button:active {
            transform: scale(0.95);
        }
    </style>
</head>
<body>

<div class="location-info">
    <strong id="currentLocation">🌉 Bridge View</strong>
</div>

<div id="panorama"></div>

<!-- Pannellum JS -->
<script src="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.js"></script>

<script>
const locations = {
    bridge: {
        image: "360photo.JPG",
        title: "🌉 Bridge View",
        hotspots: [
            {
                pitch: -15,
                yaw: 0,
                type: "custom",
                targetLocation: "villaLounge",
                text: "🏡 Visit Villa Lounge"
            }
        ]
    },
    villaLounge: {
        image: "VillaLounge.JPG",
        title: "🏡 Villa Lounge",
        hotspots: [
            {
                pitch: -10,
                yaw: 180,
                type: "custom",
                targetLocation: "bridge",
                text: "🌉 Back to Bridge"
            }
        ]
    }
};

let currentLocation = 'bridge';
let viewer;

function createHotspotButton(text, targetLocation) {
    const button = document.createElement('button');
    button.className = 'location-button';
    button.innerHTML = text;
    button.onclick = (e) => {
        e.stopPropagation();
        e.preventDefault();
        console.log('Button clicked, navigating to:', targetLocation);
        goToLocation(targetLocation);
    };
    return button;
}

function goToLocation(location) {
    console.log('Navigating to:', location);
    if (viewer) {
        // Clear old hotspots
        const oldHotspots = viewer.getHotSpots();
        oldHotspots.forEach(hotspot => {
            viewer.removeHotSpot(hotspot.id);
        });
        
        // Set new panorama
        viewer.setPanorama(locations[location].image);
        
        // Add new hotspots with buttons
        locations[location].hotspots.forEach((hotspot, index) => {
            const hotspotId = 'hotspot-' + index;
            const button = createHotspotButton(hotspot.text, hotspot.targetLocation);
            
            viewer.addHotSpot({
                pitch: hotspot.pitch,
                yaw: hotspot.yaw,
                type: hotspot.type,
                clickHandlerFunc: () => goToLocation(hotspot.targetLocation),
                content: button.outerHTML
            }, hotspotId);
        });
        
        // Reattach button listeners after DOM update
        setTimeout(() => {
            document.querySelectorAll('.location-button').forEach(btn => {
                btn.onclick = (e) => {
                    e.stopPropagation();
                    e.preventDefault();
                    const locationData = btn.getAttribute('data-location');
                    if (locationData) {
                        goToLocation(locationData);
                    }
                };
            });
        }, 100);
        
        document.getElementById('currentLocation').textContent = locations[location].title;
        currentLocation = location;
    }
}

function initViewer(location) {
    const config = {
        "type": "equirectangular",
        "panorama": locations[location].image,
        "autoLoad": true,
        "showControls": true
    };

    viewer = pannellum.viewer('panorama', config);
    document.getElementById('currentLocation').textContent = locations[location].title;
    
    // Add initial hotspots with buttons
    locations[location].hotspots.forEach((hotspot, index) => {
        const hotspotId = 'hotspot-' + index;
        const button = createHotspotButton(hotspot.text, hotspot.targetLocation);
        button.setAttribute('data-location', hotspot.targetLocation);
        
        viewer.addHotSpot({
            pitch: hotspot.pitch,
            yaw: hotspot.yaw,
            type: hotspot.type,
            clickHandlerFunc: () => goToLocation(hotspot.targetLocation),
            content: button.outerHTML
        }, hotspotId);
    });
    
    // Reattach button listeners
    setTimeout(() => {
        document.querySelectorAll('.location-button').forEach(btn => {
            btn.onclick = (e) => {
                e.stopPropagation();
                e.preventDefault();
                const locationData = btn.getAttribute('data-location');
                if (locationData) {
                    goToLocation(locationData);
                }
            };
        });
    }, 100);
}

// Initialize with bridge view
initViewer('bridge');
</script>

</body>
</html>