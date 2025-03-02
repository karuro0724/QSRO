<?php
session_start();
include 'config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit();
}

// Fetch windows from database
$result = $conn->query("SELECT * FROM windows ORDER BY id ASC");
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
</head>
<body>
    <?php include 'top-bar.php'; ?>

<div class="container mt-4">

    <h2 class="text-center">Window Management</h2>
    
    <button class="btn btn-success mb-3" data-toggle="modal" data-target="#addWindowModal">
        <i class="fa fa-plus"></i> Add Window
    </button>

    <table class="table table-bordered">
        <thead class="thead-dark">
            <tr>
                <th>Window Number</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['window_number']); ?></td>
                    <td>
                        <span class="badge badge-<?php echo $row['status'] === 'Active' ? 'success' : 'danger'; ?>">
                            <?php echo $row['status']; ?>
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-warning btn-sm edit-btn" 
                            data-id="<?php echo $row['id']; ?>" 
                            data-number="<?php echo htmlspecialchars($row['window_number']); ?>"
                            data-status="<?php echo $row['status']; ?>">
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

<!-- Add Window Modal -->
<div class="modal fade" id="addWindowModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Window</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="addWindowForm">
                    <div class="form-group">
                        <label for="window_number">Window Number</label>
                        <input type="text" class="form-control" id="window_number" name="window_number" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Window</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Window Modal -->
<div class="modal fade" id="editWindowModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Window</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editWindowForm">
                    <input type="hidden" id="edit_window_id">
                    <div class="form-group">
                        <label for="edit_window_number">Window Number</label>
                        <input type="text" class="form-control" id="edit_window_number" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_window_status">Status</label>
                        <select class="form-control" id="edit_window_status">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Window</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- jQuery AJAX -->
<script>
    // Add Window via AJAX
$("#addWindowForm").submit(function(event) {
    event.preventDefault();
    let windowNumber = $("#window_number").val();

    $.ajax({
        url: "function/add_window.php",
        type: "POST",
        data: { window_number: windowNumber },
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
            let number = $(this).data("number");
            let status = $(this).data("status");

            $("#edit_window_id").val(id);
            $("#edit_window_number").val(number);
            $("#edit_window_status").val(status);

            $("#editWindowModal").modal("show");
        });

        // Update Window via AJAX
        $("#editWindowForm").submit(function(event) {
            event.preventDefault();
            let id = $("#edit_window_id").val();
            let number = $("#edit_window_number").val();
            let status = $("#edit_window_status").val();

            $.ajax({
                url: "function/update_window.php",
                type: "POST",
                data: { id: id, window_number: number, status: status },
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

        // Delete Window via AJAX
        $(".delete-btn").click(function() {
            let windowId = $(this).data("id");

            Swal.fire({
                title: "Are you sure?",
                text: "This window will be deleted permanently.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, delete it!",
                cancelButtonText: "Cancel"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "function/delete_window.php",
                        type: "POST",
                        data: { id: windowId },
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

<!-- Bootstrap JS -->
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>

</body>
</html>
