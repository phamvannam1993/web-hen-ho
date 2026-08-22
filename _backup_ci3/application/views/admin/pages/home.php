<html>
<?php $this->load->view('admin/partials/head')?>
  <body>
    <!-- Layout wrapper -->
  <div class="layout-wrapper layout-content-navbar  ">
    <div class="layout-container">
        <?php $this->load->view('admin/partials/aside')?>
        <div class="menu-mobile-toggler d-xl-none rounded-1">
          <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large text-bg-secondary p-2 rounded-1">
            <i class="bx bx-menu icon-base"></i>
            <i class="bx bx-chevron-right icon-base"></i>
          </a>
        </div>
    <!-- Layout container -->
      <div class="layout-page">
          <?php $this->load->view('admin/partials/nav')?>
          <div class="content-wrapper">
          <div class="container-xxl flex-grow-1 container-p-y"> 
        <div class="row g-6 mb-6">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                <div class="content-left">
                    <span class="text-heading">Session</span>
                    <div class="d-flex align-items-center my-1">
                    <h4 class="mb-0 me-2">21,459</h4>
                    <p class="text-success mb-0">(+29%)</p>
                    </div>
                    <small class="mb-0">Total Users</small>
                </div>
                <div class="avatar">
                    <span class="avatar-initial rounded bg-label-primary">
                    <i class="icon-base bx bx-group icon-lg"></i>
                    </span>
                </div>
                </div>
            </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                <div class="content-left">
                    <span class="text-heading">Paid Users</span>
                    <div class="d-flex align-items-center my-1">
                    <h4 class="mb-0 me-2">4,567</h4>
                    <p class="text-success mb-0">(+18%)</p>
                    </div>
                    <small class="mb-0">Last week analytics </small>
                </div>
                <div class="avatar">
                    <span class="avatar-initial rounded bg-label-danger">
                    <i class="icon-base bx bx-user-plus icon-lg"></i>
                    </span>
                </div>
                </div>
            </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                <div class="content-left">
                    <span class="text-heading">Active Users</span>
                    <div class="d-flex align-items-center my-1">
                    <h4 class="mb-0 me-2">19,860</h4>
                    <p class="text-danger mb-0">(-14%)</p>
                    </div>
                    <small class="mb-0">Last week analytics</small>
                </div>
                <div class="avatar">
                    <span class="avatar-initial rounded bg-label-success">
                    <i class="icon-base bx bx-user-check icon-lg"></i>
                    </span>
                </div>
                </div>
            </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                <div class="content-left">
                    <span class="text-heading">Pending Users</span>
                    <div class="d-flex align-items-center my-1">
                    <h4 class="mb-0 me-2">237</h4>
                    <p class="text-success mb-0">(+42%)</p>
                    </div>
                    <small class="mb-0">Last week analytics</small>
                </div>
                <div class="avatar">
                    <span class="avatar-initial rounded bg-label-warning">
                    <i class="icon-base bx bx-user-voice icon-lg"></i>
                    </span>
                </div>
                </div>
            </div>
            </div>
        </div>
        </div>
          </div>
          <?php $this->load->view('admin/partials/footer')?>
          <?php $this->load->view('admin/partials/script')?>
        <!-- / Footer -->
            <div class="content-backdrop fade"></div>
          </div>
          <!-- Content wrapper -->
        </div>
        <!-- / Layout page -->
      </div>
    </div>
  </body>
</html>
