
<div class="container-xxl flex-grow-1 container-p-y">
    <h2><?=$title?></h2>
    <form action="" method="POST">
        <?php $this->load->view('admin/pages/form')?>
        <input type="hidden" value="<?=$product_id?>" name="dataForm[product_id]">
        <input type="hidden" value="<?=$address_id?>" name="dataForm[address_id]">
        <button type="submit" class="btn btn-primary"><?=$text_button?></button>
    </form>
</div>