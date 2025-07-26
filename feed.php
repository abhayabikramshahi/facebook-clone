<!-- Update the post header section in the feed loop -->
<div class="flex items-center justify-between">
    <div class="flex items-center space-x-3">
        <a href="profile.php?username=<?php echo htmlspecialchars($post['username']); ?>" class="flex items-center space-x-3">
            <img src="<?php echo $post['profile_picture'] ? 'uploads/profiles/' . $post['profile_picture'] : 'assets/images/profiles/default-profile.jpg'; ?>" 
                 class="w-10 h-10 rounded-full object-cover" 
                 alt="<?php echo htmlspecialchars($post['username']); ?>">
            <div>
                <p class="font-medium text-gray-900">
                    <?php echo htmlspecialchars($post['username']); ?>
                    <?php if ($post['verification_status'] === 'verified'): ?>
                        <i class="fas fa-check-circle text-blue-500"></i>
                    <?php endif; ?>
                </p>
                <p class="text-sm text-gray-500">
                    <?php echo timeAgo($post['created_at']); ?>
                </p>
            </div>
        </a>
    </div>
    <?php if ($post['user_id'] != $currentUserId): ?>
        <div class="flex items-center space-x-2">
            <button onclick="startConversation(<?php echo $post['user_id']; ?>)" 
                    class="text-gray-600 hover:text-blue-600 transition-colors">
                <i class="fas fa-envelope"></i>
            </button>
            <button onclick="toggleFollow(<?php echo $post['user_id']; ?>)" 
                    class="px-3 py-1 text-sm <?php echo $post['is_following'] ? 'bg-gray-200 text-gray-700' : 'bg-blue-600 text-white'; ?> rounded-lg hover:bg-opacity-90"
                    id="followBtn_<?php echo $post['user_id']; ?>">
                <?php echo $post['is_following'] ? 'Following' : 'Follow'; ?>
            </button>
        </div>
    <?php endif; ?>
</div>

<!-- Add this to the PHP section at the top of the file -->
<?php
// Update the posts query to include follow status
$stmt = $pdo->prepare("
    SELECT p.*, u.username, u.profile_picture, u.verification_status,
           (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
           (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count,
           EXISTS(SELECT 1 FROM likes WHERE post_id = p.id AND user_id = ?) as is_liked,
           EXISTS(SELECT 1 FROM follows WHERE follower_id = ? AND following_id = p.user_id) as is_following
    FROM posts p
    JOIN users u ON p.user_id = u.id
    ORDER BY p.created_at DESC
");
$stmt->execute([$currentUserId, $currentUserId]);
$posts = $stmt->fetchAll();
?>

<!-- Add this to your script section -->
<script>
// Add the toggleFollow function if it doesn't exist
async function toggleFollow(userId) {
    try {
        const response = await fetch('follow.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ user_id: userId })
        });
        
        const data = await response.json();
        
        if (data.status === 'success') {
            const followBtn = document.getElementById(`followBtn_${userId}`);
            if (followBtn) {
                if (data.is_following) {
                    followBtn.className = 'px-3 py-1 text-sm bg-gray-200 text-gray-700 rounded-lg hover:bg-opacity-90';
                    followBtn.textContent = 'Following';
                } else {
                    followBtn.className = 'px-3 py-1 text-sm bg-blue-600 text-white rounded-lg hover:bg-opacity-90';
                    followBtn.textContent = 'Follow';
                }
            }
        } else {
            console.error('Follow error:', data.error);
        }
    } catch (error) {
        console.error('Error toggling follow:', error);
    }
}
</script> 