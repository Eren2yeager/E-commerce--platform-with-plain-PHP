<?php
require_once('../config/database.php');
require_once('../includes/auth.php');
require_once('../includes/profile_functions.php');
require_once('../includes/toast.php');

// Require login to access profile
requireLogin();

$page_title = 'My Profile - ShopHub';
$user_id = getCurrentUserId();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Update Profile
    if (isset($_POST['update_profile'])) {
        $result = updateUserProfile($user_id, $_POST);
        if ($result['success']) {
            setToast($result['message'], 'success');
        } else {
            setToast($result['message'], 'error');
        }
        header("Location: profile.php");
        exit;
    }
    
    // Upload Profile Image
    if (isset($_POST['upload_image']) && isset($_FILES['profile_image'])) {
        $result = uploadProfileImage($user_id, $_FILES['profile_image']);
        if ($result['success']) {
            setToast($result['message'], 'success');
        } else {
            setToast($result['message'], 'error');
        }
        header("Location: profile.php");
        exit;
    }
    
    // Remove Profile Image
    if (isset($_POST['remove_image'])) {
        $result = removeProfileImage($user_id);
        if ($result['success']) {
            setToast($result['message'], 'success');
        } else {
            setToast($result['message'], 'error');
        }
        header("Location: profile.php");
        exit;
    }
    
    // Change Password
    if (isset($_POST['change_password'])) {
        $result = changePassword(
            $user_id,
            $_POST['current_password'],
            $_POST['new_password'],
            $_POST['confirm_password']
        );
        if ($result['success']) {
            setToast($result['message'], 'success');
        } else {
            setToast($result['message'], 'error');
        }
        header("Location: profile.php");
        exit;
    }
}

// Get user data
$user = getUserProfile($user_id);

require_once('../includes/header.php');
?>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h1 style="margin: 0;">My Profile</h1>
        <a href="logout.php" class="btn btn-logout">
            🚪 Logout
        </a>
    </div>

    <div class="profile-container">
        
        <!-- Left Sidebar: Profile Picture -->
        <div class="profile-sidebar">
            <div class="profile-picture-card">
                <div class="profile-picture-wrapper">
                    <?php if (!empty($user['profile_image'])): ?>
                        <img src="<?= htmlspecialchars($user['profile_image']) ?>" 
                             alt="Profile Picture" 
                             class="profile-picture">
                    <?php else: ?>
                        <div class="profile-picture-placeholder">
                            <span><?= strtoupper(substr($user['username'], 0, 2)) ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <h3><?= htmlspecialchars($user['username']) ?></h3>
                <p class="user-email"><?= htmlspecialchars($user['email']) ?></p>
                
                <!-- Upload Image Form -->
                <form method="POST" enctype="multipart/form-data" class="upload-form">
                    <input type="file" 
                           name="profile_image" 
                           id="profile_image" 
                           accept="image/*" 
                           style="display: none;"
                           onchange="this.form.submit()">
                    <label for="profile_image" class="btn btn-primary btn-sm">
                        📷 Change Photo
                    </label>
                    <input type="hidden" name="upload_image" value="1">
                </form>

                <?php if (!empty($user['profile_image'])): ?>
                    <form method="POST" style="margin-top: 0.5rem;">
                        <button type="submit" name="remove_image" class="btn btn-danger btn-sm" 
                                onclick="return confirm('Remove profile picture?')">
                            🗑️ Remove Photo
                        </button>
                    </form>
                <?php endif; ?>

                <div class="profile-stats">
                    <div class="stat">
                        <span class="stat-label">Member Since</span>
                        <span class="stat-value">
                            <?= date('M Y', strtotime($user['created_at'])) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Content: Profile Forms -->
        <div class="profile-content">
            
            <!-- Personal Information -->
            <div class="profile-section">
                <h2>Personal Information</h2>
                <form method="POST" class="profile-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" 
                                   id="first_name" 
                                   name="first_name" 
                                   value="<?= htmlspecialchars($user['first_name'] ?? '') ?>"
                                   placeholder="Enter first name">
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" 
                                   id="last_name" 
                                   name="last_name" 
                                   value="<?= htmlspecialchars($user['last_name'] ?? '') ?>"
                                   placeholder="Enter last name">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   value="<?= htmlspecialchars($user['email']) ?>"
                                   required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" 
                                   id="phone" 
                                   name="phone" 
                                   value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                                   placeholder="+1 (555) 123-4567">
                        </div>
                    </div>

                    <div class="form-buttons">
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            💾 Save Changes
                        </button>
                    </div>
                </form>
            </div>

            <!-- Address Information -->
            <div class="profile-section">
                <h2>Address Information</h2>
                <form method="POST" class="profile-form">
                    <div class="form-group">
                        <label for="address">Street Address</label>
                        <textarea id="address" 
                                  name="address" 
                                  rows="2" 
                                  placeholder="123 Main Street, Apt 4B"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="city">City</label>
                            <input type="text" 
                                   id="city" 
                                   name="city" 
                                   value="<?= htmlspecialchars($user['city'] ?? '') ?>"
                                   placeholder="New York">
                        </div>
                        <div class="form-group">
                            <label for="state">State / Province</label>
                            <input type="text" 
                                   id="state" 
                                   name="state" 
                                   value="<?= htmlspecialchars($user['state'] ?? '') ?>"
                                   placeholder="NY">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="zip_code">ZIP / Postal Code</label>
                            <input type="text" 
                                   id="zip_code" 
                                   name="zip_code" 
                                   value="<?= htmlspecialchars($user['zip_code'] ?? '') ?>"
                                   placeholder="10001">
                        </div>
                        <div class="form-group">
                            <label for="country">Country</label>
                            <input type="text" 
                                   id="country" 
                                   name="country" 
                                   value="<?= htmlspecialchars($user['country'] ?? '') ?>"
                                   placeholder="United States">
                        </div>
                    </div>

                    <!-- Copy hidden fields for other data -->
                    <input type="hidden" name="first_name" value="<?= htmlspecialchars($user['first_name'] ?? '') ?>">
                    <input type="hidden" name="last_name" value="<?= htmlspecialchars($user['last_name'] ?? '') ?>">
                    <input type="hidden" name="email" value="<?= htmlspecialchars($user['email']) ?>">
                    <input type="hidden" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">

                    <div class="form-buttons">
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            💾 Save Address
                        </button>
                    </div>
                </form>
            </div>

            <!-- Change Password -->
            <div class="profile-section">
                <h2>Change Password</h2>
                <form method="POST" class="profile-form">
                    <div class="form-group">
                        <label for="current_password">Current Password *</label>
                        <input type="password" 
                               id="current_password" 
                               name="current_password" 
                               required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="new_password">New Password *</label>
                            <input type="password" 
                                   id="new_password" 
                                   name="new_password" 
                                   required
                                   minlength="6">
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password *</label>
                            <input type="password" 
                                   id="confirm_password" 
                                   name="confirm_password" 
                                   required
                                   minlength="6">
                        </div>
                    </div>

                    <div class="form-buttons">
                        <button type="submit" name="change_password" class="btn btn-danger">
                            🔒 Change Password
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

