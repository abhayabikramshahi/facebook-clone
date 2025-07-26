<?php
require_once 'config/database.php';
require_once 'includes/session.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$currentUserId = getCurrentUserId();
$currentUsername = getCurrentUsername();

// Get all conversations
$stmt = $pdo->prepare("
    SELECT DISTINCT 
        u.id,
        u.username,
        u.profile_picture,
        u.verification_status,
        (
            SELECT message 
            FROM messages 
            WHERE (sender_id = :currentUserId AND receiver_id = u.id) 
               OR (sender_id = u.id AND receiver_id = :currentUserId)
            ORDER BY created_at DESC 
            LIMIT 1
        ) as last_message,
        (
            SELECT created_at 
            FROM messages 
            WHERE (sender_id = :currentUserId AND receiver_id = u.id) 
               OR (sender_id = u.id AND receiver_id = :currentUserId)
            ORDER BY created_at DESC 
            LIMIT 1
        ) as last_message_time,
        (
            SELECT COUNT(*) 
            FROM messages 
            WHERE sender_id = u.id 
            AND receiver_id = :currentUserId 
            AND is_read = 0
        ) as unread_count
    FROM users u
    INNER JOIN messages m ON (m.sender_id = u.id AND m.receiver_id = :currentUserId)
        OR (m.sender_id = :currentUserId AND m.receiver_id = u.id)
    WHERE u.id != :currentUserId
    ORDER BY last_message_time DESC
");
$stmt->execute(['currentUserId' => $currentUserId]);
$conversations = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - PhotoShare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.socket.io/4.5.4/socket.io.min.js"></script>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white border-b border-gray-200 fixed w-full top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="index.php" class="text-2xl font-bold text-blue-600">PhotoShare</a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="notifications.php" class="text-gray-700 hover:text-blue-600">
                        <i class="fas fa-bell"></i>
                    </a>
                    <a href="profile.php" class="text-gray-700 hover:text-blue-600">
                        <i class="fas fa-user-circle"></i>
                        <span class="ml-2"><?php echo htmlspecialchars($currentUsername); ?></span>
                    </a>
                    <a href="logout.php" class="text-gray-700 hover:text-blue-600">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="ml-2">Logout</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="pt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="flex h-[calc(100vh-8rem)]">
                    <!-- Conversations List -->
                    <div class="w-1/3 border-r border-gray-200 overflow-y-auto">
                        <div class="p-4 border-b border-gray-200">
                            <div class="flex justify-between items-center mb-4">
                                <h2 class="text-xl font-semibold text-gray-900">Messages</h2>
                                <button onclick="openSearchModal()" class="text-blue-600 hover:text-blue-700">
                                    <i class="fas fa-search"></i>
                                    <span class="ml-2">Search Users</span>
                                </button>
                            </div>
                        </div>
                        <div class="divide-y divide-gray-200">
                            <?php foreach ($conversations as $conversation): ?>
                                <a href="?user=<?php echo $conversation['id']; ?>" 
                                   class="block p-4 hover:bg-gray-50 <?php echo isset($_GET['user']) && $_GET['user'] == $conversation['id'] ? 'bg-gray-50' : ''; ?>">
                                    <div class="flex items-center space-x-3">
                                        <img src="<?php echo $conversation['profile_picture'] ?? 'assets/images/profiles/default-profile.jpg'; ?>" 
                                             class="w-12 h-12 rounded-full object-cover" 
                                             alt="<?php echo htmlspecialchars($conversation['username']); ?>">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between">
                                                <p class="text-sm font-medium text-gray-900 truncate">
                                                    <?php echo htmlspecialchars($conversation['username']); ?>
                                                    <?php if ($conversation['verification_status'] === 'verified'): ?>
                                                        <i class="fas fa-check-circle text-blue-500 ml-1"></i>
                                                    <?php endif; ?>
                                                </p>
                                                <p class="text-xs text-gray-500">
                                                    <?php echo date('M j', strtotime($conversation['last_message_time'])); ?>
                                                </p>
                                            </div>
                                            <p class="text-sm text-gray-500 truncate">
                                                <?php echo htmlspecialchars($conversation['last_message']); ?>
                                            </p>
                                        </div>
                                        <?php if ($conversation['unread_count'] > 0): ?>
                                            <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-500 rounded-full">
                                                <?php echo $conversation['unread_count']; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Chat Area -->
                    <div class="flex-1 flex flex-col">
                        <?php if (isset($_GET['user'])): ?>
                            <?php
                            $otherUserId = $_GET['user'];
                            $stmt = $pdo->prepare("SELECT username, profile_picture, verification_status FROM users WHERE id = ?");
                            $stmt->execute([$otherUserId]);
                            $otherUser = $stmt->fetch();
                            
                            if ($otherUser):
                            ?>
                                <!-- Chat Header -->
                                <div class="p-4 border-b border-gray-200 flex items-center space-x-3">
                                    <img src="<?php echo $otherUser['profile_picture'] ?? 'assets/images/profiles/default-profile.jpg'; ?>" 
                                         class="w-10 h-10 rounded-full object-cover" 
                                         alt="<?php echo htmlspecialchars($otherUser['username']); ?>">
                                    <div>
                                        <h3 class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($otherUser['username']); ?>
                                            <?php if ($otherUser['verification_status'] === 'verified'): ?>
                                                <i class="fas fa-check-circle text-blue-500 ml-1"></i>
                                            <?php endif; ?>
                                        </h3>
                                    </div>
                                </div>

                                <!-- Messages -->
                                <div class="flex-1 overflow-y-auto p-4 space-y-4" id="messages">
                                    <?php
                                    $stmt = $pdo->prepare("
                                        SELECT m.*, u.username, u.profile_picture 
                                        FROM messages m 
                                        JOIN users u ON m.sender_id = u.id 
                                        WHERE (m.sender_id = :currentUserId AND m.receiver_id = :otherUserId)
                                           OR (m.sender_id = :otherUserId AND m.receiver_id = :currentUserId)
                                        ORDER BY m.created_at ASC
                                    ");
                                    $stmt->execute([
                                        'currentUserId' => $currentUserId,
                                        'otherUserId' => $otherUserId
                                    ]);
                                    $messages = $stmt->fetchAll();

                                    foreach ($messages as $message):
                                        $isCurrentUser = $message['sender_id'] == $currentUserId;
                                    ?>
                                        <div class="flex <?php echo $isCurrentUser ? 'justify-end' : 'justify-start'; ?>">
                                            <div class="flex items-end space-x-2">
                                                <?php if (!$isCurrentUser): ?>
                                                    <img src="<?php echo $message['profile_picture'] ?? 'assets/images/profiles/default-profile.jpg'; ?>" 
                                                         class="w-8 h-8 rounded-full object-cover" 
                                                         alt="<?php echo htmlspecialchars($message['username']); ?>">
                                                <?php endif; ?>
                                                <div class="max-w-xs lg:max-w-md">
                                                    <div class="rounded-lg px-4 py-2 <?php echo $isCurrentUser ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-900'; ?>">
                                                        <p class="text-sm"><?php echo htmlspecialchars($message['message']); ?></p>
                                                    </div>
                                                    <p class="text-xs text-gray-500 mt-1">
                                                        <?php echo date('g:i A', strtotime($message['created_at'])); ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Message Input -->
                                <div class="p-4 border-t border-gray-200">
                                    <form id="messageForm" class="flex space-x-4">
                                        <input type="hidden" name="receiver_id" value="<?php echo $otherUserId; ?>">
                                        <input type="text" 
                                               name="message" 
                                               class="flex-1 rounded-lg border-gray-200 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50" 
                                               placeholder="Type a message...">
                                        <button type="submit" 
                                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                            Send
                                        </button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <div class="flex-1 flex items-center justify-center">
                                    <p class="text-gray-500">User not found</p>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="flex-1 flex items-center justify-center">
                                <p class="text-gray-500">Select a conversation to start messaging</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize Socket.IO connection
        const socket = io('http://localhost:3000');
        const currentUserId = <?php echo $currentUserId; ?>;
        let typingTimeout;
        
        // Connect to Socket.IO server
        socket.on('connect', () => {
            console.log('Connected to Socket.IO server');
            // Authenticate user
            socket.emit('authenticate', currentUserId);
        });
        
        // Handle new messages
        socket.on('new_message', (data) => {
            addMessage(data);
            // Mark message as read
            socket.emit('message_read', { senderId: data.senderId });
        });
        
        // Handle message sent confirmation
        socket.on('message_sent', (data) => {
            addMessage({
                senderId: currentUserId,
                message: data.message,
                timestamp: data.timestamp
            });
        });
        
        // Handle user typing status
        socket.on('user_typing', (data) => {
            const typingIndicator = document.getElementById('typing-indicator');
            if (data.isTyping) {
                typingIndicator.textContent = 'User is typing...';
                typingIndicator.classList.remove('hidden');
            } else {
                typingIndicator.classList.add('hidden');
            }
        });
        
        // Handle user online/offline status
        socket.on('user_status', (data) => {
            const statusIndicator = document.querySelector(`[data-user-id="${data.userId}"] .status-indicator`);
            if (statusIndicator) {
                statusIndicator.className = `status-indicator w-2 h-2 rounded-full ${data.status === 'online' ? 'bg-green-500' : 'bg-gray-400'}`;
            }
        });
        
        // Message form submission
        document.getElementById('messageForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const form = e.target;
            const message = form.message.value;
            const receiverId = form.receiver_id.value;
            
            if (message.trim()) {
                // Send message through Socket.IO
                socket.emit('private_message', {
                    receiverId: receiverId,
                    message: message
                });
                
                // Clear input
                form.message.value = '';
            }
        });
        
        // Handle typing indicator
        const messageInput = document.querySelector('input[name="message"]');
        if (messageInput) {
            messageInput.addEventListener('input', () => {
                const receiverId = document.querySelector('input[name="receiver_id"]').value;
                
                // Clear existing timeout
                clearTimeout(typingTimeout);
                
                // Emit typing status
                socket.emit('typing', {
                    receiverId: receiverId,
                    isTyping: true
                });
                
                // Set timeout to stop typing indicator
                typingTimeout = setTimeout(() => {
                    socket.emit('typing', {
                        receiverId: receiverId,
                        isTyping: false
                    });
                }, 1000);
            });
        }
        
        // Add message to chat
        function addMessage(data) {
            const messagesDiv = document.getElementById('messages');
            if (!messagesDiv) return;
            
            const isCurrentUser = data.senderId == currentUserId;
            
            const messageHtml = `
                <div class="flex ${isCurrentUser ? 'justify-end' : 'justify-start'}">
                    <div class="flex items-end space-x-2">
                        ${!isCurrentUser ? `
                            <img src="assets/images/profiles/default-profile.jpg" 
                                 class="w-8 h-8 rounded-full object-cover" 
                                 alt="User">
                        ` : ''}
                        <div class="max-w-xs lg:max-w-md">
                            <div class="rounded-lg px-4 py-2 ${isCurrentUser ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-900'}">
                                <p class="text-sm">${data.message}</p>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">
                                ${new Date(data.timestamp).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })}
                            </p>
                        </div>
                    </div>
                </div>
            `;
            
            messagesDiv.insertAdjacentHTML('beforeend', messageHtml);
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        }
        
        // Scroll to bottom of messages
        const messagesDiv = document.getElementById('messages');
        if (messagesDiv) {
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        }

        // Add these functions to your existing script
        function openSearchModal() {
            const modal = document.getElementById('searchModal');
            modal.classList.add('active');
            document.getElementById('searchInput').focus();
        }

        function closeSearchModal() {
            const modal = document.getElementById('searchModal');
            modal.classList.remove('active');
            document.getElementById('searchInput').value = '';
            document.getElementById('searchResults').innerHTML = '';
        }

        // Handle search input
        let searchTimeout;
        document.getElementById('searchInput').addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            const query = e.target.value.trim();
            
            if (query.length < 2) {
                document.getElementById('searchResults').innerHTML = '';
                return;
            }
            
            searchTimeout = setTimeout(() => {
                searchUsers(query);
            }, 300);
        });

        // Search users function
        async function searchUsers(query) {
            try {
                console.log('Searching for:', query);
                
                const response = await fetch(`search_users.php?q=${encodeURIComponent(query)}`);
                console.log('Response status:', response.status);
                
                const data = await response.json();
                console.log('Search results:', data);
                
                const resultsDiv = document.getElementById('searchResults');
                resultsDiv.innerHTML = '';
                
                if (data.status === 'error') {
                    console.error('Search error:', data.error);
                    resultsDiv.innerHTML = `<p class="text-red-500 text-center py-4">${data.error}</p>`;
                    return;
                }
                
                if (!data.users || data.users.length === 0) {
                    console.log('No users found');
                    resultsDiv.innerHTML = '<p class="text-gray-500 text-center py-4">No users found</p>';
                    return;
                }
                
                data.users.forEach(user => {
                    console.log('Processing user:', user);
                    const lastMessageTime = user.last_message_time ? 
                        new Date(user.last_message_time).toLocaleDateString() : '';
                    
                    const userHtml = `
                        <div class="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <img src="${user.profile_picture}" 
                                     class="w-12 h-12 rounded-full object-cover" 
                                     alt="${user.username}"
                                     onerror="this.src='assets/images/profiles/default-profile.jpg'">
                                <div>
                                    <div class="flex items-center space-x-2">
                                        <p class="font-medium text-gray-900">
                                            ${user.username}
                                            ${user.verification_status === 'verified' ? 
                                                '<i class="fas fa-check-circle text-blue-500 ml-1"></i>' : ''}
                                        </p>
                                        <div class="flex items-center space-x-2">
                                            <button onclick="startConversation(${user.id})" 
                                                    class="text-gray-500 hover:text-blue-600 transition-colors"
                                                    title="Send Message">
                                                <i class="fas fa-envelope"></i>
                                            </button>
                                            <button onclick="toggleFollow(${user.id})" 
                                                    class="text-gray-500 hover:text-blue-600 transition-colors"
                                                    title="${user.is_following ? 'Unfollow' : 'Follow'}"
                                                    id="followBtn_${user.id}">
                                                <i class="fas ${user.is_following ? 'fa-user-check' : 'fa-user-plus'}"></i>
                                            </button>
                                        </div>
                                    </div>
                                    ${user.bio ? `
                                        <p class="text-sm text-gray-500 truncate max-w-xs">
                                            ${user.bio}
                                        </p>
                                    ` : ''}
                                    ${user.has_conversation ? `
                                        <p class="text-xs text-gray-400">
                                            ${user.message_count} messages • Last message: ${lastMessageTime}
                                        </p>
                                    ` : ''}
                                </div>
                            </div>
                            <button onclick="startConversation(${user.id})" 
                                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                ${user.has_conversation ? 'Continue Chat' : 'Message'}
                            </button>
                        </div>
                    `;
                    resultsDiv.insertAdjacentHTML('beforeend', userHtml);
                });
            } catch (error) {
                console.error('Error searching users:', error);
                const resultsDiv = document.getElementById('searchResults');
                resultsDiv.innerHTML = '<p class="text-red-500 text-center py-4">Error searching users: ' + error.message + '</p>';
            }
        }

        // Start conversation function
        function startConversation(userId) {
            window.location.href = `messages.php?user=${userId}`;
            closeSearchModal();
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('searchModal');
            if (event.target === modal) {
                closeSearchModal();
            }
        }

        // Add follow/unfollow functionality
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
                        const icon = followBtn.querySelector('i');
                        if (data.is_following) {
                            icon.className = 'fas fa-user-check';
                            followBtn.title = 'Unfollow';
                        } else {
                            icon.className = 'fas fa-user-plus';
                            followBtn.title = 'Follow';
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

    <!-- Add typing indicator in the chat area -->
    <div id="typing-indicator" class="hidden text-sm text-gray-500 italic px-4 py-2"></div>

    <!-- Search Modal -->
    <div id="searchModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl">
                <div class="flex justify-between items-center p-4 border-b">
                    <h3 class="text-xl font-semibold text-gray-900">Search Users</h3>
                    <button onclick="closeSearchModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="p-4">
                    <input type="text" 
                           id="searchInput" 
                           class="w-full rounded-lg border-gray-200 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50" 
                           placeholder="Search by username...">
                </div>
                <div id="searchResults" class="p-4 space-y-2 max-h-96 overflow-y-auto">
                    <!-- Search results will be populated here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Add this CSS to your existing styles -->
    <style>
    #searchModal {
        display: none;
    }
    #searchModal.active {
        display: block;
    }
    </style>
</body>
</html> 