// This file is part of Zoph.
//
// Zoph is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.
// 
// Zoph is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with Zoph; if not, write to the Free Software
// Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

var zMaps=function() {
    var map;
    var markers;
    function createMap(div, provider) {
        var map = L.map('map').setView([0,0],1);

        switch(provider) {
        case "googlev3":
            var street = L.gridLayer.googleMutant( {
                type: 'roadmap'
            }).addTo(map);
            var sat = L.gridLayer.googleMutant( {
                type: 'satellite'
            }).addTo(map);
            // Hybrid mode is not working correctly yet
            //var hybrid = L.gridLayer.googleMutant( {
            //    type: 'hybrid'
            //}).addTo(map);
            layers = { 
                "Satellite": sat,
                "Streetmap": street
            };
            break;
        case "mapbox":
            var url="https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}{r}.png?access_token={accessToken}";
            var maxZoom=18;
            var attr='Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="http://mapbox.com">Mapbox</a>';

            var street=L.tileLayer(url, {
                attribution: attr,
                maxZoom: maxZoom,
                id: 'mapbox.streets',
                detectRetina: true,
                accessToken: mapbox_api_key
            }).addTo(map);

            var sat=L.tileLayer(url, {
                attribution: attr,
                maxZoom: maxZoom,
                id: 'mapbox.satellite',
                detectRetina: true,
                accessToken: mapbox_api_key
            }).addTo(map);

            var strsat=L.tileLayer(url, {
                attribution: attr,
                maxZoom: maxZoom,
                id: 'mapbox.streets-satellite',
                detectRetina: true,
                accessToken: mapbox_api_key
            }).addTo(map);

            layers = { 
                "Streetmap + Satellite": strsat,
                "Satellite": sat,
                "Streetmap": street
            };
            break;
        case "osm":
            var url="http://{s}.tile.osm.org/{z}/{x}/{y}.png"
            var attr='&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
            var street=L.tileLayer(url, {
                attribution: attr,
                detectRetina: true,
            }).addTo(map);
        }
        if(typeof layers != "undefined") {
            L.control.layers(layers).addTo(map);
        }
        this.map = map;
        this.markers = new L.featureGroup;
        this.markers.addTo(map);
    }

    function clickMap(e) {
        var latfield=document.getElementById('lat');
        var lonfield=document.getElementById('lon');
        var zoomfield=document.getElementById('mapzoom');
        var maptypefield=document.getElementById('maptype');

        latfield.value=e.latlng.lat;
        lonfield.value=e.latlng.lng;
        if(zoomfield) {
            zoomfield.value=e.target.getZoom();
        }
      
        zMaps.markers.remove();
        zMaps.markers = new L.featureGroup;
        zMaps.markers.addTo(zMaps.map);
        zMaps.createMarker(e.latlng.lat, e.latlng.lng);
    }

    function zoomUpdate(e) {
        var zoomfield=document.getElementById('mapzoom');
        if(zoomfield) {
            zoomfield.value=zMaps.map.getZoom();
        }
    }

    function createMarker(lat, lon, icon, title, infoBubble) {
        var marker=new L.marker( [lat, lon], {title: title});;
        if(icon) {
            marker.setIcon(new L.icon({ iconUrl: icon }));
        }
        zMaps.map.setView(new L.LatLng(lat,lon), zMaps.map.getZoom() || 14);

        if (infoBubble) {
            marker.bindPopup(infoBubble);
        }
        marker.addTo(zMaps.markers);
    }

    function createPolyline(points) {
        var polyline = L.polyline(points, {color : 'blue'}).addTo(zMaps.markers);
    }

    function setFieldUpdate() {
        // This makes sure that the map is updated when a user changes the
        // Lat and Lon fields manually.
        var latfield=document.getElementById("lat");
        var lonfield=document.getElementById("lon");
        var zoomfield=document.getElementById("mapzoom");

        latfield.onchange=zMaps.updateMap;
        lonfield.onchange=zMaps.updateMap;
        if(zoomfield) {
            zoomfield.onchange=zMaps.updateMap;
        }
    }

    function updateMap() {
        var latfield=document.getElementById("lat");
        var lonfield=document.getElementById("lon");
        var zoomfield=document.getElementById("mapzoom");
        var distance=document.getElementById("latlon_distance");
        var lat=latfield.value;
        var lon=lonfield.value;
        var zoomlevel=zMaps.map.getZoom();
        
        if(zoomfield) {
           zoomlevel=parseInt(zoomfield.value);
        }

        zMaps.markers.remove();
        zMaps.markers = new L.featureGroup;
        zMaps.markers.addTo(zMaps.map);
        zMaps.createMarker(lat, lon);
        createMarker(lat, lon,null,null,null);
        zMaps.setCenterAndZoom([lat, lon],zoomlevel);
    }

    function setCenterAndZoom(center, zoomlevel) {
        this.map.setView(center, zoomlevel);
    }

    function autoCenterAndZoom() {
        this.map.fitBounds(this.markers.getBounds());
    }

    function setUpdateHandlers() {
        this.map.on('click', zMaps.clickMap);
        this.map.on('zoomend', zMaps.zoomUpdate);
        this.map.on('moveend', zMaps.zoomUpdate);
        setFieldUpdate();
    }

    return {
        map:map,
        markers:markers,
        createMap:createMap,
        clickMap:clickMap,
        zoomUpdate:zoomUpdate,
        createMarker:createMarker,
        createPolyline:createPolyline,
        updateMap:updateMap,
        setCenterAndZoom:setCenterAndZoom,
        autoCenterAndZoom:autoCenterAndZoom,
        setUpdateHandlers:setUpdateHandlers

    };
}();
