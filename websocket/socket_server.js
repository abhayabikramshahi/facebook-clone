const express = require('express');
const app = express();
const http = require('http').createServer(app);
const io = require('socket.io')(http, {
    cors: {
        origin: "*",
        methods: ["GET", "POST"]
    }
});

// Store online users
const onlineUsers = new Map();

io.on('connection', (socket) => {
    console.log('User connected:', socket.id);

    // Handle user authentication
    socket.on('authenticate', (userId) => {
        onlineUsers.set(userId, socket.id);
        socket.userId = userId;
        console.log(`User ${userId} authenticated`);
        
        // Broadcast user online status
        io.emit('user_status', {
            userId: userId,
            status: 'online'
        });
    });

    // Handle private messages
    socket.on('private_message', (data) => {
        const { receiverId, message } = data;
        const receiverSocketId = onlineUsers.get(receiverId);

        if (receiverSocketId) {
            // Send to receiver
            io.to(receiverSocketId).emit('new_message', {
                senderId: socket.userId,
                message: message,
                timestamp: new Date().toISOString()
            });
        }

        // Send confirmation to sender
        socket.emit('message_sent', {
            message: message,
            timestamp: new Date().toISOString()
        });
    });

    // Handle typing status
    socket.on('typing', (data) => {
        const { receiverId, isTyping } = data;
        const receiverSocketId = onlineUsers.get(receiverId);

        if (receiverSocketId) {
            io.to(receiverSocketId).emit('user_typing', {
                userId: socket.userId,
                isTyping: isTyping
            });
        }
    });

    // Handle read receipts
    socket.on('message_read', (data) => {
        const { senderId } = data;
        const senderSocketId = onlineUsers.get(senderId);

        if (senderSocketId) {
            io.to(senderSocketId).emit('message_read_confirmation', {
                readerId: socket.userId
            });
        }
    });

    // Handle disconnection
    socket.on('disconnect', () => {
        if (socket.userId) {
            onlineUsers.delete(socket.userId);
            
            // Broadcast user offline status
            io.emit('user_status', {
                userId: socket.userId,
                status: 'offline'
            });
        }
        console.log('User disconnected:', socket.id);
    });
});

const PORT = process.env.PORT || 3000;
http.listen(PORT, () => {
    console.log(`Socket.IO server running on port ${PORT}`);
}); 