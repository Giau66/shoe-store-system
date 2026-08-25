<!-- LEAFLET MAP & NOMINATIM GEOLOCATION PICKER MODAL -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<!-- MODAL MAP PICKER -->
<div class="modal fade" id="mapPickerModal" tabindex="-1" aria-hidden="true" style="z-index: 10050;">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <div class="modal-header bg-dark text-white p-3">
                <h5 class="modal-title fw-bold fs-6">
                    <i class="fa-solid fa-map-location-dot me-2 text-warning"></i>Chọn Địa Chỉ Trên Bản Đồ (Maps)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 bg-light">
                <!-- THANH TÌM KIẾM VỊ TRÍ & NÚT VỊ TRÍ CỦA TÔI -->
                <div class="row g-2 mb-3">
                    <div class="col-md-7 col-12">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                            <input type="text" id="mapSearchInput" class="form-control border-start-0 shadow-none" placeholder="Nhập tên đường, phường/xã, quận/huyện...">
                            <button type="button" class="btn btn-primary fw-bold" onclick="mapSearchAddress()">
                                <i class="fa-solid fa-search me-1"></i> Tìm Vị Trí
                            </button>
                        </div>
                    </div>
                    <div class="col-md-5 col-12">
                        <button type="button" class="btn btn-success w-100 fw-bold shadow-sm" onclick="mapUseCurrentLocation()">
                            <i class="fa-solid fa-crosshairs me-1"></i> 📍 Vị trí hiện tại của tôi
                        </button>
                    </div>
                </div>

                <!-- CONTAINER BẢN ĐỒ -->
                <div class="position-relative rounded-3 overflow-hidden border shadow-sm" style="height: 380px;">
                    <div id="leafletMap" style="width: 100%; height: 100%;"></div>
                    <!-- Spinner Loader -->
                    <div id="mapLoader" class="position-absolute top-50 start-50 translate-middle bg-white bg-opacity-90 px-3 py-2 rounded-3 shadow text-dark fw-bold" style="display: none; z-index: 1000;">
                        <i class="fa-solid fa-spinner fa-spin me-2 text-primary"></i> Đang tải vị trí...
                    </div>
                </div>

                <!-- HIỂN THỊ ĐỊA CHỈ XÁC NHẬN -->
                <div class="mt-3 p-3 bg-white rounded-3 border border-warning">
                    <small class="text-muted d-block fw-bold mb-1"><i class="fa-solid fa-location-pin text-danger me-1"></i>Địa chỉ đã chọn từ bản đồ:</small>
                    <div id="mapSelectedAddressText" class="fw-bold text-dark fs-6" style="min-height: 24px;">
                        Đang chọn vị trí...
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 p-3 bg-white">
                <button type="button" class="btn btn-light rounded-3 fw-bold" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-warning text-dark rounded-3 px-4 fw-bold shadow-sm" onclick="mapConfirmSelection()">
                    <i class="fa-solid fa-check me-1"></i> TỰ ĐỘNG ĐIỀN ĐỊA CHỈ NÀY
                </button>
            </div>
        </div>
    </div>
</div>

<script>
var mapInstance = null;
var mapMarker = null;
var currentTargetInput = null;
var currentProvinceSelect = null;
var selectedAddressDetails = {
    full_address: '',
    province: '',
    district: '',
    ward: '',
    lat: 0,
    lng: 0
};

// Hàm chuẩn hóa tiếng Việt loại bỏ dấu và tiền tố TP/Tỉnh
function normalizeVietnamese(str) {
    if (!str) return '';
    return str.toLowerCase()
        .normalize("NFD").replace(/[\u0300-\u036f]/g, "")
        .replace(/đ/g, "d").replace(/Đ/g, "d")
        .replace(/tp\.|thành phố|tỉnh/gi, "")
        .trim();
}

// Khởi tạo và Mở Modal Map (Đồng bộ 2 chiều với Form bên ngoài)
function openMapPicker(targetInputId, provinceSelectId) {
    currentTargetInput = typeof targetInputId === 'string' ? document.getElementById(targetInputId) : targetInputId;
    currentProvinceSelect = provinceSelectId ? (typeof provinceSelectId === 'string' ? document.getElementById(provinceSelectId) : provinceSelectId) : null;

    // 1. Đọc địa chỉ / Tỉnh thành đang nhập ở form ngoài để đồng bộ lên ô tìm kiếm bản đồ
    var initialQuery = '';
    if (currentTargetInput && currentTargetInput.value.trim() !== '') {
        initialQuery = currentTargetInput.value.trim();
    } else if (currentProvinceSelect && currentProvinceSelect.selectedIndex > 0) {
        var selectedProvText = currentProvinceSelect.options[currentProvinceSelect.selectedIndex].text;
        if (!selectedProvText.includes('--')) {
            initialQuery = selectedProvText;
        }
    }

    document.getElementById('mapSearchInput').value = initialQuery;

    var mapModalEl = document.getElementById('mapPickerModal');
    var mapModal = new bootstrap.Modal(mapModalEl);
    mapModal.show();

    mapModalEl.addEventListener('shown.bs.modal', function () {
        if (!mapInstance) {
            initLeafletMap(initialQuery);
        } else {
            mapInstance.invalidateSize();
            if (initialQuery !== '') {
                mapSearchAddress(initialQuery);
            }
        }
    }, { once: true });
}

