<?php
session_start();
include 'config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit();
}

// Fetch staff from database with window number and course names
$staff_result = $conn->query("
    SELECT staff.*, windows.id as window_id, windows.window_number, GROUP_CONCAT(courses.course_name SEPARATOR ', ') AS course_names 
    FROM staff 
    JOIN windows ON staff.window_number = windows.id 
    JOIN courses ON FIND_IN_SET(courses.id, staff.courses) 
    GROUP BY staff.id 
    ORDER BY staff.id ASC
");

// Fetch windows from database
$windows_result = $conn->query("SELECT * FROM windows ORDER BY id ASC");

// Fetch courses from database
$courses_result = $conn->query("SELECT * FROM courses ORDER BY id ASC");
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
    <title>Staff Management</title>
</head>
<body>
    <?php include 'top-bar.php'; ?>

    <div class="container mt-4">
        <h2 class="text-center">Staff Management</h2>
        
        <button class="btn btn-success mb-3" data-toggle="modal" data-target="#addStaffModal">
            <i class="fa fa-plus"></i> Add Staff
        </button>

        <table class="table table-bordered">
            <thead class="thead-dark">
                <tr>
                    <th>Staff Name</th>
                    <th>Window Number</th>
                    <th>Courses</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $staff_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['staff_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['window_number']); ?></td>
                        <td><?php echo htmlspecialchars($row['course_names']); ?></td>
                        <td>
                            <button class="btn btn-warning btn-sm edit-btn" 
                                data-id="<?php echo $row['id']; ?>" 
                                data-name="<?php echo htmlspecialchars($row['staff_name']); ?>"
                                data-window="<?php echo htmlspecialchars($row['window_id']); ?>"
                                data-courses="<?php echo htmlspecialchars($row['courses']); ?>">
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

    <!-- Add Staff Modal -->
    <div class="modal fade" id="addStaffModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Staff</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="addStaffForm">
                        <div class="form-group">
                            <label for="staff_name">Staff Name</label>
                            <input type="text" class="form-control" id="staff_name" name="staff_name" required>
                        </div>
                        <div class="form-group">
                            <label for="window_number">Window Number</label>
                            <select class="form-control" id="window_number" name="window_number" required>
                                <option value="">Select Window</option>
                                <?php while ($window = $windows_result->fetch_assoc()): ?>
                                    <option value="<?php echo $window['id']; ?>"><?php echo $window['window_number']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="courses">Courses</label>
                            <div>
                                <?php
                                $courses_result->data_seek(0); // Reset pointer
                                while ($course = $courses_result->fetch_assoc()): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="courses[]" value="<?php echo $course['id']; ?>" id="course_<?php echo $course['id']; ?>">
                                        <label class="form-check-label" for="course_<?php echo $course['id']; ?>">
                                            <?php echo $course['course_name']; ?>
                                        </label>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Staff</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Staff Modal -->
    <div class="modal fade" id="editStaffModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Staff</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="editStaffForm">
                        <input type="hidden" id="edit_id" name="id">
                        <div class="form-group">
                            <label for="edit_staff_name">Staff Name</label>
                            <input type="text" class="form-control" id="edit_staff_name" name="staff_name" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_window_number">Window Number</label>
                            <select class="form-control" id="edit_window_number" name="window_number" required>
                                <option value="">Select Window</option>
                                <?php
                                $windows_result->data_seek(0); // Reset pointer
                                while ($window = $windows_result->fetch_assoc()): ?>
                                    <option value="<?php echo $window['id']; ?>"><?php echo $window['window_number']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit_courses">Courses</label>
                            <div>
                                <?php
                                $courses_result->data_seek(0); // Reset pointer
                                while ($course = $courses_result->fetch_assoc()): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="courses[]" value="<?php echo $course['id']; ?>" id="edit_course_<?php echo $course['id']; ?>">
                                        <label class="form-check-label" for="edit_course_<?php echo $course['id']; ?>">
                                            <?php echo $course['course_name']; ?>
                                        </label>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="edit_password">Password</label>
                            <input type="password" class="form-control" id="edit_password" name="password">
                        </div>
                        <button type="submit" class="btn btn-primary">Update Staff</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            // Add Staff
            $('#addStaffForm').on('submit', function(e) {
                e.preventDefault();
                $.ajax({
                    type: 'POST',
                    url: 'function/add_staff.php',
                    data: $(this).serialize(),
                    success: function(response) {
                        const result = JSON.parse(response);
                        if (result.status === 'success') {
                            Swal.fire('Success', result.message, 'success').then(() => location.reload());
                        } else {
                            Swal.fire('Error', result.message, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        Swal.fire('Error', 'Failed to add staff: ' + error, 'error');
                    }
                });
            });

            // Edit Staff
            $('.edit-btn').on('click', function() {
    const id = $(this).data('id');
    const name = $(this).data('name');
    const window = $(this).data('window');
    const courses = $(this).data('courses') ? $(this).data('courses').toString().split(',') : [];

    $('#edit_id').val(id);
    $('#edit_staff_name').val(name);
    $('#edit_window_number').val(window); // This will now match with the select option values

    // Reset all checkboxes
    $('input[name="courses[]"]').prop('checked', false);

    // Check the appropriate courses
    courses.forEach(courseId => {
        const trimmedCourseId = courseId.trim();
        $(`input[name="courses[]"][value="${trimmedCourseId}"]`).prop('checked', true);
    });

    $('#editStaffModal').modal('show');
});

            $('#editStaffForm').on('submit', function(e) {
                e.preventDefault();
                $.ajax({
                    type: 'POST',
                    url: 'function/update_staff.php',
                    data: $(this).serialize(),
                    success: function(response) {
                        const result = JSON.parse(response);
                        if (result.status === 'success') {
                            Swal.fire('Success', result.message, 'success').then(() => location.reload());
                        } else {
                            Swal.fire('Error', result.message, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        Swal.fire('Error', 'Failed to update staff: ' + error, 'error');
                    }
                });
            });

            // Delete Staff
            $('.delete-btn').on('click', function() {
                const id = $(this).data('id');
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            type: 'POST',
                            url: 'function/delete_staff.php',
                            data: { id: id },
                            success: function(response) {
                                const result = JSON.parse(response);
                                if (result.status === 'success') {
                                    Swal.fire('Deleted!', result.message, 'success').then(() => location.reload());
                                } else {
                                    Swal.fire('Error', result.message, 'error');
                                }
                            },
                            error: function(xhr, status, error) {
                                Swal.fire('Error', 'Failed to delete staff: ' + error, 'error');
                            }
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>