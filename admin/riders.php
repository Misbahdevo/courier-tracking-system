<?php
/**
 * Admin Manage Riders
 * Courier & Parcel Tracking System
 */

$page_title = "Manage Riders - SwiftPost";
$path_prefix = "../";
require_once $path_prefix . 'includes/header.php';

// Guard admin access
guard_admin();

$active_tab = 'riders';

$errors = [];
$success_msg = '';

// Handle Add/Edit Rider Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_rider'])) {
    $rider_id = isset($_POST['rider_id']) ? intval($_POST['rider_id']) : 0;
    $name = sanitize($conn, $_POST['name']);
    $phone = sanitize($conn, $_POST['phone']);
    $area = sanitize($conn, $_POST['area']);
    $username = sanitize($conn, $_POST['username']);
    $password = $_POST['password'];
    
    // Validations
    if (empty($name)) $errors[] = "Rider name is required.";
    if (empty($phone)) $errors[] = "Phone number is required.";
    if (empty($area)) $errors[] = "Delivery area is required.";
    if (empty($username)) $errors[] = "Login username is required.";
    
    // Check username uniqueness
    if (empty($errors)) {
        $check_q = "SELECT id FROM riders WHERE username = ? AND id != ?";
        $stmt = mysqli_prepare($conn, $check_q);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "si", $username, $rider_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $errors[] = "Username '$username' is already taken by another rider.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    
    // Save to Database
    if (empty($errors)) {
        if ($rider_id > 0) {
            // Edit Rider
            if (!empty($password)) {
                // Update with new password
                $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
                $query = "UPDATE riders SET name = ?, phone = ?, area = ?, username = ?, password = ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "sssssi", $name, $phone, $area, $username, $hashed_pass, $rider_id);
            } else {
                // Update without password change
                $query = "UPDATE riders SET name = ?, phone = ?, area = ?, username = ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "ssssi", $name, $phone, $area, $username, $rider_id);
            }
            
            if (mysqli_stmt_execute($stmt)) {
                set_flash_message('success', "Rider '$name' updated successfully.");
                header("Location: riders.php");
                exit();
            } else {
                $errors[] = "Failed to update rider record.";
            }
            mysqli_stmt_close($stmt);
        } else {
            // Create Rider
            if (empty($password)) {
                $errors[] = "Password is required for a new rider account.";
            } else {
                $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
                $query = "INSERT INTO riders (name, phone, area, username, password, status) VALUES (?, ?, ?, ?, ?, 'active')";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "sssss", $name, $phone, $area, $username, $hashed_pass);
                if (mysqli_stmt_execute($stmt)) {
                    set_flash_message('success', "Rider '$name' created successfully.");
                    header("Location: riders.php");
                    exit();
                } else {
                    $errors[] = "Failed to create rider record.";
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}

// Handle Status Toggle (Deactivate / Activate)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $r_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    if ($r_id > 0) {
        $status = ($action === 'activate') ? 'active' : 'inactive';
        $toggle_q = "UPDATE riders SET status = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $toggle_q);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "si", $status, $r_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            set_flash_message('success', "Rider status changed to '$status' successfully.");
        }
    }
    header("Location: riders.php");
    exit();
}

// Handle Purge/Delete Rider
if (isset($_GET['delete_id'])) {
    $del_id = intval($_GET['delete_id']);
    if ($del_id > 0) {
        $del_q = "DELETE FROM riders WHERE id = ?";
        $stmt = mysqli_prepare($conn, $del_q);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $del_id);
            if (mysqli_stmt_execute($stmt)) {
                set_flash_message('success', "Rider deleted successfully.");
            } else {
                set_flash_message('danger', "Failed to delete rider. They may have active shipments linked.");
            }
            mysqli_stmt_close($stmt);
        }
    }
    header("Location: riders.php");
    exit();
}

// Check Edit Mode
$edit_rider = null;
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $edit_q = mysqli_query($conn, "SELECT * FROM riders WHERE id = $edit_id LIMIT 1");
    if ($edit_q && $row = mysqli_fetch_assoc($edit_q)) {
        $edit_rider = $row;
    }
}

// Fetch all riders
$riders = [];
$q_riders = mysqli_query($conn, "SELECT * FROM riders ORDER BY created_at DESC");
if ($q_riders) {
    while ($row = mysqli_fetch_assoc($q_riders)) {
        $riders[] = $row;
    }
}
?>

