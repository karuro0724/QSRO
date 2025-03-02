<?php
session_start();
include 'config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit();
}

// Fetch documents from database
$result = $conn->query("SELECT * FROM documents ORDER BY id ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>Document Management</title>
</head>
<body>
    <?php include 'top-bar.php'; ?>

    <div class="container mt-4">
        <h2 class="text-center">Document Management</h2>
        
        <button class="btn btn-success mb-3" data-toggle="modal" data-target="#addDocumentModal">
            <i class="fa fa-plus"></i> Add Document
        </button>

        <table class="table table-bordered">
            <thead class="thead-dark">
                <tr>
                    <th>Document Name</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['document_name']); ?></td>
                        <td>
                            <button class="btn btn-warning btn-sm edit-btn" 
                                data-id="<?php echo $row['id']; ?>" 
                                data-name="<?php echo htmlspecialchars($row['document_name']); ?>">
                                <i class="fa fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-danger btn-sm delete-btn" data-id="<?php echo $row['id']; ?>">
                                <i class="fa fa-trash"></i> Delete
                            </button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Add Document Modal -->
    <div class="modal fade" id="addDocumentModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Document</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="addDocumentForm">
                        <div class="form-group">
                            <label for="document_name">Document Name</label>
                            <input type="text" class="form-control" id="document_name" name="document_name" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Document</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Document Modal -->
    <div class="modal fade" id="editDocumentModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Document</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="editDocumentForm">
                        <input type="hidden" id="edit_document_id">
                        <div class="form-group">
                            <label for="edit_document_name">Document Name</label>
                            <input type="text" class="form-control" id="edit_document_name" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Document</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add Document via AJAX
        $("#addDocumentForm").submit(function(event) {
            event.preventDefault();
            let documentName = $("#document_name").val();

            $.ajax({
                url: "function/add_document.php",
                type: "POST",
                data: { document_name: documentName },
                dataType: "json",
                success: function(response) {
                    if (response.status === "success") {
                        Swal.fire("Added!", response.message, "success").then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire("Error", response.message, "error");
                    }
                },
                error: function() {
                    Swal.fire("Error", "Something went wrong!", "error");
                }
            });
        });

        $(document).ready(function() {
            // Open Edit Modal
            $(".edit-btn").click(function() {
                let id = $(this).data("id");
                let name = $(this).data("name");

                $("#edit_document_id").val(id);
                $("#edit_document_name").val(name);

                $("#editDocumentModal").modal("show");
            });

            // Update Document via AJAX
            $("#editDocumentForm").submit(function(event) {
                event.preventDefault();
                let id = $("#edit_document_id").val();
                let name = $("#edit_document_name").val();

                $.ajax({
                    url: "function/update_document.php",
                    type: "POST",
                    data: { id: id, document_name: name },
                    dataType: "json",
                    success: function(response) {
                        if (response.status === "success") {
                            Swal.fire("Updated!", response.message, "success").then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire("Error", response.message, "error");
                        }
                    },
                    error: function() {
                        Swal.fire("Error", "Something went wrong!", "error");
                    }
                });
            });

            // Delete Document via AJAX
            $(".delete-btn").click(function() {
                let documentId = $(this).data("id");

                Swal.fire({
                    title: "Are you sure?",
                    text: "This document will be deleted permanently.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Yes, delete it!",
                    cancelButtonText: "Cancel"
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "function/delete_document.php",
                            type: "POST",
                            data: { id: documentId },
                            dataType: "json",
                            success: function(response) {
                                if (response.status === "success") {
                                    Swal.fire("Deleted!", response.message, "success").then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire("Error", response.message, "error");
                                }
                            },
                            error: function() {
                                Swal.fire("Error", "Something went wrong!", "error");
                            }
                        });
                    }
                });
            });
        });
    </script>

    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>