<style>
/* Logout Button */
.btn-logout {
    padding: 0.75rem 1.5rem;
    background: #dc3545;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    display: inline-block;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-logout:hover {
    background: #c82333;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
}

/* Profile Page Styles */
.profile-container {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 2rem;
    align-items: start;
}

/* Profile Sidebar */
.profile-sidebar {
    position: sticky;
    top: 90px;
}

.profile-picture-card {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.profile-picture-wrapper {
    width: 150px;
    height: 150px;
    margin: 0 auto 1.5rem;
    border-radius: 50%;
    overflow: hidden;
    /* border: 4px solid #667eea; */
}

.profile-picture {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-picture-placeholder {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    font-weight: bold;
    color: white;
}

.profile-picture-card h3 {
    margin: 0 0 0.5rem 0;
    color: #333;
}

.user-email {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 1.5rem;
}

.upload-form {
    margin-bottom: 0.5rem;
}

.btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
}

.btn-danger {
    background: #dc3545;
    color: white;
}

.btn-danger:hover {
    background: #c82333;
}

.profile-stats {
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid #eee;
}

.stat {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.stat-label {
    font-size: 0.8rem;
    color: #999;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-value {
    font-size: 1rem;
    font-weight: 600;
    color: #333;
}

/* Profile Content */
.profile-content {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.profile-section {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.profile-section h2 {
    margin: 0 0 1.5rem 0;
    color: #333;
    font-size: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #f0f0f0;
}

.profile-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-group label {
    font-weight: 600;
    color: #333;
    font-size: 0.9rem;
}

.form-group input,
.form-group textarea {
    padding: 0.75rem;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-group textarea {
    resize: vertical;
    font-family: inherit;
}

.form-buttons {
    display: flex;
    gap: 1rem;
    padding-top: 1rem;
}

/* Responsive */
@media (max-width: 968px) {
    .profile-container {
        grid-template-columns: 1fr;
    }

    .profile-sidebar {
        position: static;
    }

    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require_once('../includes/footer.php'); ?>