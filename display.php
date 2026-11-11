<?php
require_once __DIR__ . '/connect.php';
require_once __DIR__ . '/auth.php';
require_auth();
$current_year = date('Y'); // Current year for filtering

$limit = 10; // Number of records to show per page

if (isset($_GET['live_search'])) {
    $query = trim((string)($_GET['query'] ?? ''));
    $terms = array_values(array_filter(array_map('trim', explode('+', $query))));
    $conditions = [];
    $parameters = [];
    foreach ($terms as $term) {
        $conditions[] = '(name LIKE ? OR idNumber LIKE ?)';
        $parameters[] = '%' . $term . '%';
        $parameters[] = '%' . $term . '%';
    }

    $where = $conditions ? 'WHERE ' . implode(' OR ', $conditions) : '';
    $statement = $conn->pdo()->prepare("SELECT * FROM student $where ORDER BY id DESC LIMIT 100");
    $statement->execute($parameters);
    $records = $statement->fetchAll();
    header('Content-Type: application/json');
    echo json_encode(['records' => $records, 'total' => count($records)]);
    exit();
}
// --- 1. DETERMINE CURRENT PAGE ---
// Get the current page number from the URL, default to 1
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
// Ensure the page number is at least 1
$page = max(1, $page);
// --- 2. CALCULATE TOTAL RECORDS ---
$total_result = $conn->query("SELECT COUNT(id) AS total FROM student");
$total_row = $total_result->fetch_assoc();
$total_records = $total_row['total'];

// --- 3. CALCULATE TOTAL PAGES ---
$total_pages = ceil($total_records / $limit);
// Adjust page if it exceeds total pages
$page = min($page, $total_pages);

// --- 4. CALCULATE OFFSET (SQL STARTING POINT) ---
$offset = ($page - 1) * $limit;

// --- 5. EXECUTE PAGINATED QUERY ---
$sql = "SELECT * FROM student ORDER BY id DESC LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

// Handle AJAX student data request (for info)
if (isset($_GET['get_student'])) {
    $id = (int)($_GET['id'] ?? 0);
    $sql = "SELECT * FROM student WHERE id = '$id'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
        echo json_encode($student);
    } else {
        echo json_encode(['error' => 'Student record not found']);
    }
    exit();
}
/* I use again and again for user input
 built fuction mysqli_real_escape_string
 to escape special char and prevent system from
 crashing and I use for input only under control of user */

