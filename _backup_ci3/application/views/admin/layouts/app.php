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
            <?php $this->load->view($content);?>
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

