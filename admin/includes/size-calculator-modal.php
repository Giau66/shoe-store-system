<!-- MODAL BẢNG TRA SIZE GIÀY THÔNG MINH -->
<div class="modal fade" id="sizeCalculatorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-bottom p-3" style="background-color: #fde8ed;">
                <h5 class="modal-title fw-bold" style="color: #d16b82;">
                    <i class="fa-solid fa-ruler-horizontal me-2"></i>Công Cụ Gợi Ý Size Giày Chuẩn
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="small text-muted mb-3">Nhập chiều dài bàn chân của bạn (tính từ gót chân đến đầu ngón chân dài nhất):</p>
                
                <div class="input-group mb-3">
                    <input type="number" id="footLengthInput" class="form-control form-control-lg fw-bold" placeholder="VD: 24.5" step="0.1" min="20" max="32">
                    <span class="input-group-text fw-bold bg-light">cm</span>
                    <button class="btn btn-danger fw-bold px-4" style="background-color: #e28298; border: none;" onclick="calculateShoeSize()">
                        TÍNH SIZE
                    </button>
                </div>

                <!-- KẾT QUẢ HIỂN THỊ -->
                <div id="sizeResultBox" class="p-3 bg-light rounded-3 text-center d-none border">
                    <span class="text-muted small d-block">Size giày gợi ý dành cho bạn:</span>
                    <span id="recommendedSize" class="fs-1 fw-black" style="color: #d16b82;">EU 40</span>
                    <small id="sizeTipNote" class="d-block text-secondary mt-1"></small>
                </div>

                <!-- BẢNG TẠM CHUẨN EU -->
                <div class="mt-4">
                    <h6 class="fw-bold small mb-2 text-uppercase text-muted">Bảng tham khảo nhanh:</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered text-center small mb-0">
                            <thead class="table-light">
                                <tr><th>Độ dài chân</th><th>22.5 cm</th><th>23.5 cm</th><th>24.5 cm</th><th>25.5 cm</th><th>26.5 cm</th></tr>
                            </thead>
                            <tbody>
                                <tr><td class="fw-bold">Size EU</td><td>36</td><td>37 - 38</td><td>39 - 40</td><td>41 - 42</td><td>43 - 44</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function calculateShoeSize() {
    const val = parseFloat(document.getElementById('footLengthInput').value);
    const resultBox = document.getElementById('sizeResultBox');
    const sizeEl = document.getElementById('recommendedSize');
    const noteEl = document.getElementById('sizeTipNote');

    if (!val || val < 20 || val > 32) {
        alert('Vui lòng nhập chiều dài bàn chân hợp lệ từ 20 cm đến 32 cm!');
        return;
    }

    let size = "36";
    if (val <= 22.5) size = "EU 36";
    else if (val <= 23.0) size = "EU 37";
    else if (val <= 23.5) size = "EU 38";
    else if (val <= 24.5) size = "EU 39";
    else if (val <= 25.0) size = "EU 40";
    else if (val <= 25.5) size = "EU 41";
    else if (val <= 26.5) size = "EU 42";
    else if (val <= 27.5) size = "EU 43";
    else size = "EU 44";

    sizeEl.innerText = size;
    noteEl.innerText = "Mẹo: Nếu chân bè ngang hoặc hay mang tất dày, bạn nên chọn tăng thêm +1 Size nhé!";
    resultBox.classList.remove('d-none');
}
</script>