if (isset($_POST['submit'])) {
    $is_edit = isset($_POST['is_edit']) ? (int)$_POST['is_edit'] : 0;
    $edit_id = isset($_POST['edit_id']) ? (int)$_POST['edit_id'] : 0;

    $name = isset($_POST['name']) ? htmlspecialchars(ucwords(strtolower(trim($_POST['name'])))) : '';
    $sex = isset($_POST['sex']) ? $_POST['sex'] : '';
    $idNumber = isset($_POST['idNumber']) ? trim($_POST['idNumber']) : '';
    $department = isset($_POST['department']) ? $_POST['department'] : '';
    $campus = isset($_POST['campus']) ? $_POST['campus'] : '';
    $pcSerialNumber = isset($_POST['pcSerialNumber']) ? trim($_POST['pcSerialNumber']) : '';
    $pcModel = isset($_POST['pcModel']) ? trim($_POST['pcModel']) : '';
    $contact = isset($_POST['contact']) ? trim($_POST['contact']) : '';
    $year = isset($_POST['year']) ? $_POST['year'] : '';

    // Handle file upload (no changes needed)
    $photo = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png'];

        if (in_array($file_ext, $allowed_ext)) {
            $new_filename = uniqid('photo_', true) . '.' . $file_ext;
            $target_file = $target_dir . $new_filename;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
                $photo = $target_file;
            }
        } else {
            $_SESSION['error'] = 'Only JPG, JPEG, PNG files are allowed for photo.';
        }
    }

    // Check if ID number exists
    $sql_check = "SELECT id FROM student WHERE idNumber = '$idNumber'";
    $result = $conn->query($sql_check);

    // Validate inputs
    if (empty($name)) {
        $_SESSION['error'] = 'Name is required.';
    } elseif (empty($idNumber)) {
        $_SESSION['error'] = 'ID Number is required.';
    } elseif (empty($department)) {
        $_SESSION['error'] = 'Department is required.';
    } elseif (empty($pcSerialNumber)) {
        $_SESSION['error'] = 'PC Serial Number is required.';
    } elseif ($result->num_rows && ($is_edit == 0 || ($is_edit == 1 && $edit_id != $result->fetch_assoc()['id']))) {
        $_SESSION['error'] = 'This ID Number already exists in the system!';
    } else {
        if ($is_edit == 1 && $edit_id > 0) {
            // Update existing student
            $sql = $conn->prepare("UPDATE student 
                SET name=?, sex=?, idNumber=?, department=?, campus=?, pcSerialNumber=?, pcModel=?, contact=?, photo=?, year=? 
                WHERE id=?");
            $sql->bind_param("sssssssssii", $name, $sex, $idNumber, $department, $campus, $pcSerialNumber, $pcModel, $contact, $photo, $year, $edit_id);

            if ($sql->execute()) {
                $_SESSION['success'] = "Student record updated successfully.";
                header("Location: display.php");
                exit();
            } else {
                $_SESSION['error'] = "Error updating record.";
            }
        } else {
            // Insert new student
            $sql = $conn->prepare("INSERT INTO student (name, sex, idNumber, department, campus, pcSerialNumber, pcModel, contact, photo, year) 
                                   VALUES (?,?,?,?,?,?,?,?,?,?)");
            $sql->bind_param("ssssssssss", $name, $sex, $idNumber, $department, $campus, $pcSerialNumber, $pcModel, $contact, $photo, $year);

            if ($sql->execute()) {
                $_SESSION['success'] = "Student record added successfully.";
                header("Location: display.php");
                exit();
            } else {
                $_SESSION['error'] = "Error inserting record.";
            }
        }
    }
}

// Handle delete request
if (isset($_GET['deleteid'])) {
    $id = (int)$_GET['deleteid'];

    $sql = "DELETE FROM student WHERE id='$id'";
    try {
        $conn->query($sql);
        $_SESSION['success'] = "Student record deleted successfully";
    } catch (Throwable $exception) {
        $_SESSION['error'] = "Error deleting student record.";
    }
    header("Location: display.php");
    exit();
}

// --- SEARCH LOGIC ---
$searchQuery = '';
$whereSql = '';
if (isset($_POST['search']) && !empty(trim($_POST['search_query']))) {
    $searchQuery = trim($_POST['search_query']);
    // Keep search query format consistent for display and use
    $searchQueryDisplay = ucwords(strtolower($searchQuery));

    // Build WHERE clause
    $searchTerms = explode('+', $searchQuery);
    $whereConditions = [];

    foreach ($searchTerms as $term) {
        $term = trim($term);
        if (!empty($term)) {
            // Use prepared statements for complex logic or mysqli_real_escape_string for simple, manual escaping
            $escapedTerm = escape_sqlite_value($term);
            $whereConditions[] = "(name LIKE '%$escapedTerm%' OR idNumber LIKE '%$escapedTerm%')";
        }
    }

    if (!empty($whereConditions)) {
        $whereSql = "WHERE " . implode(' OR ', $whereConditions);
    }
}
$search_param = '';
if (!empty($searchQuery)) {
    // URL-encode the search query for use in links
    $search_param = '&search_query=' . urlencode($searchQuery) . '&search=1';
}
// --- PAGINATION LOGIC ---

// 1. DETERMINE CURRENT PAGE
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);

// 2. CALCULATE TOTAL RECORDS (MUST incorporate search)
$total_result_query = "SELECT COUNT(id) AS total FROM student " . $whereSql;
$total_result = $conn->query($total_result_query);
$total_row = $total_result->fetch_assoc();
$total_records = $total_row['total'];

