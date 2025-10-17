<?php
$page_title = 'Edit Profile';
require_once('includes/load.php');

$current_user = current_user();
$user_id = (int)$current_user['id'];

$user = find_by_id('users', $user_id);
$departments = find_all('departments');

if (!$user) {
    $session->msg("d", "User not found.");
    redirect('index.php', false);
}

// Handle profile update
if (isset($_POST['update_profile'])) {
    $req_fields = array('name', 'username', 'department_id');
    validate_fields($req_fields);

    if (empty($errors)) {
        $name     = remove_junk($db->escape($_POST['name']));
        $username = remove_junk($db->escape($_POST['username']));
        $dep_id   = (int)$db->escape($_POST['department_id']);

        $password_sql = "";
        if (!empty($_POST['password'])) {
            $password = remove_junk($db->escape($_POST['password']));
            $hashed_password = sha1($password);
            $password_sql = ", password='{$hashed_password}'";
        }

        $sql = "UPDATE users 
                SET name='{$name}', username='{$username}', department='{$dep_id}' {$password_sql}
                WHERE id='{$user_id}'";

        if ($db->query($sql)) {
            $session->msg('s', 'Profile updated successfully.');
            redirect('edit_prof.php', false);
            exit();
        } else {
            $session->msg('d', 'Update profile failed.');
        }
    } else {
        $session->msg("d", $errors);
    }
}

// Handle image upload
if (isset($_POST['submit_image'])) {
    if (isset($_FILES['user_image'])) {
        $photo = new Media();
        $photo->upload($_FILES['user_image']);
        if ($photo->process_user($user_id)) {
            $session->msg('s', 'Photo has been uploaded.');
            redirect('edit_prof.php');
        } else {
            $session->msg('d', join($photo->errors));
            redirect('edit_prof.php');
        }
    }
}

$back_url = 'admin.php'; // default

if ($current_user['user_level'] == 1) {
    $back_url = 'admin.php';
} elseif ($current_user['user_level'] == 3) {
    $back_url = 'home.php';
} elseif ($current_user['user_level'] == 2) {
    $back_url = 'super_admin.php';
}

?>

<?php include_once('layouts/header.php');

$msg = $session->msg(); // get the flashed message

if (!empty($msg) && is_array($msg)):
    $type = key($msg);        // "danger", "success", etc.
    $text = $msg[$type];      // The message itself
?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: '<?php echo $type === "danger" ? "error" : $type; ?>',
                title: '<?php echo ucfirst($type); ?>',
                text: '<?php echo addslashes($text); ?>',
                confirmButtonText: 'OK',
                background: '#f8f9fa',
                confirmButtonColor: '#1e7e34'
            });
        });
    </script>
<?php endif; ?>

