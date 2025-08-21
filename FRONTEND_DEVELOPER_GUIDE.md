# 🚀 **Frontend Developer Guide - Chat Feature Implementation**

## 🎯 **What You Need to Implement (Your Responsibility)**

This guide explains **exactly what you need to build** for the chat feature. The backend is complete and documented - you just need to implement the frontend integration.

## 📱 **What You're Building**

### **1. Chat Screen/Component**

-   UI for displaying messages
-   Input field for typing messages
-   Support for different message types (text, image, location)
-   Real-time message updates

### **4. API Endpoints (Exact URLs)**

-   **Open Chat**: `POST /api/requests/{id}/chat/open`
-   **List Messages**: `GET /api/chat/threads/{threadId}/messages`
-   **Post Message**: `POST /api/chat/threads/{threadId}/messages`
-   **Close Thread**: `PATCH /api/chat/threads/{threadId}/close`

### **2. WebSocket Connection Management**

-   Laravel Echo integration
-   Channel subscription/unsubscription
-   Event handling for real-time updates

### **3. API Integration**

-   HTTP calls to REST endpoints
-   Authentication handling
-   Error handling and user feedback

## 🔄 **How REST API + WebSocket Work Together (The Flow)**

### **The Confusion Explained:**

You're right to be confused! This is a **hybrid approach** that's actually **very common** in modern apps. Here's how it works:

### **Real-World Example (Like WhatsApp/Telegram):**

#### **Step 1: User Opens Chat (REST API)**

```dart
// You call REST API to open/create chat thread
POST /api/requests/123/chat/open
Response: {
  "success": true,
  "message": "Chat thread opened successfully",
  "data": { "threadId": 456 }
}
```

#### **Step 2: Subscribe to Real-time Updates (WebSocket)**

```dart
// You subscribe to WebSocket channel for instant updates
// Channel name: private-chat.{threadId}
Echo.private('chat.456')
    .listen('message.created', handleNewMessage);
```

#### **Step 3: User Types Message (REST API)**

```dart
// You send message via REST API
POST /api/chat/threads/456/messages
Body: { "type": "text", "text": "Hello!" }
Response: {
  "success": true,
  "message": "Message posted successfully",
  "data": { "id": 789 }
}
```

#### **Step 4: Message Appears Instantly (WebSocket)**

```dart
// Message automatically appears via WebSocket event
// No need to refresh or poll - it's real-time!
```

### **Why Both? (The Magic)**

#### **REST API is for:**

-   **User Actions** - What the user does (send message, open chat)
-   **Data Persistence** - Saving messages to database
-   **Authentication** - Verifying user identity
-   **Error Handling** - Immediate feedback for user actions

#### **WebSocket is for:**

-   **Real-time Updates** - Instant message delivery
-   **Live Communication** - No need to refresh/poll
-   **Efficient** - One connection, multiple updates
-   **User Experience** - Messages appear as they're typed

## 🎯 **Complete Implementation Flow**

### **Phase 1: Initialize Chat**

```dart
// 1. Call REST API to open chat thread
final response = await http.post('/api/requests/$requestId/chat/open');
final data = jsonDecode(response.body);
final threadId = data['data']['threadId'];

// 2. Subscribe to WebSocket for real-time updates
Echo.private('chat.$threadId')
    .listen('message.created', handleNewMessage)
    .listen('thread.closed', handleThreadClosed);

// 3. Load existing messages via REST API
final messagesResponse = await http.get('/api/chat/threads/$threadId/messages');
final messagesData = jsonDecode(messagesResponse.body);
final messages = messagesData['data']['messages'];
final nextCursor = messagesData['data']['nextCursor'];
```

### **Phase 2: Real-time Messaging**

```dart
// User types message and hits send
// 1. Send via REST API (immediate feedback)
final postResponse = await http.post('/api/chat/threads/$threadId/messages', {
    'type': 'text',
    'text': userInput
});

if (postResponse.statusCode == 200) {
    final responseData = jsonDecode(postResponse.body);
    final messageId = responseData['data']['id'];
    // Message will be received via WebSocket automatically
}

// 2. Message automatically appears via WebSocket
// (No additional code needed - it's automatic!)
```

### **Phase 3: Cleanup**

```dart
// When chat is done
// 1. Close thread via REST API
final closeResponse = await http.patch('/api/chat/threads/$threadId/close');
if (closeResponse.statusCode == 200) {
    final responseData = jsonDecode(closeResponse.body);
    final status = responseData['data']['status'];
    final closedAt = responseData['data']['closed_at'];
}

// 2. Unsubscribe from WebSocket
Echo.leave('chat.$threadId');
```

## 🔌 **WebSocket Implementation Details**

### **Channel Naming Convention**

-   **Backend Channel**: `private-chat.{threadId}` (internal Laravel naming)
-   **Frontend Subscription**: `Echo.private('chat.{threadId}')` (note: no 'private-' prefix)
-   **Example**: For thread ID 123:
    -   Backend: `private-chat.123`
    -   Frontend: `Echo.private('chat.123')`

### **What You Need to Install:**

```yaml
# pubspec.yaml
dependencies:
    laravel_echo: ^1.0.0 # For WebSocket connection
    socket_io_client: ^2.0.0 # Alternative if Laravel Echo not available
```

### **Connection Setup:**

```dart
// Initialize Laravel Echo
final echo = Echo({
  'broadcaster': 'socket.io',
  'host': 'your-websocket-server.com',
  'auth': {
    'headers': {
      'Authorization': 'Bearer $jwtToken',
    },
  },
});
```