// 3. CALCULATE TOTAL PAGES
$total_pages = $total_records > 0 ? ceil($total_records / $limit) : 1;
$page = min($page, $total_pages); // Adjust page if it exceeds total pages

// 4. CALCULATE OFFSET (SQL STARTING POINT)
$offset = ($page - 1) * $limit;

// 5. EXECUTE PAGINATED QUERY (MUST incorporate search)
$sql = "SELECT * FROM student $whereSql ORDER BY id DESC LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

if (!$result) {
    die('Error executing paginated query: ' . $conn->error);
}
$num = 0;
$department = array(
    // College of Agriculture and Environmental Sciences (CAES)
    "Animal and Range Science",
    "Natural Resources and Environmental Science",
    "Plant Sciences",
    "Agricultural Economics and Agribusiness",
    "Rural Development and Agricultural Extension",

    //College of Business and Economics (CBE)
    "Accounting",
    "Cooperatives",
    "Management",
    "Economics",
    "Public Administration and Development Management",

    //College of Computing and Informatics
    "Computer Science",
    "Information Science",
    "Information Technology",
    "Software Engineering",
    "Statistics",

    // College of Education and Behavioral Sciences
    "Pedagogy",
    "Special Needs",
    "Educational Planning and Management",
    "English Language Improvement Centre",

    // College of Health and Medical Sciences
    "Medicine",
    "Pharmacy",
    "Nursing and Midwifery",
    "Public Health",
    "Environmental Health Sciences",
    "Medical Laboratory Science",

    //College of Law
    "Law",

    // College of Natural and Computational Sciences
    "Biology",
    "Chemistry",
    "Mathematics",
    "Physics",

    //College of Social Sciences and Humanities
    "Afan Oromo, Literature and Communication",
    "Gender and Development Studies",
    "Foreign Languages and Journalism",
    "History and Heritage Management",
    "Geography and Environmental Studies",
    "Sociology",

    // College of Veterinary Medicine
    "Veterinary Medicine",
    "Veterinary Laboratory Technology",

    // Haramaya Institute of Technology
    "Agricultural Engineering",
    "Water Resources and Irrigation Engineering",
    "Civil Engineering",
    "Electrical and Computer Engineering",
    "Mechanical Engineering",
    "Chemical Engineering",
    "Food Science and Post-Harvest Technology",
    "Food Technology and Process Engineering",

    // Sport Sciences Academy
    "Sport Sciences",

    // College of Agro-Industry and Land Resources
    "Land Administration",
    "Dairy and Meat Technology",
    "Forest Resource Management",
    "Soil Resources and Watershed Management",
);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PC Checkup System - Haramaya University</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>

    <header class="header text-center no-print">
        <h1>PC Checkup System</h1>
        <p class="lead mt-2">Haramaya University <span class="year-badge"><?= $current_year ?></span></p>
    </header>

    <div class="d-flex justify-content-center align-items-center flex-wrap my-3 no-print">


        <nav aria-label="Page navigation" class="order-2 order-md-2">
            <ul class="pagination pagination-sm mb-0">

                <li class="page-item <?= ($page <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=1<?= $search_param; ?>" aria-label="First">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>

                <li class="page-item <?= ($page <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?= $page - 1; ?><?= $search_param; ?>">Previous</a>
                </li>

                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?= $page + 1; ?><?= $search_param; ?>">Next</a>
                </li>

                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?= $total_pages; ?><?= $search_param; ?>" aria-label="Last">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
    <div id="recordsSummary" class="records-summary text-center small mt-2 no-print">
        Showing records <?= $total_records > 0 ? $offset + 1 : 0; ?> - <?= min($offset + $limit, $total_records); ?> of <?= $total_records; ?>
    </div>

    <div class="container">
        <?php if (isset($_SESSION['error'])): ?>
            <!-- <div class="alert alert-danger text-center"  id="error-message"> -->
            <div class="alert alert-danger text-center error-alert" id="error-message">
                <?= $_SESSION['error'];
                unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success text-center success-alert" id="success-message">
                <?= $_SESSION['success'];
                unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center gap-2 mb-4 no-print buto1">
            <a href="home" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Go Back
            </a>
            <div class="d-flex gap-2">
                <a href="profile.php" class="btn btn-outline-light">
                    <i class="bi bi-person-gear"></i> Edit Profile
                </a>
                <a href="logout.php" class="btn btn-outline-danger">
                    <i class="bi bi-box-arrow-right"></i> Log out
                </a>
                <button class="btn btn-primary" onclick="window.print()">
                    <i class="bi bi-printer"></i> Print
                </button>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center rounded p-3 mb-3 no-print records-toolbar">
            <div>
                <h2 class="h5 text-white mb-1">Computer Records</h2>
                <p class="text-white-50 mb-0">Manage registered student devices.</p>
            </div>
            <button id="toggleFormBtn" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#studentModal" type="button">
                <i class="bi bi-person-plus"></i> Add New Record
            </button>
        </div>

        <div class="modal fade" id="studentModal" tabindex="-1" aria-labelledby="studentModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="studentModalLabel"><i class="bi bi-person-plus me-2"></i>Add New Record</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form autocomplete="off" id="form1" method="POST" class="needs-validation" novalidate enctype="multipart/form-data">
                    <input type="hidden" name="edit_id" id="edit_id" value="0">
                    <input type="hidden" name="is_edit" id="is_edit" value="0">

                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="name">Full Name</label>
                                <input placeholder="John Doe" type="text" class="form-control" name="name" id="name" required>
                                <div class="invalid-feedback">Please enter student's name.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="sex">Gender</label>
                                <select class="form-select" name="sex" id="sex" required>
                                    <option value="" selected disabled>Select gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                                <div class="invalid-feedback">Please select gender.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="idNumber">ID Number</label>
                                <input placeholder="0998/16" type="text" class="form-control" name="idNumber" id="idNumber" required minlength="4">
                                <div class="invalid-feedback">Please enter an ID number with at least 4 characters.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="department">Department</label>
                                <select class="form-select" id="department" name="department" required>
                                    <option value="" selected disabled>Select a Department</option>
                                    <?php foreach ($department as $dep) : ?>
                                        <option value="<?= htmlspecialchars($dep, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($dep, ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Please select a department.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="campus">Campus</label>
                                <select class="form-select" name="campus" id="campus" required>
                                    <option value="" selected disabled>Select Campus</option>
                                    <option value="Main">Main</option>
                                    <option value="HiT">HiT</option>
                                    <option value="Station">Station</option>
                                    <option value="Harar">Harar</option>
                                </select>
                                <div class="invalid-feedback">Please choose a campus.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="pcSerialNumber">PC Serial Number</label>
                                <input placeholder="5#1234555" type="text" class="form-control" name="pcSerialNumber" id="pcSerialNumber" required>
                                <div class="invalid-feedback">Please enter the PC serial number.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="pcModel">PC Model</label>
                                <input placeholder="HP EliteBook" type="text" class="form-control" name="pcModel" id="pcModel">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="contact">Contact Number</label>
                                <input placeholder="099918272" type="tel" class="form-control" name="contact" id="contact">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="year">Year</label>
                                <select class="form-select" id="year" name="year" required>
                                    <option value="" selected disabled>Select Year</option>
                                    <?php for ($i = 1; $i <= 7; $i++): ?>
                                        <option value="<?= $i ?>">Year <?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="photo">Photo (Optional)</label>
                                <input type="file" class="form-control" name="photo" id="photo" accept="image/jpeg, image/png">
                                <div class="form-text">Only JPG and PNG images are accepted.</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button id="change" type="submit" class="btn btn-success" name="submit">
                            <i class="bi bi-save"></i> Save Record
                        </button>
                    </div>
                </form>
                </div>
            </div>
        </div>

        <div  class="search-box no-print my-3">
            <form id="searchForm" method="POST" class="input-group">
                <input autocomplete="off" type="search" class="form-control" placeholder="Search by name or ID..."
                    name="search_query" value="<?php echo htmlspecialchars($searchQuery); ?>">
                <span class="search-icon" aria-hidden="true"><i class="bi bi-search"></i></span>
            </form>
        </div>

        <div class="table-responsive cl1">
            <table class="table table-hover zoom-table">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Student Name</th>
                        <th>Photo</th>
                        <th>Gender</th>
                        <th>Batch</th>
                        <th>ID Number</th>
                        <th>Department</th>
                        <th>Campus</th>
                        <th>PC Serial</th>
                        <th class="no-print">PC Model</th>

                        <th class="no-print text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="recordsBody" class="table-dark">
                    <?php if ($result->num_rows > 0):
                        while ($row = $result->fetch_assoc()):
                            $num++;
                            $id = $row['id'];
                            $name = $row['name'];
                            $sex = $row['sex'];
                            $year = $row['year'];
                            $idNumber = $row['idNumber'];
                            $department = $row['department'];
                            $campus = $row['campus'];
                            $pcSerialNumber = $row['pcSerialNumber'];
                            $pcModel = $row['pcModel'];
                            $photo = $row['photo'];
                    ?>
                            <tr>
                                <td><?= $num; ?></td>
                                <td>
                                    <?= htmlspecialchars($name); ?>
                                </td>
                                <td>
                                    <span class="profile-photo-container">
                                        <i class="fas fa-user-circle profile-photo-icon" aria-hidden="true"></i>

                                        <img src="<?= htmlspecialchars($photo) ?>"
                                            alt="<?= htmlspecialchars($name) ?>'s photo"
                                            class="profile-photo-img"
                                            onerror="this.style.display='none'; this.previousElementSibling.style.display='block';">
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($sex); ?></td>
                                <td><?= htmlspecialchars($year); ?></td>
                                <td class="id-cell">
                                    <?= htmlspecialchars($idNumber); ?>
                                </td>
                                <td><?= htmlspecialchars($department); ?></td>
                                <td><?= htmlspecialchars($campus); ?></td>
                                <td class="serial-cell">
                                    <?= htmlspecialchars($pcSerialNumber); ?>
                                </td>
                                <td class="model-cell psm no-print">
                                    <?= htmlspecialchars($pcModel); ?>
                                </td>

                                <td class="no-print text-center">
                                    <button class="btn btn-sm btn-primary" onclick="editStudent(<?= $id; ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>

                                    <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?= $id; ?>)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <button class="btn btn-sm btn-info" onclick="viewProfile(<?= $id; ?>)">
                                        <i class="bi bi-info-circle"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="11">
                                <div class="alert alert-secondary text-center my-3 py-3" role="alert">
                                    <h5 class="alert-heading text-secondary">
                                        <i class="bi bi-calendar-x-fill me-2"></i> No Checkup Data Available
                                    </h5>
                                    <p class="mb-2">
                                        No PC checkup records found
                                        <?php if (!empty($searchQuery)): ?>
                                            matching "<strong><?= htmlspecialchars($searchQueryDisplay); ?></strong>".
                                        <?php else: ?>
                                            for the system.
                                        <?php endif; ?>
                                    </p>
                                    <hr>
                                    <p class="mb-0 small text-muted">
                                        To begin tracking, use the **"Add New Record"** button above.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
    </div>
    </div>

    <!-- Delete Confirmation Modal section -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this record? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Delete</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile View Overlay section -->
    <div id="profileOverlay" class="overlay">
        <div class="overlay-content">
            <button style="margin-left: 86%;" class="btn btn-danger mt-1" onclick="closeProfile()">Close</button>
            <div id="profileContent"></div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // View student profile
        function viewProfile(id) {
            $.get('', {
                get_student: 1,
                id: id
            }, function(data) {
                try {
                    var student = JSON.parse(data);
                    if (student.error) {
                        alert(student.error);
                        return;
                    }

                    var photo = student.photo || 'assets/default-profile.jpg';

                    var profileHtml = `
                    <div class="profile-header text-center">
                        <img src="${photo}" class="profile-img mb-2" alt="Student Photo">
                        <h3>${student.name}</h3>
                    </div>
                    <div class="profile-details">
                        <div class="row detail-row">
                            <div class="col-md-4 detail-label">Gender:</div>
                            <div class="col-md-8">${student.sex}</div>
                        </div>
                        <div class="row detail-row">
                            <div class="col-md-4 detail-label">ID Number:</div>
                            <div class="col-md-8">${student.idNumber}</div>
                        </div>
                        <div class="row detail-row">
                            <div class="col-md-4 detail-label">Department:</div>
                            <div class="col-md-8">${student.department}</div>
                        </div>
                        <div class="row detail-row">
                            <div class="col-md-4 detail-label">Batch:</div>
                            <div class="col-md-8">${student.year}</div>
                        </div>
                        <div class="row detail-row">
                            <div class="col-md-4 detail-label">Campus:</div>
                            <div class="col-md-8">${student.campus}</div>
                        </div>
                        <div class="row detail-row">
                            <div class="col-md-4 detail-label">PC Serial Number:</div>
                            <div class="col-md-8">${student.pcSerialNumber}</div>
                        </div>
                        <div class="row detail-row">
                            <div class="col-md-4 detail-label">PC Model:</div>
                            <div class="col-md-8">${student.pcModel || 'Not specified'}</div>
                        </div>
                        <div class="row detail-row">
                            <div class="col-md-4 detail-label">Contact:</div>
                            <div class="col-md-8">${student.contact || 'Not provided'}</div>
                        </div>
                    </div>
                `;

                    $('#profileContent').html(profileHtml);
                    $('#profileOverlay').show();
                } catch (e) {
                    console.error('Error parsing student data:', e);
                    alert('Error loading student profile');
                }
            }).fail(function() {
                alert('Failed to load student profile');
            });
        }

        // Close profile view
        function closeProfile() {
            $('#profileOverlay').hide();
        }


        // Delete confirmation
        function confirmDelete(id) {
            var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            var deleteBtn = document.getElementById('confirmDeleteBtn');

            deleteBtn.href = 'display.php?deleteid=' + id;
            deleteModal.show();
        }

        function editStudent(id) {
            $.get('', {
                    get_student: 1,
                    id: id
                },
                function(data) {

                    try {
                        console.log("Editing student ID:", id);

                        const student = JSON.parse(data);
                        console.log('Data returned:', data);
                        if (student.error) {
                            alert(student.error);
                            return;
                        }
                        // Populate form with student data
                        $('#edit_id').val(student.id);
                        $('#is_edit').val(1);
                        $('#name').val(student.name);
                        $('#sex').val(student.sex);
                        $('#idNumber').val(student.idNumber);
                        $('#department').val(student.department);
                        $('#campus').val(student.campus);
                        $('#pcSerialNumber').val(student.pcSerialNumber);
                        $('#pcModel').val(student.pcModel);
                        $('#contact').val(student.contact);
                        $('#year').val(student.year);


                        $('#studentModalLabel').html('<i class="bi bi-pencil me-2"></i>Edit Student');
                        $('#change').html('<i class="bi bi-pencil"></i> Save Changes');
                        bootstrap.Modal.getOrCreateInstance(document.getElementById('studentModal')).show();

                    } catch (e) {
                        console.error('Error parsing student data:', e);
                        alert('Error loading student data');
                    }
                });
        }
        //to remove alert messages after displayed in defined period of time (mine is 3-second).
        setTimeout(function() {
            const alertElement1 = document.getElementById('error-message');
            const alertElement2 = document.getElementById('success-message');

            if (alertElement1) {
                var bootstrapAlert1 = new bootstrap.Alert(alertElement1);
                bootstrapAlert1.close();
            }

            if (alertElement2) {
                var bootstrapAlert2 = new bootstrap.Alert(alertElement2);
                bootstrapAlert2.close();
            }
        }, 3000);
        document.getElementById("form1").addEventListener("submit", function(event) {
            if (!this.checkValidity() || document.getElementById("idNumber").value.length < 4) {
                event.preventDefault();
                event.stopPropagation();
            }
            this.classList.add("was-validated");
        });

        document.getElementById("toggleFormBtn").addEventListener("click", function() {
            document.getElementById("form1").reset();
            document.getElementById("edit_id").value = "0";
            document.getElementById("is_edit").value = "0";
            document.getElementById("studentModalLabel").innerHTML = '<i class="bi bi-person-plus me-2"></i>Add New Record';
            document.getElementById("change").innerHTML = '<i class="bi bi-save"></i> Save Record';
            document.getElementById("form1").classList.remove("was-validated");
        });

        const searchForm = document.getElementById("searchForm");
        const searchInput = searchForm.querySelector('input[name="search_query"]');
        const recordsBody = document.getElementById("recordsBody");
        const recordsSummary = document.getElementById("recordsSummary");
        const pagination = document.querySelector(".pagination");
        let searchTimer;

        function escapeHtml(value) {
            return String(value ?? "").replace(/[&<>"']/g, function(character) {
                return {
                    "&": "&amp;",
                    "<": "&lt;",
                    ">": "&gt;",
                    '"': "&quot;",
                    "'": "&#039;"
                }[character];
            });
        }

        function renderLiveRecords(records) {
            if (!records.length) {
                recordsBody.innerHTML = `
                    <tr><td colspan="11">
                        <div class="alert alert-secondary text-center my-3 py-3">
                            <h5 class="alert-heading text-secondary"><i class="bi bi-search me-2"></i>No matching records</h5>
                            <p class="mb-0">Try a different name or ID number.</p>
                        </div>
                    </td></tr>`;
                return;
            }

            recordsBody.innerHTML = records.map(function(record, index) {
                const photo = escapeHtml(record.photo || "");
                const name = escapeHtml(record.name);
                return `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${name}</td>
                        <td>
                            <span class="profile-photo-container">
                                <i class="fas fa-user-circle profile-photo-icon" aria-hidden="true"></i>
                                <img src="${photo}" alt="${name}'s photo" class="profile-photo-img"
                                    onerror="this.style.display='none'; this.previousElementSibling.style.display='block';">
                            </span>
                        </td>
                        <td>${escapeHtml(record.sex)}</td>
                        <td>${escapeHtml(record.year)}</td>
                        <td class="id-cell">${escapeHtml(record.idNumber)}</td>
                        <td>${escapeHtml(record.department)}</td>
                        <td>${escapeHtml(record.campus)}</td>
                        <td class="serial-cell">${escapeHtml(record.pcSerialNumber)}</td>
                        <td class="model-cell psm no-print">${escapeHtml(record.pcModel)}</td>
                        <td class="no-print text-center">
                            <button class="btn btn-sm btn-primary" onclick="editStudent(${Number(record.id)})"><i class="bi bi-pencil"></i></button>
                            <button class="btn btn-sm btn-danger" onclick="confirmDelete(${Number(record.id)})"><i class="bi bi-trash"></i></button>
                            <button class="btn btn-sm btn-info" onclick="viewProfile(${Number(record.id)})"><i class="bi bi-info-circle"></i></button>
                        </td>
                    </tr>`;
            }).join("");
        }

        searchForm.addEventListener("submit", function(event) {
            event.preventDefault();
        });

        searchInput.addEventListener("input", function() {
            clearTimeout(searchTimer);
            const query = searchInput.value.trim();
            searchTimer = setTimeout(function() {
                fetch("display.php?live_search=1&query=" + encodeURIComponent(query), {
                    headers: { "Accept": "application/json" }
                })
                    .then(function(response) {
                        if (!response.ok) throw new Error("Search request failed");
                        return response.json();
                    })
                    .then(function(data) {
                        renderLiveRecords(data.records);
                        recordsSummary.textContent = data.total
                            ? "Showing " + data.total + " matching record" + (data.total === 1 ? "" : "s")
                            : "No matching records";
                        if (pagination) pagination.closest("nav").style.visibility = query ? "hidden" : "visible";
                    })
                    .catch(function() {
                        recordsSummary.textContent = "Unable to update search results.";
                    });
            }, 250);
        });
    </script>
</body>
</body>

</html>
