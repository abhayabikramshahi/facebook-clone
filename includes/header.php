<!-- Add this before the closing body tag -->
<!-- Messaging Panel -->
<div id="messagePanel" class="fixed right-0 top-16 bottom-0 w-96 bg-white border-l border-gray-200 transform translate-x-full transition-transform duration-300 ease-in-out z-40">
    <div class="h-full flex flex-col">
        <!-- Panel Header -->
        <div class="p-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-900">Messages</h3>
            <button onclick="toggleMessagePanel()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <!-- Conversations List -->
        <div id="conversationsList" class="flex-1 overflow-y-auto">
            <!-- Conversations will be loaded here -->
        </div>
        
        <!-- Active Chat -->
        <div id="activeChat" class="hidden flex-1 flex flex-col">
            <!-- Chat Header -->
            <div class="p-4 border-b border-gray-200 flex items-center space-x-3">
                <button onclick="showConversations()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <img id="chatUserImage" src="" class="w-10 h-10 rounded-full object-cover" alt="">
                <div>
                    <h4 id="chatUserName" class="font-medium text-gray-900"></h4>
                    <p class="text-sm text-gray-500">Active now</p>
                </div>
            </div>
            
            <!-- Messages Container -->
            <div id="messagesContainer" class="flex-1 overflow-y-auto p-4 space-y-4">
                <!-- Messages will be loaded here -->
            </div>
            
            <!-- Message Input -->
            <div class="p-4 border-t border-gray-200">
                <form id="messageForm" class="flex space-x-2">
                    <input type="text" id="messageInput" 
                           class="flex-1 rounded-lg border border-gray-300 px-4 py-2 focus:outline-none focus:border-blue-500"
                           placeholder="Type a message...">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const currentUserId = <?php echo getCurrentUserId(); ?>;
let currentChatUserId = null;
let messageCheckInterval = null;

// Toggle message panel
function toggleMessagePanel() {
    const panel = document.getElementById('messagePanel');
    panel.classList.toggle('translate-x-full');
    if (!panel.classList.contains('translate-x-full')) {
        loadConversations();
        startMessageCheck();
    } else {
        stopMessageCheck();
    }
}

// Load conversations
async function loadConversations() {
    const list = document.getElementById('conversationsList');
    list.innerHTML = '<p class="text-center text-gray-500 p-4">Loading conversations...</p>';
    
    try {
        const response = await fetch('get_conversations.php');
        const data = await response.json();
        
        if (data.status === 'success') {
            if (data.conversations.length === 0) {
                list.innerHTML = '<p class="text-center text-gray-500 p-4">No conversations yet</p>';
                return;
            }
            
            list.innerHTML = data.conversations.map(conv => `
                <div class="p-4 border-b border-gray-200 hover:bg-gray-50 cursor-pointer" 
                     onclick="openChat(${conv.user_id})">
                    <div class="flex items-center space-x-3">
                        <img src="${conv.profile_picture || 'assets/images/profiles/default-profile.jpg'}" 
                             class="w-12 h-12 rounded-full object-cover" 
                             alt="${conv.username}">
                        <div class="flex-1">
                            <div class="flex justify-between items-start">
                                <h4 class="font-medium text-gray-900">
                                    ${conv.username}
                                    ${conv.verification_status === 'verified' ? 
                                        '<i class="fas fa-check-circle text-blue-500 ml-1"></i>' : ''}
                                </h4>
                                <span class="text-sm text-gray-500">${conv.last_message_time}</span>
                            </div>
                            <p class="text-sm text-gray-500 truncate">${conv.last_message}</p>
                        </div>
                    </div>
                </div>
            `).join('');
        } else {
            list.innerHTML = '<p class="text-center text-red-500 p-4">Error loading conversations</p>';
        }
    } catch (error) {
        console.error('Error:', error);
        list.innerHTML = '<p class="text-center text-red-500 p-4">Error loading conversations</p>';
    }
}

// Open chat with user
async function openChat(userId) {
    currentChatUserId = userId;
    const conversationsList = document.getElementById('conversationsList');
    const activeChat = document.getElementById('activeChat');
    
    conversationsList.classList.add('hidden');
    activeChat.classList.remove('hidden');
    
    try {
        const response = await fetch(`get_chat.php?user_id=${userId}`);
        const data = await response.json();
        
        if (data.status === 'success') {
            document.getElementById('chatUserImage').src = data.user.profile_picture || 'assets/images/profiles/default-profile.jpg';
            document.getElementById('chatUserName').textContent = data.user.username;
            
            const messagesContainer = document.getElementById('messagesContainer');
            messagesContainer.innerHTML = data.messages.map(msg => `
                <div class="flex ${msg.sender_id == currentUserId ? 'justify-end' : 'justify-start'}">
                    <div class="max-w-[70%] ${msg.sender_id == currentUserId ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-900'} rounded-lg px-4 py-2">
                        <p>${msg.message}</p>
                        <p class="text-xs ${msg.sender_id == currentUserId ? 'text-blue-200' : 'text-gray-500'} mt-1">
                            ${msg.created_at}
                        </p>
                    </div>
                </div>
            `).join('');
            
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// Show conversations list
function showConversations() {
    document.getElementById('conversationsList').classList.remove('hidden');
    document.getElementById('activeChat').classList.add('hidden');
    currentChatUserId = null;
}

// Send message
document.getElementById('messageForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    if (!currentChatUserId) return;
    
    const input = document.getElementById('messageInput');
    const message = input.value.trim();
    
    if (!message) return;
    
    try {
        const response = await fetch('send_message.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                receiver_id: currentChatUserId,
                message: message
            })
        });
        
        const data = await response.json();
        
        if (data.status === 'success') {
            input.value = '';
            appendMessage(data.message);
        }
    } catch (error) {
        console.error('Error:', error);
    }
});

// Append new message to chat
function appendMessage(message) {
    const messagesContainer = document.getElementById('messagesContainer');
    const messageHtml = `
        <div class="flex ${message.sender_id == currentUserId ? 'justify-end' : 'justify-start'}">
            <div class="max-w-[70%] ${message.sender_id == currentUserId ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-900'} rounded-lg px-4 py-2">
                <p>${message.message}</p>
                <p class="text-xs ${message.sender_id == currentUserId ? 'text-blue-200' : 'text-gray-500'} mt-1">
                    ${message.created_at}
                </p>
            </div>
        </div>
    `;
    messagesContainer.insertAdjacentHTML('beforeend', messageHtml);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

// Check for new messages
function startMessageCheck() {
    messageCheckInterval = setInterval(async () => {
        if (currentChatUserId) {
            try {
                const response = await fetch(`check_messages.php?user_id=${currentChatUserId}`);
                const data = await response.json();
                
                if (data.status === 'success' && data.messages.length > 0) {
                    data.messages.forEach(message => {
                        appendMessage(message);
                    });
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }
    }, 3000); // Check every 3 seconds
}

function stopMessageCheck() {
    if (messageCheckInterval) {
        clearInterval(messageCheckInterval);
    }
}

// Start conversation
function startConversation(userId) {
    toggleMessagePanel();
    setTimeout(() => openChat(userId), 100);
}
</script> 