<style>
    :root {
        --primary-green: #1e7e34;
        --dark-green: #155724;
        --light-green: #28a745;
        --accent-green: #34ce57;
        --primary-yellow: #ffc107;
        --dark-yellow: #e0a800;
        --light-yellow: #ffda6a;
        --card-bg: #ffffff;
        --text-dark: #343a40;
        --text-light: #6c757d;
        --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        --hover-shadow: 0 8px 25px rgba(30, 126, 52, 0.15);
    }

    /* Header Styling */
    .profile-header {
        background: linear-gradient(135deg, var(--primary-green) 0%, var(--dark-green) 100%);
        color: white;
        padding: 2rem;
        border-radius: 15px;
        margin-bottom: 2rem;
        box-shadow: var(--card-shadow);
        border-left: 5px solid var(--primary-yellow);
    }

    .profile-header h1 {
        margin: 0;
        font-weight: 700;
        font-size: 2rem;
    }

    .profile-header .subtitle {
        opacity: 0.9;
        font-size: 1rem;
    }

    /* Card Styling */
    .profile-card {
        border: none;
        border-radius: 15px;
        box-shadow: var(--card-shadow);
        transition: all 0.3s ease;
        margin-bottom: 1.5rem;
        border-top: 5px solid var(--primary-green);
        overflow: hidden;

    }

    .profile-card:hover {
        box-shadow: var(--hover-shadow);
        transform: translateY(-2px);
    }



    .card-header-custom i {
        color: var(--primary-yellow);
    }

    /* Profile Image */
    .profile-image-container {
        position: relative;
        display: inline-block;
    }

    .profile-image {
        width: 200px;
        height: 200px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid var(--primary-green);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        transition: all 0.3s ease;
    }

    .profile-image:hover {
        border-color: var(--primary-yellow);
        transform: scale(1.05);
    }

    /* Form Styling */
    .form-label {
        font-weight: 600;
        color: var(--dark-green);
        margin-bottom: 0.5rem;
    }

    .form-control,
    .form-select {
        border: 2px solid #e9ecef;
        border-radius: 10px;
        padding: 0.75rem 1rem;
        transition: all 0.3s ease;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: var(--primary-green);
        box-shadow: 0 0 0 0.2rem rgba(30, 126, 52, 0.25);
    }

    /* Buttons */
    .btn-custom {
        border-radius: 10px;
        font-weight: 600;
        padding: 0.75rem 1.5rem;
        transition: all 0.3s ease;
        border: none;
    }

    .btn-success-custom {
        background: linear-gradient(135deg, var(--primary-green), var(--dark-green));
        color: white;
    }

    .btn-success-custom:hover {
        background: linear-gradient(135deg, var(--dark-green), #0f4019);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(30, 126, 52, 0.3);
    }

    .btn-warning-custom {
        background: linear-gradient(135deg, var(--primary-yellow), var(--dark-yellow));
        color: #000;
    }

    .btn-warning-custom:hover {
        background: linear-gradient(135deg, var(--dark-yellow), #bf8f00);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3);
    }

    .btn-secondary-custom {
        background: linear-gradient(135deg, #6c757d, #5a6268);
        color: white;
    }

    .btn-secondary-custom:hover {
        background: linear-gradient(135deg, #5a6268, #495057);
        transform: translateY(-2px);
    }

    /* File Upload */
    .file-upload-container {
        position: relative;
    }

    .file-upload-container input[type="file"] {
        border: 2px dashed #dee2e6;
        border-radius: 10px;
        padding: 1rem;
        transition: all 0.3s ease;
    }

    .file-upload-container input[type="file"]:hover {
        border-color: var(--primary-green);
        background-color: rgba(40, 167, 69, 0.05);
    }

    /* Animation */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .profile-card {
        animation: fadeInUp 0.6s ease forwards;
    }

    .profile-card:nth-child(1) {
        animation-delay: 0.1s;
    }

    .profile-card:nth-child(2) {
        animation-delay: 0.2s;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .profile-header {
            padding: 1.5rem;
            text-align: center;
        }

        .profile-header h1 {
            font-size: 1.5rem;
        }

        .profile-image {
            width: 150px;
            height: 150px;
        }

        .btn-custom {
            padding: 0.6rem 1rem;
            font-size: 0.9rem;
        }
    }

    /* User Info Badges */
    .user-info-badge {
        background: linear-gradient(135deg, var(--light-green), var(--primary-green));
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.9rem;
    }

    /* Password Strength Indicator */
    .password-strength {
        height: 4px;
        border-radius: 2px;
        margin-top: 0.5rem;
        transition: all 0.3s ease;
    }

    .strength-weak {
        background: #dc3545;
        width: 25%;
    }

    .strength-medium {
        background: #ffc107;
        width: 50%;
    }

    .strength-strong {
        background: #28a745;
        width: 100%;
    }
</style>

<div class="container mt-4">
    <!-- Profile Header -->
    <div class="profile-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1><i class="fa-solid fa-user-edit me-2 "></i> Edit Profile</h1>
                <div class="subtitle">Update your personal information and profile picture</div>
            </div>
            <div class="text-end">
                <div class="user-info-badge">
                    <i class="fa-solid fa-id-card me-1"></i>
                    <?php
                    $user_levels = [1 => 'Admin', 2 => 'IT', 3 => 'User'];
                    echo $user_levels[$current_user['user_level']];
                    ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Profile Picture Section -->
        <div class="col-md-4">
            <div class="profile-card">
                <div class="card-header-custom">
                    <h4 class="mb-0"><i class="fa-solid fa-camera me-2 p-2"></i> Profile Picture</h4>
                </div>
                <div class="card-body text-center p-4">
                    <div class="profile-image-container mb-4">
                        <img src="uploads/users/<?php echo $user['image'] ?: 'no_image.png'; ?>"
                            class="profile-image"
                            alt="User Image"
                            onerror="this.src='uploads/users/no_image.png'">
                    </div>

                    <form method="post" action="edit_prof.php" enctype="multipart/form-data">
                        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                        <div class="mb-3 file-upload-container">
                            <input class="p-3" type="file" name="user_image" class="form-control" accept="image/*" required>
                            <small class="text-muted mt-2 d-block">Supported formats: JPG, PNG, GIF (Max: 2MB)</small>
                        </div>
                        <button type="submit" name="submit_image" class="btn btn-warning-custom w-100 btn-custom">
                            <i class="fa-solid fa-upload me-2"></i> Upload New Image
                        </button>
                    </form>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="profile-card">
                <div class="card-header-custom">
                    <h4 class="mb-0"><i class="fa-solid fa-chart-simple me-2 p-2"></i> Profile Info</h4>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted">Member Since</small>
                        <div class="fw-bold text-success"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></div>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Last Login</small>
                        <div class="fw-bold text-success"><?php echo date('M j, Y g:i A'); ?></div>
                    </div>
                    <div>
                        <small class="text-muted">Account Status</small>
                        <div>
                            <span class="badge bg-success">
                                <i class="fa-solid fa-circle-check me-1"></i>Active
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Details Section -->
        <div class="col-md-8">
            <div class="profile-card">
                <div class="card-header-custom">
                    <h3 class="mb-0"><i class="fa-solid fa-user-pen me-2 p-2"></i> Profile Information</h3>
                </div>
                <div class="card-body p-4">
                    <form method="post" action="edit_prof.php">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="name" class="form-control"
                                    value="<?php echo remove_junk($user['name']); ?>"
                                    placeholder="Enter your full name" required>
                                <small class="text-muted">Your complete name as it should appear</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control"
                                    value="<?php echo remove_junk($user['username']); ?>"
                                    placeholder="Enter username" required>
                                <small class="text-muted">Your unique username for login</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">New Password</label>
                                <div class="input-group">
                                    <input type="password" name="password" class="form-control"
                                        placeholder="Enter new password" id="passwordInput">
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="fa-solid fa-eye" id="toggleIcon"></i>
                                    </button>
                                </div>
                                <div class="password-strength strength-weak" id="passwordStrength"></div>
                                <small class="text-muted">Leave blank to keep current password</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Department</label>
                                <select name="department_id" class="form-select" required>
                                    <option value="">-- Select Department --</option>
                                    <?php foreach ($departments as $dep): ?>
                                        <option value="<?php echo $dep['id']; ?>"
                                            <?php if ($user['department'] == $dep['id']) echo 'selected'; ?>>
                                            <?php echo remove_junk($dep['department']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Your assigned department</small>
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="row mt-4">
                            <div class="col-md-6 mb-2">
                                <button type="submit" name="update_profile" class="btn btn-success-custom w-100 btn-custom">
                                    <i class="fa-solid fa-floppy-disk me-2"></i> Save Changes
                                </button>
                            </div>
                            <div class="col-md-6 mb-2">
                                <a href="<?php echo $back_url; ?>" class="btn btn-secondary-custom w-100 btn-custom">
                                    <i class="fa-solid fa-arrow-left me-2"></i> Back to Dashboard
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Security Tips -->
            <div class="profile-card">
                <div class="card-header-custom">
                    <h4 class="mb-0"><i class="fa-solid fa-shield-halved me-2 p-2"></i>Security Tips</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning border-0">
                        <div class="d-flex">
                            <i class="fa-solid fa-lightbulb text-warning me-3 fa-2x"></i>
                            <div>
                                <h6 class="alert-heading mb-2">Keep Your Account Secure</h6>
                                <ul class="mb-0 ps-3">
                                    <li>Use a strong, unique password</li>
                                    <li>Never share your login credentials</li>
                                    <li>Update your password regularly</li>
                                    <li>Use a recent, clear profile photo</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Password strength indicator
        const passwordInput = document.getElementById('passwordInput');
        const passwordStrength = document.getElementById('passwordStrength');

        if (passwordInput && passwordStrength) {
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;

                if (password.length === 0) {
                    passwordStrength.className = 'password-strength';
                    passwordStrength.style.width = '0%';
                    return;
                }

                // Length check
                if (password.length >= 6) strength += 1;
                if (password.length >= 8) strength += 1;

                // Character variety checks
                if (/[a-z]/.test(password)) strength += 1;
                if (/[A-Z]/.test(password)) strength += 1;
                if (/[0-9]/.test(password)) strength += 1;
                if (/[^a-zA-Z0-9]/.test(password)) strength += 1;

                // Update strength indicator
                if (strength <= 2) {
                    passwordStrength.className = 'password-strength strength-weak';
                    passwordStrength.style.width = '25%';
                } else if (strength <= 4) {
                    passwordStrength.className = 'password-strength strength-medium';
                    passwordStrength.style.width = '50%';
                } else {
                    passwordStrength.className = 'password-strength strength-strong';
                    passwordStrength.style.width = '100%';
                }
            });
        }
    });
</script>

<script>
document.getElementById('togglePassword').addEventListener('click', function () {
    const passwordInput = document.getElementById('passwordInput');
    const toggleIcon = document.getElementById('toggleIcon');
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
});
</script>

<?php include_once('layouts/footer.php'); ?>