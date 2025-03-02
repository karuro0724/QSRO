<?php
session_start();
include 'config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit();
}

// Fetch courses from database
$result = $conn->query("SELECT * FROM courses ORDER BY id ASC");
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

    <h2 class="text-center">Course Management</h2>
    
    <button class="btn btn-success mb-3" data-toggle="modal" data-target="#addCourseModal">
        <i class="fa fa-plus"></i> Add Course
    </button>

    <table class="table table-bordered">
        <thead class="thead-dark">
            <tr>
                <th>Course Name</th>
                <th>Description</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['course_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['description']); ?></td>
                    <td>
                        <button class="btn btn-warning btn-sm edit-btn" 
                            data-id="<?php echo $row['id']; ?>" 
                            data-name="<?php echo htmlspecialchars($row['course_name']); ?>"
                            data-description="<?php echo htmlspecialchars($row['description']); ?>">
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

<!-- Add Course Modal -->
<div class="modal fade" id="addCourseModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Course</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="addCourseForm">
                    <div class="form-group">
                        <label for="course_name">Course Name</label>
                        <input type="text" class="form-control" id="course_name" name="course_name" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Course</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Course Modal -->
<div class="modal fade" id="editCourseModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Course</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editCourseForm">
                    <input type="hidden" id="edit_course_id">
                    <div class="form-group">
                        <label for="edit_course_name">Course Name</label>
                        <input type="text" class="form-control" id="edit_course_name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_description">Description</label>
                        <textarea class="form-control" id="edit_description" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Course</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- jQuery AJAX -->
<script>
    // Add Course via AJAX
    $("#addCourseForm").submit(function(event) {
        event.preventDefault();
        let courseName = $("#course_name").val();
        let description = $("#description").val();

        $.ajax({
            url: "function/add_course.php",
            type: "POST",
            data: { course_name: courseName, description: description },
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
            let description = $(this).data("description");

            $("#edit_course_id").val(id);
            $("#edit_course_name").val(name);
            $("#edit_description").val(description);

            $("#editCourseModal").modal("show");
        });

        // Update Course via AJAX
        $("#editCourseForm").submit(function(event) {
            event.preventDefault();
            let id = $("#edit_course_id").val();
            let name = $("#edit_course_name").val();
            let description = $("#edit_description").val();

            $.ajax({
                url: "function/update_course.php",
                type: "POST",
                data: { id: id, course_name: name, description: description },
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

        // Delete Course via AJAX
        $(".delete-btn").click(function() {
            let courseId = $(this).data("id");

            Swal.fire({
                title: "Are you sure?",
                text: "This course will be deleted permanently.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, delete it!",
                cancelButtonText: "Cancel"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "function/delete_course.php",
                        type: "POST",
                        data: { id: courseId },
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