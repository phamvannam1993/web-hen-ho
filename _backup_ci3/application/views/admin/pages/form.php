<?php foreach($forms as $form) { ?>
    <?php if($form['type'] == 'text') { ?>
        <div class="mb-3">
            <label><?=$form['title']?></label>
            <input type="text" name="dataForm[<?=$form['field']?>]" class="form-control" value="<?=$form['value']?>" <?php if($form['required']) { ?> required <?php } ?> >
            <span class="text-error"><?= isset($dataError[$form['field']]) ? $dataError[$form['field']] : ''?></span>
        </div>
    <?php } ?>

    <?php if($form['type'] == 'number') { ?>
        <div class="mb-3">
            <label><?=$form['title']?></label>
            <input type="number" name="dataForm[<?=$form['field']?>]" class="form-control" value="<?=$form['value']?>">
            <span class="text-error"><?= isset($dataError[$form['field']]) ? $dataError[$form['field']] : ''?></span>
        </div>
    <?php } ?>

    <?php if($form['type'] == 'password') { ?>
        <div class="mb-3">
            <label><?=$form['title']?></label>
            <input type="password" name="dataForm[<?=$form['field']?>]" class="form-control" value="<?=$form['value']?>">
            <span class="text-error"><?= isset($dataError[$form['field']]) ? $dataError[$form['field']] : ''?></span>
        </div>
    <?php } ?>
    
    <?php if($form['type'] == 'date') { ?>
        <div class="mb-3">
            <label><?=$form['title']?></label>
            <input type="date" name="dataForm[<?=$form['field']?>]" class="form-control" value="<?=$form['value']?>">
            <span class="text-error"><?= isset($dataError[$form['field']]) ? $dataError[$form['field']] : ''?></span>
        </div>
    <?php } ?>
        
    <?php if($form['type'] == 'textarea') { ?>
        <div class="mb-3">
            <label><?=$form['title']?></label>
            <textarea name="dataForm[<?=$form['field']?>]" rows="5" id="<?=$form['field']?>" class="form-control"><?=$form['value']?></textarea>
            <span class="text-error"><?= isset($dataError[$form['field']]) ? $dataError[$form['field']] : ''?></span>
        </div>
        <script>
            CKEDITOR.replace('<?=$form['field']?>');
        </script>
    <?php } ?>
          
    <?php if($form['type'] == 'option') { ?>
        <div class="mb-3">
            <label><?=$form['title']?></label>
            <select name="dataForm[<?=$form['field']?>]" class="selectpicker w-100 form-control" data-style="btn-default">
                <?php foreach($form['items'] as $item) { ?>
                    <option value="<?=$item['id']?>"><?=$item['name']?></option>
                <?php } ?>
            </select>
            <span class="text-error"><?= isset($dataError[$form['field']]) ? $dataError[$form['field']] : ''?></span>
        </div>
    <?php } ?>

    <?php if($form['type'] == 'file') { ?>                 
        <div class="mb-3">
            <div class="upload-box">
                <h3><?=$form['title']?></h3>

                <div class="dropzone" id="dropzone">
                    <div class="dz-message">
                    <div class="icon">⬆️</div>
                    <p>Drop files here or click to upload</p>
                    <small>(This is just a demo dropzone. Selected files are not actually uploaded.)</small>
                    </div>
                    <input type="hidden" name="image_url" value="" id="image">
                    <input type="file" id="fileInput" name="image" hidden accept="image/*">
                    <input type="hidden" name="image_old" value="<?=$form['value']?>">
                </div>
               
                <div id="preview-list">
                  <?php if($form['value']) { ?>
                  <div class="preview-item">
                    <img src="/<?=$form['value']?>">
                      <div class="preview-info">
                          <strong></strong>
                          <small></small>
                      </div>
                      <a class="remove-btn remove-btn-file">Remove file</a>
                    </div>
                  <?php } ?>
                </div>
            </div>        
        </div>
        <script>
            $(".remove-btn-file").click(function() {
              $(".preview-item").remove()
            })
            const dropzone = document.getElementById("dropzone");
            const fileInput = document.getElementById("fileInput");
            const previewList = document.getElementById("preview-list");

            dropzone.addEventListener("click", () => fileInput.click());

            dropzone.addEventListener("dragover", (e) => {
            e.preventDefault();
                dropzone.classList.add("dragover");
            });
            dropzone.addEventListener("dragleave", () => {
            dropzone.classList.remove("dragover");
            });

            dropzone.addEventListener("drop", (e) => {
                e.preventDefault();
                dropzone.classList.remove("dragover");
                handleFile(e.dataTransfer.files);
            });

          fileInput.addEventListener("change", () => {
              handleFile(fileInput.files);
          });

        function handleFiles(files) {
        [...files].forEach(file => {
            if (!file.type.startsWith("image/")) return;

            const reader = new FileReader();
            reader.onload = e => {
            const container = document.createElement("div");
            container.className = "preview-item";

            const img = document.createElement("img");
            img.src = e.target.result;

            const info = document.createElement("div");
            info.className = "preview-info";
            info.innerHTML = `
                <strong>${file.name}</strong>
                <small>${(file.size / 1024).toFixed(1)} KB</small>
            `;

            const removeBtn = document.createElement("button");
            removeBtn.className = "remove-btn";
            removeBtn.innerText = "Remove file";
            removeBtn.onclick = () => container.remove();

            container.appendChild(img);
            container.appendChild(info);
            container.appendChild(removeBtn);

            previewList.appendChild(container);
            };
            reader.readAsDataURL(file);
        });
        }

        function handleFile(files) {
            // Xoá tất cả ảnh hiện tại trước khi thêm ảnh mới
            previewList.innerHTML = "";

            // Chỉ lấy file đầu tiên (nếu có)
            const file = files[0];
            if (!file || !file.type.startsWith("image/")) return;

            const reader = new FileReader();
            reader.onload = e => {
                const container = document.createElement("div");
                container.className = "preview-item";

                const img = document.createElement("img");
                img.src = e.target.result;
                $("#image").val(e.target.result)
                const info = document.createElement("div");
                info.className = "preview-info";
                info.innerHTML = `
                <strong>${file.name}</strong>
                <small>${(file.size / 1024).toFixed(1)} KB</small>
                `;

                const removeBtn = document.createElement("button");
                removeBtn.className = "remove-btn";
                removeBtn.innerText = "Remove file";
                removeBtn.onclick = () => {
                container.remove();
                fileInput.value = ""; // reset input để có thể chọn lại cùng file
                };

                container.appendChild(img);
                container.appendChild(info);
                container.appendChild(removeBtn);

                previewList.appendChild(container);
            };
          reader.readAsDataURL(file);
        }
        </script>
    <?php } ?>
    <?php if($form['type'] == 'multiple') { ?>     
   <div class="mb-3">
   <label><?=$form['title']?></label>
    <div class="multi-select">
      <input type="text" id="time-zone-select" class="input-box" placeholder="Select time zone" readonly>
      <div class="dropdown-option">
        <ul>
            <?php foreach($form['items'] as $item) { ?>
                <li data-value="<?=$item['id']?>"><?=$item['name']?></li>
            <?php } ?>
        </ul>
      </div>
      <div class="selected-values" id="selected-values"></div>
    </div>
  </div>
  <?php } ?>
<?php } ?>
<style>
   body {
  font-family: 'Segoe UI', sans-serif;
  background: #f5f6fa;
  padding: 40px;
}