// Khởi tạo bản đồ Leaflet
function initLeafletMap(initialQuery) {
    var defaultLat = 10.2537;
    var defaultLng = 105.9722;

    mapInstance = L.map('leafletMap').setView([defaultLat, defaultLng], 14);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap'
    }).addTo(mapInstance);

    mapMarker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(mapInstance);

    mapMarker.on('dragend', function (e) {
        var coord = e.target.getLatLng();
        reverseGeocode(coord.lat, coord.lng);
    });

    mapInstance.on('click', function (e) {
        mapMarker.setLatLng(e.latlng);
        reverseGeocode(e.latlng.lat, e.latlng.lng);
    });

    // Nếu đã có địa chỉ từ bên ngoài ➔ Tìm kiếm và ghim kim trực tiếp tại địa chỉ đó
    if (initialQuery && initialQuery.trim() !== '') {
        mapSearchAddress(initialQuery);
    } else {
        reverseGeocode(defaultLat, defaultLng);
    }

    document.getElementById('mapSearchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            mapSearchAddress();
        }
    });
}

// Chức năng 1: Lấy Vị Trí Hiện Tại (GPS HTML5 Geolocation API)
function mapUseCurrentLocation() {
    if (!navigator.geolocation) {
        alert('Trình duyệt của bạn không hỗ trợ định vị GPS.');
        return;
    }
    showMapLoader(true);
    navigator.geolocation.getCurrentPosition(
        function (pos) {
            showMapLoader(false);
            var lat = pos.coords.latitude;
            var lng = pos.coords.longitude;
            if (mapInstance) {
                mapInstance.setView([lat, lng], 16);
                mapMarker.setLatLng([lat, lng]);
            }
            reverseGeocode(lat, lng);
        },
        function (err) {
            showMapLoader(false);
            alert('Không thể lấy GPS: ' + (err.message || 'Hãy cấp quyền truy cập vị trí cho trình duyệt.'));
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
}

// Chức năng 2: Reverse Geocode (Từ tọa độ ➔ Địa chỉ tiếng Việt)
function reverseGeocode(lat, lng) {
    showMapLoader(true);
    selectedAddressDetails.lat = lat;
    selectedAddressDetails.lng = lng;

    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&addressdetails=1&accept-language=vi`)
        .then(res => res.json())
        .then(data => {
            showMapLoader(false);
            if (data && data.display_name) {
                var addr = data.address || {};
                var formatted = data.display_name;
                var provName = addr.city || addr.state || addr.province || '';
                
                selectedAddressDetails.full_address = formatted;
                selectedAddressDetails.province = provName;

                document.getElementById('mapSelectedAddressText').innerHTML = 
                    `<i class="fa-solid fa-location-dot text-danger me-1"></i> ${formatted}`;
            } else {
                var fallback = `Tọa độ: ${lat.toFixed(5)}, ${lng.toFixed(5)}`;
                document.getElementById('mapSelectedAddressText').innerText = fallback;
                selectedAddressDetails.full_address = fallback;
            }
        })
        .catch(err => {
            showMapLoader(false);
            var fallback = `Vị trí: ${lat.toFixed(5)}, ${lng.toFixed(5)}`;
            document.getElementById('mapSelectedAddressText').innerText = fallback;
            selectedAddressDetails.full_address = fallback;
        });
}

// Chức năng 3: Tìm Kiếm Địa Chỉ
function mapSearchAddress(customQuery) {
    var query = customQuery || document.getElementById('mapSearchInput').value.trim();
    if (!query) {
        alert('Vui lòng nhập địa chỉ hoặc tên vị trí cần tìm!');
        return;
    }
    showMapLoader(true);
    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&countrycodes=vn&accept-language=vi&limit=1`)
        .then(res => res.json())
        .then(data => {
            showMapLoader(false);
            if (data && data.length > 0) {
                var lat = parseFloat(data[0].lat);
                var lon = parseFloat(data[0].lon);
                mapInstance.setView([lat, lon], 16);
                mapMarker.setLatLng([lat, lon]);
                reverseGeocode(lat, lon);
            } else {
                if (!customQuery) {
                    alert('Không tìm thấy vị trí "' + query + '". Hãy thử nhập rõ tên phường/xã hoặc tỉnh thành.');
                }
            }
        })
        .catch(err => {
            showMapLoader(false);
        });
}

// Chức năng 4: Tự Động Điền Địa Chỉ Và Chọn Lại Tỉnh Thành Cho Form Ngoại
function mapConfirmSelection() {
    if (currentTargetInput) {
        currentTargetInput.value = selectedAddressDetails.full_address;
        
        // Tự động tìm và chọn lại đúng Tỉnh/Thành phố trong thẻ <select> ở form ngoài
        if (currentProvinceSelect) {
            var rawSearch = (selectedAddressDetails.province + ' ' + selectedAddressDetails.full_address);
            var normSearch = normalizeVietnamese(rawSearch);
            
            var matchedIndex = -1;
            for (var i = 0; i < currentProvinceSelect.options.length; i++) {
                var optText = currentProvinceSelect.options[i].text;
                var normOpt = normalizeVietnamese(optText);
                if (!normOpt) continue;

                if (normSearch.includes(normOpt) || normOpt.includes(normalizeVietnamese(selectedAddressDetails.province))) {
                    matchedIndex = i;
                    break;
                }
            }

            if (matchedIndex !== -1) {
                currentProvinceSelect.selectedIndex = matchedIndex;
                currentProvinceSelect.dispatchEvent(new Event('change'));
            }
        }
    }

    // Đóng Modal
    var modalEl = document.getElementById('mapPickerModal');
    var modalInst = bootstrap.Modal.getInstance(modalEl);
    if (modalInst) modalInst.hide();
}

function showMapLoader(show) {
    var loader = document.getElementById('mapLoader');
    if (loader) loader.style.display = show ? 'block' : 'none';
}
</script>
