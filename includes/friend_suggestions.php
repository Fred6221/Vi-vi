<?php
// Get friend suggestions
$suggestions = getFriendSuggestions($user['id']);

if (empty($suggestions)) {
    echo '<li class="list-group-item text-center">Aucune suggestion pour le moment</li>';
} else {
    foreach ($suggestions as $suggestion) {
?>
    <li class="list-group-item d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <img src="<?php echo $suggestion['profile_pic'] ? 'assets/images/profile/' . $suggestion['profile_pic'] : 'assets/images/default-profile.jpg'; ?>" 
                 class="rounded-circle me-2" width="40" height="40" alt="Profile Picture">
            <div>
                <a href="profile.php?id=<?php echo $suggestion['id']; ?>" class="text-decoration-none">
                    <?php echo htmlspecialchars($suggestion['name']); ?>
                </a>
            </div>
        </div>
        <button class="btn btn-sm btn-primary add-friend" data-user-id="<?php echo $suggestion['id']; ?>">
            <i class="fas fa-user-plus"></i>
        </button>
    </li>
<?php
    }
}
?>