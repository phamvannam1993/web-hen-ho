
<div class="container-xxl flex-grow-1 container-p-y">
    <h2><?=$title?></h2>
    <div class="card">
        <div class="card-datatable">
            <div id="DataTables_Table_0_wrapper" class="dt-container dt-bootstrap5 dt-empty-footer">
                <div class="row mx-3 my-0 justify-content-between">
                    <div class="d-md-flex justify-content-between align-items-center dt-layout-start col-md-auto me-auto mt-0">
                        <div class="dt-length mb-md-6 mb-0">
                            <select name="DataTables_Table_0_length" id="perpage" aria-controls="DataTables_Table_0" class="form-select ms-0" >
                                <option value="10" <?=isset($perPage) && $perPage == 10 ? 'selected' : ''?>>10</option>
                                <option value="25" <?=isset($perPage) && $perPage == 25 ? 'selected' : ''?>>25</option>
                                <option value="50" <?=isset($perPage) && $perPage == 50 ? 'selected' : ''?>> 50</option>
                                <option value="100" <?=isset($perPage) && $perPage == 100 ? 'selected' : ''?>>100</option>
                            </select>
                            <label for="dt-length-0"></label>
                        </div>
                    </div>
                    <div class="d-md-flex align-items-center dt-layout-end col-md-auto ms-auto d-flex gap-md-4 justify-content-md-between justify-content-center gap-4 flex-wrap mt-0">
                        <div class="dt-search mb-md-6 mb-2"><input type="search" class="form-control" id="dt-search-0" placeholder="Tìm kiếm" aria-controls="DataTables_Table_0" /><label for="dt-search-0"></label></div>
                        <div class="dt-buttons btn-group flex-wrap d-flex gap-4 mb-md-0 mb-6">
                            <a class="btn add-new btn-primary" tabindex="0" aria-controls="DataTables_Table_0" href="/admin/<?=$page_name?>/form">
                                <span><i class="icon-base bx bx-plus icon-sm me-0 me-sm-2"></i><span class="d-none d-sm-inline-block">Thêm mới</span></span>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="justify-content-between dt-layout-table">
                    <div class="d-md-flex justify-content-between align-items-center dt-layout-full table-responsive">
                        <table class="datatables-users table border-top dataTable dtr-column" id="DataTables_Table_0" aria-describedby="DataTables_Table_0_info" style="width: 100%;">
                            <thead>
                                <?php foreach($fields as $field) {?>
                                    <th><?=$field['name']?></th>
                                <?php } ?>
                                <th>Xử lý</th>
                            </thead>
                            <tbody>
                            <?php foreach($datas as $data) {?>
                                <tr>
                                <?php foreach($fields as $field) {?>
                                    <?php if($field['type'] == 'image') { ?>
                                        <td><img src="/<?=$data[$field['key']]?>" width="100"></td>
                                    <?php } else { ?>
                                        <td><?=$data[$field['key']]?></td>
                                    <?php } ?>
                                <?php } ?>
                                <td>
                                    <a href="/admin/<?=$page_name?>/form/<?=$data['id']?>" class="btn btn-sm btn-warning">Sửa</a>
                                    <button type="button"
                                            class="btn btn-sm btn-danger"
                                            data-bs-toggle="modal"
                                            data-bs-target="#confirmDeleteModal"
                                            data-id="<?= $data['id'] ?>"
                                            data-title="<?= $data['title'] ?>">
                                        Xóa
                                    </button>
                                </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="row mx-3 justify-content-between">
                    <div class="d-md-flex justify-content-between align-items-center flex-wrap mt-3">
                        <!-- Hiển thị số lượng -->
                        <div class="dt-info mb-2">
                            Hiển thị <?= $firstItem ?> đến <?= $lastItem ?> trong tổng số <?= $total ?> mục
                        </div>

                        <!-- Phân trang -->
                        <div class="dt-paging">
                            <nav aria-label="pagination">
                                <ul class="pagination">
                                    <!-- Previous -->
                                    <li class="dt-paging-button page-item <?= ($page == 1) ? 'disabled' : '' ?>">
                                        <a class="page-link previous" href="<?= $previousPageUrl ?? '#' ?>">
                                            <i class="icon-base bx bx-chevron-left icon-18px"></i>
                                        </a>
                                    </li>

                                    <!-- Pages -->
                                    <?php foreach ($pagesToShow as $p): ?>
                                        <?php if ($p === '...'): ?>
                                            <li class="page-item disabled"><span class="page-link">...</span></li>
                                        <?php else: ?>
                                            <li class="dt-paging-button page-item <?= ($p == $page) ? 'active' : '' ?>">
                                                <a class="page-link" href="<?= pageUrl($p) ?>"><?= $p ?></a>
                                            </li>
                                        <?php endif; ?>
                                    <?php endforeach; ?>

                                    <!-- Next -->
                                    <li class="dt-paging-button page-item <?= ($page == $totalPage) ? 'disabled' : '' ?>">
                                        <a class="page-link next" href="<?= $nextPageUrl ?? '#' ?>">
                                            <i class="icon-base bx bx-chevron-right icon-18px"></i>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal xác nhận xóa -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="GET" id="deleteForm">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="confirmDeleteLabel">Xác nhận xóa</h5>
                    <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Bạn có chắc chắn muốn xóa <strong id="deleteTitle"></strong> không?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-danger">Xóa</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    const deleteModal = document.getElementById('confirmDeleteModal');
    deleteModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const id = button.getAttribute('data-id');
        const title = button.getAttribute('data-title');

        const deleteTitle = deleteModal.querySelector('#deleteTitle');
        const deleteForm = deleteModal.querySelector('#deleteForm');

        deleteTitle.textContent = title;
        deleteForm.action = "/admin/<?=$page_name?>/delete/"+id;
    });
    $("#perpage").change(function() {
        var limit = $(this).val()
        location.href = "?limit="+limit;
    })
</script>