### **Channel Subscription:**

```dart
// Subscribe to specific chat thread
final channel = echo.private('chat.$threadId');

// Listen for events
channel.listen('message.created', (event) {
  // Add new message to UI
  setState(() {
    messages.add(ChatMessage.fromJson(event));
  });
});

channel.listen('thread.closed', (event) {
  // Update UI to show thread is closed
  setState(() {
    threadStatus = 'closed';
  });
});
```

## 📡 **REST API Implementation Details**

### **Authentication:**

```dart
// Always include JWT token in headers
final headers = {
  'Authorization': 'Bearer $jwtToken',
  'Content-Type': 'application/json',
};
```

### **Error Handling:**

```dart
try {
  final response = await http.post(url, headers: headers, body: body);

  if (response.statusCode == 200) {
    // Success
  } else if (response.statusCode == 401) {
    // Token expired - refresh or redirect to login
  } else if (response.statusCode == 403) {
    // User not authorized
  } else if (response.statusCode == 501) {
    // Chat feature disabled
  }
} catch (e) {
  // Network error or other exception
}
```

## 🎨 **UI Implementation Tips**

### **Message Display:**

```dart
// Use StreamBuilder for real-time updates
StreamBuilder<List<ChatMessage>>(
  stream: _messageStream,
  builder: (context, snapshot) {
    if (snapshot.hasData) {
      return ListView.builder(
        itemCount: snapshot.data!.length,
        itemBuilder: (context, index) {
          final message = snapshot.data![index];
          return MessageTile(message: message);
        },
      );
    }
    return CircularProgressIndicator();
  },
)
```

### **Message Types:**

```dart
Widget _buildMessageContent(ChatMessage message) {
  switch (message.type) {
    case 'text':
      return Text(message.text ?? '');
    case 'location':
      return LocationWidget(lat: message.lat!, lng: message.lng!);
    case 'image':
      return Image.network(message.mediaUrl!);
    default:
      return Text('Unknown message type');
  }
}
```

## 🔒 **Security Considerations**

### **Authentication:**

-   Always include JWT token in API calls
-   Handle token expiration gracefully
-   Store tokens securely (use flutter_secure_storage)

### **Authorization:**

-   Users can only access their own chat threads
-   Admin users can access threads they're assigned to
-   Backend handles all security - you just need to handle errors

### **Media Security:**

-   Media URLs are signed with short TTL
-   URLs expire automatically for security
-   No need to implement additional security on frontend

## 📚 **What You Get from Backend**

### **Complete API Reference:**

-   **Swagger UI**: `/api/documentation`
-   **Chat Endpoints**: Under "Chat" tag
-   **Real-time Events**: Under "Real-time Events" tag

### **Event Schemas:**

-   **message.created**: Complete message data
-   **thread.closed**: Thread closure information

### **Response Formats:**

-   All endpoints return consistent format: `{ "success": true, "message": "...", "data": {...} }`
-   Error responses with proper HTTP status codes

### **Exact Response Examples:**

#### **Open Chat Response:**

```json
{
    "success": true,
    "message": "Chat thread opened successfully",
    "data": { "threadId": 456 }
}
```

#### **List Messages Response:**

```json
{
  "success": true,
  "message": "Messages retrieved successfully",
  "data": {
    "messages": [...],
    "nextCursor": "123"
  }
}
```

#### **Post Message Response:**

```json
{
    "success": true,
    "message": "Message posted successfully",
    "data": { "id": 789 }
}
```

#### **Close Thread Response:**

```json
{
    "success": true,
    "message": "Chat thread closed successfully",
    "data": {
        "status": "closed",
        "closed_at": "2025-01-20T11:00:00Z"
    }
}
```

## 🚀 **Getting Started Checklist**

### **Before You Start:**

1. ✅ **Review Swagger UI** at `/api/documentation`
2. ✅ **Understand WebSocket channels** from README.md
3. ✅ **Check event schemas** under "Real-time Events" tag
4. ✅ **Test API endpoints** using Swagger UI

### **Implementation Order:**

1. **REST API integration** (open, post, list, close)
2. **WebSocket connection** (Echo setup)
3. **Channel subscription** (event handling)
4. **UI components** (message display, input)
5. **Real-time updates** (automatic message appearance)
6. **Error handling** (network, auth, validation)

## 🎉 **What You'll Achieve**

By following this guide, you'll build a **professional chat feature** that:

-   ✅ **Works offline** (REST API for reliability)
-   ✅ **Updates in real-time** (WebSocket for instant updates)
-   ✅ **Handles errors gracefully** (proper error handling)
-   ✅ **Provides great UX** (no need to refresh)
-   ✅ **Follows security best practices** (JWT + policy-based access)

## 🔗 **Support & Resources**

### **Backend Documentation:**

-   **Swagger UI**: `/api/documentation`
-   **README.md**: WebSocket overview
-   **routes/channels.php**: Detailed WebSocket implementation

### **Testing:**

-   Use Swagger UI to test all endpoints
-   Verify authentication and authorization
-   Test different message types
-   Validate real-time events

---

**Status**: 🟢 **READY FOR IMPLEMENTATION**  
**Backend**: 🟢 **COMPLETE & DOCUMENTED**  
**Frontend**: 🟢 **GUIDE PROVIDED**  
**Next Step**: 🟢 **START BUILDING!**

Your backend team has provided everything you need. Now it's your turn to build an amazing chat experience! 🚀