<div class="row">
    <!-- Admin Sidebar -->
    <div class="col-lg-3 col-md-4 mb-4">
        <div class="card glass-card border-0 shadow-sm">
            <div class="list-group list-group-flush rounded-3">
                <a href="dashboard.php" class="list-group-item list-group-item-action py-3 px-4 fw-semibold <?php echo ($active_tab === 'dashboard') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-chart-line me-2"></i> Dashboard
                </a>
                <a href="parcels.php" class="list-group-item list-group-item-action py-3 px-4 fw-semibold <?php echo ($active_tab === 'parcels') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-boxes-packing me-2"></i> Manage Parcels
                </a>
                <a href="riders.php" class="list-group-item list-group-item-action py-3 px-4 fw-semibold <?php echo ($active_tab === 'riders') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-motorcycle me-2"></i> Manage Riders
                </a>
                <a href="customers.php" class="list-group-item list-group-item-action py-3 px-4 fw-semibold <?php echo ($active_tab === 'customers') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-users me-2"></i> Manage Customers
                </a>
                <a href="reports.php" class="list-group-item list-group-item-action py-3 px-4 fw-semibold <?php echo ($active_tab === 'reports') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-file-invoice me-2"></i> Reports
                </a>
            </div>
        </div>
    </div>
    
    <!-- Main Content Area -->
    <div class="col-lg-9 col-md-8">
        <h2 class="fw-bold mb-4">Manage Delivery Riders</h2>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger mb-4">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Add / Edit Rider Form Card -->
            <div class="col-xl-4 order-xl-2">
                <div class="card glass-card border-0 shadow-sm sticky-top" style="top: 80px; z-index: 2;">
                    <div class="card-body p-4">
                        <h4 class="fw-bold mb-3 text-indigo">
                            <?php echo $edit_rider ? '<i class="fa-regular fa-pen-to-square me-1"></i> Edit Rider' : '<i class="fa-solid fa-user-plus me-1"></i> Add Rider'; ?>
                        </h4>
                        
                        <form action="riders.php" method="POST">
                            <input type="hidden" name="rider_id" value="<?php echo $edit_rider ? $edit_rider['id'] : ''; ?>">
                            
                            <!-- Rider Name -->
                            <div class="mb-3">
                                <label for="name" class="form-label fw-semibold small text-muted text-uppercase">Full Name</label>
                                <input type="text" class="form-control form-control-sm" id="name" name="name" value="<?php echo $edit_rider ? htmlspecialchars($edit_rider['name']) : ''; ?>" placeholder="e.g. John Doe" required>
                            </div>
                            
                            <!-- Phone -->
                            <div class="mb-3">
                                <label for="phone" class="form-label fw-semibold small text-muted text-uppercase">Phone</label>
                                <input type="text" class="form-control form-control-sm" id="phone" name="phone" value="<?php echo $edit_rider ? htmlspecialchars($edit_rider['phone']) : ''; ?>" placeholder="e.g. +123456789" required>
                            </div>
                            
                            <!-- Delivery Area -->
                            <div class="mb-3">
                                <label for="area" class="form-label fw-semibold small text-muted text-uppercase">Delivery Area</label>
                                <input type="text" class="form-control form-control-sm" id="area" name="area" value="<?php echo $edit_rider ? htmlspecialchars($edit_rider['area']) : ''; ?>" placeholder="e.g. Downtown, Uptown" required>
                            </div>
                            
                            <!-- Username -->
                            <div class="mb-3">
                                <label for="username" class="form-label fw-semibold small text-muted text-uppercase">Username</label>
                                <input type="text" class="form-control form-control-sm" id="username" name="username" value="<?php echo $edit_rider ? htmlspecialchars($edit_rider['username']) : ''; ?>" placeholder="e.g. rider_john" required>
                            </div>
                            
                            <!-- Password -->
                            <div class="mb-4">
                                <label for="password" class="form-label fw-semibold small text-muted text-uppercase">
                                    Password <?php echo $edit_rider ? '<small class="text-muted">(Leave blank to keep same)</small>' : ''; ?>
                                </label>
                                <input type="password" class="form-control form-control-sm" id="password" name="password" placeholder="••••••••" <?php echo $edit_rider ? '' : 'required'; ?>>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" name="save_rider" class="btn btn-primary btn-sm flex-grow-1">
                                    <i class="fa-solid fa-floppy-disk me-1"></i> Save
                                </button>
                                <?php if ($edit_rider): ?>
                                    <a href="riders.php" class="btn btn-outline-secondary btn-sm">Cancel</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Riders List Card -->
            <div class="col-xl-8 order-xl-1">
                <div class="card glass-card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Rider Details</th>
                                        <th>Area</th>
                                        <th>Username</th>
                                        <th>Status</th>
                                        <th class="pe-4 text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($riders)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-5 text-muted">
                                                <i class="fa-solid fa-users-slash fa-3x mb-3"></i>
                                                <h5>No riders found</h5>
                                                <p class="small">Add a rider using the form on the right.</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($riders as $r): ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="fw-semibold text-dark"><?php echo htmlspecialchars($r['name']); ?></div>
                                                    <small class="text-muted"><i class="fa-solid fa-phone me-1 small"></i><?php echo htmlspecialchars($r['phone']); ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($r['area']); ?></span>
                                                </td>
                                                <td>
                                                    <code><?php echo htmlspecialchars($r['username']); ?></code>
                                                </td>
                                                <td>
                                                    <?php if ($r['status'] === 'active'): ?>
                                                        <span class="status-badge badge-delivered">Active</span>
                                                    <?php else: ?>
                                                        <span class="status-badge badge-pending">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="pe-4 text-center">
                                                    <div class="btn-group btn-group-sm">
                                                        <!-- Edit -->
                                                        <a href="riders.php?edit_id=<?php echo $r['id']; ?>" class="btn btn-outline-primary" title="Edit Rider">
                                                            <i class="fa-solid fa-pencil"></i>
                                                        </a>
                                                        
                                                        <!-- Status Toggle -->
                                                        <?php if ($r['status'] === 'active'): ?>
                                                            <a href="riders.php?action=deactivate&id=<?php echo $r['id']; ?>" class="btn btn-outline-warning" title="Deactivate Rider" onclick="return confirm('Are you sure you want to deactivate this rider? They will not be able to log in.')">
                                                                <i class="fa-solid fa-ban"></i>
                                                            </a>
                                                        <?php else: ?>
                                                            <a href="riders.php?action=activate&id=<?php echo $r['id']; ?>" class="btn btn-outline-success" title="Activate Rider">
                                                                <i class="fa-solid fa-check"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                        
                                                        <!-- Delete -->
                                                        <a href="riders.php?delete_id=<?php echo $r['id']; ?>" class="btn btn-outline-danger" title="Delete Rider" onclick="return confirm('Are you sure you want to delete this rider completely? This will clear their assignment details.')">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</div>

<?php 
require_once $path_prefix . 'includes/footer.php'; 
?>