.upload-box {
  margin: auto;
  background: #fff;
  padding: 20px 30px;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
  border: 1px solid #e0e0e0;
}

h3 {
  color: #333;
  font-weight: 500;
}

.dropzone {
  margin-top: 10px;
  border: 2px dashed #d3d3d3;
  border-radius: 10px;
  background: #fcfcfc;
  padding: 20px;
  text-align: center;
  color: #555;
  cursor: pointer;
  transition: background 0.3s ease;
  position: relative;
}

.dropzone:hover {
  background: #f0f6ff;
}

.dropzone .icon {
  font-size: 2em;
  margin-bottom: 10px;
}

.dropzone small {
  color: #777;
}

#preview-list {
  display: flex;
  flex-wrap: wrap;
  margin-top: 20px;
  gap: 15px;
}

.preview-item {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
  width: 150px;
  text-align: center;
  overflow: hidden;
}

.preview-item img {
  width: 100%;
  height: 120px;
  object-fit: cover;
  border-bottom: 1px solid #eee;
}

.preview-info {
  padding: 10px;
  font-size: 14px;
  color: #444;
}

.preview-info small {
  display: block;
  color: #888;
  margin-bottom: 5px;
}
.text-error {
  color: red;
}
.remove-btn {
  display: block;
  padding: 6px 0;
  color: #d9534f !important;
  background: none;
  border: none;
  cursor: pointer;
  font-size: 13px;
  border-top: 1px solid #f0f0f0;
  width: 100%;
}
body {
  font-family: Arial, sans-serif;
  background: #f5f6fa;
  padding: 40px;
}

.multi-select-container {
  max-width: 400px;
  margin: auto;
  background: #fff;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.multi-select {
  position: relative;
}

.input-box {
  width: 100%;
  padding: 10px;
  border: 2px solid #d3d3d3;
  border-radius: 5px;
  font-size: 16px;
  cursor: pointer;
}

.input-box:focus {
  border-color: #007bff;
}

.dropdown-option {
  display: none;
  position: absolute;
  width: 100%;
  background-color: white;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  margin-top: 5px;
  z-index: 100;
}

.dropdown-option ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.dropdown-option ul li {
  padding: 8px;
  cursor: pointer;
}

.dropdown-option ul li:hover {
  background-color: #f0f8ff;
}

.selected-values {
  display: flex;
  flex-wrap: wrap;
  margin-top: 10px;
}

.selected-values span {
  background-color: #e0e0e0;
  padding: 5px 10px;
  border-radius: 20px;
  margin-right: 8px;
  margin-top: 5px;
  font-size: 14px;
  color: #444;
}

.selected-values span button {
  border: none;
  background: transparent;
  font-weight: bold;
  color: #ff6347;
  cursor: pointer;
  margin-left: 5px;
}

.selected-values span button:hover {
  color: #d9534f;
}

</style>

<script>
const inputBox = document.getElementById('time-zone-select');
const dropdown = document.querySelector('.dropdown-option');
const selectedValuesContainer = document.getElementById('selected-values');
const dropdownItems = document.querySelectorAll('.dropdown-option ul li');

inputBox.addEventListener('click', () => {
  dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
});

dropdownItems.forEach(item => {
  item.addEventListener('click', () => {
    const value = item.dataset.value;
    addSelectedValue(value);
    dropdown.style.display = 'none'; // close the dropdown
  });
});

function addSelectedValue(value) {
  // Check if the value is already selected
  if (!Array.from(selectedValuesContainer.children).some(span => span.textContent.toLowerCase() === value.toLowerCase())) {
    const selectedSpan = document.createElement('span');
    selectedSpan.innerHTML = `${capitalizeFirstLetter(value)} <button onclick="removeSelectedValue(this)">×</button>`;
    selectedValuesContainer.appendChild(selectedSpan);
  }
}

function removeSelectedValue(button) {
  button.parentElement.remove();
}

function capitalizeFirstLetter(string) {
  return string.charAt(0).toUpperCase() + string.slice(1);
}

</script>