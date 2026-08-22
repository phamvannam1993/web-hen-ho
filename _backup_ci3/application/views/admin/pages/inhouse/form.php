
<div class="container-xxl flex-grow-1 container-p-y">
    <h2><?=$title?></h2>
    <form action="" method="POST">
        <?php $this->load->view('admin/pages/form')?>
        <button type="submit" class="btn btn-primary"><?=$text_button?></button>
    </form>
</div>