(function ($) {
    var settings;
    var element;
    var map;
    var markers = new Array();
    var markerCluster;
    var clustersOnMap = new Array();
    var clusterListener;

    var methods = {
        init: function (options) {
            element = $(this);

            var defaults = $.extend({
                enableGeolocation: false,
                pixelOffsetX     : -145,
                pixelOffsetY     : -200
            });

            settings = $.extend({}, defaults, options);

            google.maps.Map.prototype.setCenterWithOffset = function (latlng, offsetX, offsetY) {
                var map = this;
                var ov = new google.maps.OverlayView();

                ov.onAdd = function () {
                    var proj = this.getProjection();
                    var aPoint = proj.fromLatLngToContainerPixel(latlng);
                    aPoint.x = aPoint.x + offsetX;
                    aPoint.y = aPoint.y + offsetY;
                    map.setCenter(proj.fromContainerPixelToLatLng(aPoint));
                }

                ov.draw = function () {
                };
                ov.setMap(this);
            };

            google.maps.visualRefresh = true;

            google.maps.event.addDomListener(window, 'load', loadMap);

            if (settings.filterForm && $(settings.filterForm).length !== 0) {
                $(settings.filterForm).submit(function (e) {
                    var form = $(this);
                    var action = $(this).attr('action');

                    $.ajax({
                        type   : 'GET',
                        url    : action,
                        data   : form.serialize(),
                        success: function (data) {
                            element.aviators_map('removeMarkers');
                            element.aviators_map('addMarkers', {
                                locations: eval(data.locations),
                                types    : eval(data.types),
                                contents : eval(data.contents)
                            });
                        }
                    });

                    e.preventDefault();
                });
            }


            if (options.callback) {
                options.callback();
            }
            return $(this);
        },

        removeMarkers: function () {
            for (i = 0; i < markers.length; i++) {
                markers[i].infobox.close();
                markers[i].marker.close();
                markers[i].setMap(null);
            }

            markerCluster.clearMarkers();

            $.each(clustersOnMap, function (index, cluster) {
                cluster.cluster.close();
            });

            clusterListener.remove();
        },

        addMarkers: function (options) {
            markers = new Array();
            settings.locations = options.locations;
            settings.contents = options.contents;
            settings.types = options.types;

            renderElements();
        }
    }

    $.fn.aviators_map = function (method) {
        // Method calling logic
        if (methods[method]) {
            return methods[ method ].apply(this, Array.prototype.slice.call(arguments, 1));
        } else if (typeof method === 'object' || !method) {
            return methods.init.apply(this, arguments);
        } else {
            $.error('Method ' + method + ' does not exist on Aviators Map');
        }
    };

    function loadMap() {
        var mapOptions = {
            zoom              : settings.zoom,
            mapTypeId         : google.maps.MapTypeId.ROADMAP,
            scrollwheel       : false,
            draggable         : true,
            mapTypeControl    : false,
            panControl        : false,
            zoomControl       : true,
            zoomControlOptions: {
                style   : google.maps.ZoomControlStyle.SMALL,
                position: google.maps.ControlPosition.LEFT_BOTTOM
            }
        };

        if (settings.enableGeolocation) {
            if (navigator.geolocation) {
                browserSupportFlag = true;
                navigator.geolocation.getCurrentPosition(function (position) {
                    initialLocation = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
                    map.setCenter(initialLocation);
                }, function () {
                    mapOptions.center = new google.maps.LatLng(settings.center.latitude, settings.center.longitude);
                });
            } else {
                browserSupportFlag = false;
                mapOptions.center = new google.maps.LatLng(settings.center.latitude, settings.center.longitude);
            }
        } else {
            mapOptions.center = new google.maps.LatLng(settings.center.latitude, settings.center.longitude);
        }

        map = new google.maps.Map($(element)[0], mapOptions);

        var dragFlag = false;
        var start = 0, end = 0;

        function thisTouchStart(e) {
            dragFlag = true;
            start = e.touches[0].pageY;
        }

        function thisTouchEnd() {
            dragFlag = false;
        }

        function thisTouchMove(e) {
            if (!dragFlag) {
                return
            }

            end = e.touches[0].pageY;
            window.scrollBy(0, ( start - end ));
        }

        var el = $('#map')[0];

        if (el.addEventListener) {
            el.addEventListener('touchstart', thisTouchStart, true);
            el.addEventListener('touchend', thisTouchEnd, true);
            el.addEventListener('touchmove', thisTouchMove, true);
        } else if (el.attachEvent){
            el.attachEvent('touchstart', thisTouchStart);
            el.attachEvent('touchend', thisTouchEnd);
            el.attachEvent('touchmove', thisTouchMove);
        }

        google.maps.event.addListener(map, 'zoom_changed', function () {
            $.each(markers, function (index, marker) {
                marker.infobox.close();
                marker.infobox.isOpen = false;
            });
        });

        renderElements();

        $( document ).on('click', '.infobox .close', function () {
            $.each(markers, function (index, marker) {
                marker.infobox.close();
                marker.infobox.isOpen = false;
            });
        });
    }

    function isClusterOnMap(clustersOnMap, cluster) {
        if (cluster === undefined) {
            return false;
        }

        if (clustersOnMap.length == 0) {
            return false;
        }

        var val = false;

        $.each(clustersOnMap, function (index, cluster_on_map) {
            if (cluster_on_map.getCenter() == cluster.getCenter()) {
                val = cluster_on_map;
            }
        });

        return val;
    }

    function addClusterOnMap(cluster) {
        // Hide all cluster's markers
        $.each(cluster.getMarkers(), (function () {
            if (this.marker.isHidden == false) {
                this.marker.isHidden = true;
                this.marker.close();
            }
        }));

        var newCluster = new InfoBox({
            markers               : cluster.getMarkers(),
            draggable             : true,
            content               : '<div class="clusterer"><div class="clusterer-inner">' + cluster.getMarkers().length + '</div></div>',
            disableAutoPan        : true,
            pixelOffset           : new google.maps.Size(-21, -21),
            position              : cluster.getCenter(),
            closeBoxURL           : "",
            isHidden              : false,
            enableEventPropagation: true,
            pane                  : "mapPane"
        });

        cluster.cluster = newCluster;

        cluster.markers = cluster.getMarkers();
        cluster.cluster.open(map, cluster.marker);
        clustersOnMap.push(cluster);
    }

    function renderElements() {

        $.each(settings.locations, function (index, location) {
            var marker = new google.maps.Marker({
                position: new google.maps.LatLng(location[0], location[1]),
                map     : map,
                icon    : settings.transparentMarkerImage
            });

            marker.infobox = new InfoBox({
                content               : settings.contents[index],
                disableAutoPan        : false,
                maxWidth              : 0,
                pixelOffset           : new google.maps.Size(settings.pixelOffsetX, settings.pixelOffsetY),
                zIndex                : null,
                closeBoxURL           : "",
                infoBoxClearance      : new google.maps.Size(1, 1),
                position              : new google.maps.LatLng(location[0], location[1]),
                isHidden              : false,
                pane                  : "floatPane",
                enableEventPropagation: false
            });
            marker.infobox.isOpen = false;

            marker.marker = new InfoBox({
                draggable             : true,
                content               : '<div class="marker ' + settings.types[index] + '"><div class="marker-inner"></div></div>',
                disableAutoPan        : true,
                pixelOffset           : new google.maps.Size(-21, -58),
                position              : new google.maps.LatLng(location[0], location[1]),
                closeBoxURL           : "",
                isHidden              : false,
                pane                  : "floatPane",
                enableEventPropagation: true
            });
            marker.marker.isHidden = false;
            marker.marker.open(map, marker);
            markers.push(marker);

            google.maps.event.addListener(marker, 'click', function (e) {
                var curMarker = this;

                $.each(markers, function (index, marker) {
                    // if marker is not the clicked marker, close the marker
                    if (marker !== curMarker) {
                        marker.infobox.close();
                        marker.infobox.isOpen = false;
                    }
                });

                if (curMarker.infobox.isOpen === false) {
                    curMarker.infobox.open(map, this);
                    curMarker.infobox.isOpen = true;
                    map.setCenterWithOffset(curMarker.getPosition(), 100, -120);
                } else {
                    curMarker.infobox.close();
                    curMarker.infobox.isOpen = false;
                }
            });
        });

//        var defaultBounds = new google.maps.LatLngBounds(
//            new google.maps.LatLng(51.576521825930946, -0.8019537012939963),
//            new google.maps.LatLng(51.56545219985624, -0.7403272669678245));
//        map.fitBounds(defaultBounds);

        // Create the search box and link it to the UI element.
        var input = /** @type {HTMLInputElement} */(
            document.getElementById('pac-input'));
        map.controls[google.maps.ControlPosition.TOP_RIGHT].push(input);

        var searchBox = new google.maps.places.SearchBox(
            /** @type {HTMLInputElement} */(input));

        // [START region_getplaces]
        // Listen for the event fired when the user selects an item from the
        // pick list. Retrieve the matching places for that item.
        google.maps.event.addListener(searchBox, 'places_changed', function() {
            var places = searchBox.getPlaces();

            for (var i = 0, marker; marker = markers[i]; i++) {
                marker.setMap(null);
            }

            // For each place, get the icon, place name, and location.
            markers = [];
            var bounds = new google.maps.LatLngBounds();
            for (var i = 0, place; place = places[i]; i++) {


//                var image = {
//                    url: place.icon,
//                    size: new google.maps.Size(71, 71),
//                    origin: new google.maps.Point(0, 0),
//                    anchor: new google.maps.Point(17, 34),
//                    scaledSize: new google.maps.Size(25, 25)
//                };
//                //
//
//                // Create a marker for each place.
//                var mrkr = new google.maps.Marker({
//                    map: map,
//                    icon: image,
//                    title: place.name,
//                    position: place.geometry.location
//                });
//
//                markers.push(mrkr);

                bounds.extend(place.geometry.location);

               window.location = window.location.origin+'/home/'+place.geometry.location.d+","+place.geometry.location.e;
            }

            map.fitBounds(bounds);
        });
        // [END region_getplaces]

        // Bias the SearchBox results towards places that are within the bounds of the
        // current map's viewport.
        google.maps.event.addListener(map, 'bounds_changed', function() {
            var bounds = map.getBounds();
            console.log(bounds);
            searchBox.setBounds(bounds);
        });


        markerCluster = new MarkerClusterer(map, markers, {
            gridSize: 40,
            styles: [
                {
                    height   : 42,
                    url      : settings.transparentClusterImage,
                    width    : 42,
                    textColor: 'transparent'
                }
            ]
        });

        clustersOnMap = new Array();

        clusterListener = google.maps.event.addListener(markerCluster, 'clusteringend', function (clusterer) {
            var availableClusters = clusterer.getClusters();
            var activeClusters = new Array();

            $.each(availableClusters, function (index, cluster) {
                if (cluster.getMarkers().length > 1) {
                    activeClusters.push(cluster);
                }
            });

            $.each(availableClusters, function (index, cluster) {
                if (cluster.getMarkers().length > 1) {
                    var val = isClusterOnMap(clustersOnMap, cluster);

                    if (val !== false) {
                        val.cluster.setContent('<div class="clusterer"><div class="clusterer-inner">' + cluster.getMarkers().length + '</div></div>');
                        val.markers = cluster.getMarkers();
                        $.each(cluster.getMarkers(), (function (index, marker) {
                            if (marker.marker.isHidden == false) {
                                marker.marker.isHidden = true;
                                marker.marker.close();
                            }
                        }));
                    } else {
                        addClusterOnMap(cluster);
                    }
                } else {
                    // Show all markers without the cluster
                    $.each(cluster.getMarkers(), function (index, marker) {
                        if (marker.marker.isHidden == true) {
                            marker.marker.open(map, this);
                            marker.marker.isHidden = false;
                        }
                    });

                    // Remove old cluster
                    $.each(clustersOnMap, function (index, cluster_on_map) {
                        if (cluster !== undefined && cluster_on_map !== undefined) {
                            if (cluster_on_map.getCenter() == cluster.getCenter()) {
                                // Show all cluster's markers/
                                cluster_on_map.cluster.close();
                                clustersOnMap.splice(index, 1);
                            }
                        }
                    });
                }
            });

            var newClustersOnMap = new Array();

            $.each(clustersOnMap, function (index, clusterOnMap) {
                var remove = true;

                $.each(availableClusters, function (index2, availableCluster) {
                    if (availableCluster.getCenter() == clusterOnMap.getCenter()) {
                        remove = false;
                    }
                });

                if (!remove) {
                    newClustersOnMap.push(clusterOnMap);
                } else {
                    clusterOnMap.cluster.close();
                }
            });

            clustersOnMap = newClustersOnMap;
        });
    }
})(jQuery);