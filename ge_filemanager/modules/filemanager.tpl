{block name='content'}
    <p>Current path is: {$session} <button class="btn btn-primary access-folder" data-folder="{$previous}">Go Back</button>
    <div class="panel">
        <h3>Upload New File</h3>
       <form action="{$upload_url}" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="fileInput">{$smarty.const.L_UPLOAD_FILE}</label>
                <input type="file" class="form-control-file" id="fileInput" name="fileToUpload[]" multiple>
            </div>
            <button type="submit" name="submitFileUpload" class="btn btn-primary">Upload</button>
        </form>
    </div>

    <div class="panel">
        <h3>Add New Folder</h3>
        <form action="{$upload_url}" method="post">
            <div class="form-group">
                <input type="text" class="form-control" name="new_folder_name" placeholder="Folder name">
            </div>
            <button type="submit" name="submitFolderCreate" class="btn btn-success">Create folder</button>
        </form>
    </div>

    <div class="panel">
        <div class="filemanager-container">
            <table class="table">
            <thead>
            <tr>
                <th colspan="2">Name</th>
                <th>Size</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            {foreach $files as $file}
                {if $file.is_folder}
                    <tr>
                        <td>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="30">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 016 3.75h3.879a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.776" />
                            </svg>
                        </td>
                        <td>{$file.name}</td>
                        <td>{$file.size}</td>
                        <td>
                            <button class="btn btn-primary access-folder" data-folder="{$file.path}">Access</button>
                            <button class="btn btn-danger delete-folder" data-folder="{$file.name}">Delete</button>
                        </td>
                    </tr>
                    
                {else}
                    <tr>
                        <td><img src="{$file.url}" class="img-preview" alt="{$file.name}" width="50"></td>
                        <td>{$file.name}</td>
                        <td>{$file.size}</td>
                        <td>
                            <button class="btn btn-info copy-path" data-path="{$file.path}">Copy path</button>
                            <a href="{$file.url}" target="_blank" class="btn btn-primary">Download</a>
                            <button class="btn btn-danger delete-file" data-file="{$file.name}">Delete</button>
                        </td>
                    </tr>
                {/if}
            {/foreach}
            </tbody>
        </table>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var copyPathButtons = document.querySelectorAll('.copy-path');
            copyPathButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    var pathToCopy = this.getAttribute('data-path');

                    // Create a temporary input element to copy the path
                    var tempInput = document.createElement('input');
                    tempInput.value = pathToCopy;
                    document.body.appendChild(tempInput);

                    // Select and copy the path
                    tempInput.select();
                    document.execCommand('copy');
                    document.body.removeChild(tempInput);

                    // Optionally, display a success message
                    alert('Path copied: ' + pathToCopy);
                });
            });

            var deleteFileButtons = document.querySelectorAll('.delete-file');
            deleteFileButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    var fileToDelete = this.getAttribute('data-file');
                    var confirmDelete = confirm('Are you sure you want to delete this file?');

                    if (confirmDelete) {
                        // Create a form to submit the file deletion
                        var deleteForm = document.createElement('form');
                        deleteForm.method = 'post';
                        deleteForm.style.display = 'none';

                        var fileInput = document.createElement('input');
                        fileInput.type = 'hidden';
                        fileInput.name = 'file_to_delete';
                        fileInput.value = fileToDelete;

                        deleteForm.appendChild(fileInput);

                        var buttonSubmit = document.createElement('button');
                        buttonSubmit.type='submit';
                        buttonSubmit.name = "deleteFile";

                        deleteForm.appendChild(buttonSubmit);

                        document.body.appendChild(deleteForm);
                        buttonSubmit.click();
                    }
                });
            });

            var deleteFolderButtons = document.querySelectorAll('.delete-folder');
            deleteFolderButtons.forEach(function(button) {
                button.addEventListener('click', function () {
                    var folderToDelete = button.getAttribute('data-folder');
                    var confirmDelete = confirm('Are you sure you want to delete this folder and its contents?');

                    if (confirmDelete) {
                        // Create a form to submit the folder deletion
                        var deleteForm = document.createElement('form');
                        deleteForm.method = 'post';
                        deleteForm.style.display = 'none';
                        deleteForm.name = 'deleteFolder';

                        var folderInput = document.createElement('input');
                        folderInput.type = 'hidden';
                        folderInput.name = 'folder_to_delete';
                        folderInput.value = folderToDelete;

                        deleteForm.appendChild(folderInput);

                        var buttonSubmit = document.createElement('button');
                        buttonSubmit.type='submit';
                        buttonSubmit.name = "deleteFolder";

                        deleteForm.appendChild(buttonSubmit);

                        document.body.appendChild(deleteForm);
                        buttonSubmit.click();
                    }
                });
            });

            // Handle folder navigation with AJAX
            var folderAccessors = document.querySelectorAll('.access-folder');
            folderAccessors.forEach(function(button) {
                button.addEventListener('click', function(event) {
                    var folderName = button.getAttribute('data-folder');

                    var form = document.createElement('form');
                    form.method = 'post';
                    form.style.display = 'none';
                    form.name = 'accessFolder';

                    var folderInput = document.createElement('input');
                    folderInput.type = 'hidden';
                    folderInput.name = 'folder_to_access';
                    folderInput.value = folderName;

                    form.appendChild(folderInput);

                    var buttonSubmit = document.createElement('button');
                    buttonSubmit.type='submit';
                    buttonSubmit.name = "submitAccessFolder";

                    form.appendChild(buttonSubmit);

                    document.body.appendChild(form);
                    buttonSubmit.click();
                });
            });
        });
    </script>
{/block}
