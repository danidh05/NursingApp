# Image Upload Guide for Chat Feature

## Overview

The chat system supports sending images in conversations. Images are uploaded directly to Firebase Storage using signed URLs for security. This guide explains the complete flow for Flutter developers.

## Image Upload Flow

### Step 1: Get Upload URL

Before uploading an image, you need to request a signed upload URL from the backend.

**Endpoint:** `POST /api/chat/threads/{threadId}/upload-url`

**Request Body:**

```json
{
    "filename": "image.jpg",
    "contentType": "image/jpeg"
}
```

**Response:**

```json
{
    "success": true,
    "message": "Upload URL generated successfully",
    "data": {
        "url": "https://storage.googleapis.com/bucket/chats/123/unique_filename.jpg?...",
        "mediaPath": "chats/123/unique_filename.jpg",
        "headers": {
            "Content-Type": "image/jpeg"
        }
    }
}
```

### Step 2: Upload Image to Signed URL

Use the signed URL from Step 1 to upload your image directly to Firebase Storage. This is done via HTTP PUT, NOT multipart form data.

**Important:**

-   Method: `PUT` (not POST!)
-   Headers: Use the `headers` from the response (especially `Content-Type`)
-   Body: Send the raw image file bytes (not FormData)

**Flutter Example:**

```dart
// Get the image file
File imageFile = ...; // your selected image

// Read the file bytes
List<int> imageBytes = await imageFile.readAsBytes();

// Upload to the signed URL
final response = await http.put(
  Uri.parse(signedUrl), // from Step 1 response
  headers: headers, // from Step 1 response.data.headers
  body: imageBytes,
);

if (response.statusCode == 200) {
  // Upload successful!
}
```

### Step 3: Post Message with Image

After the upload is successful, post the chat message using the `mediaPath` from Step 1.

**Endpoint:** `POST /api/chat/threads/{threadId}/messages`

**Request Body:**

```json
{
    "type": "image",
    "mediaPath": "chats/123/unique_filename.jpg"
}
```

**Response:**

```json
{
    "success": true,
    "message": "Message posted successfully",
    "data": {
        "id": 789
    }
}
```

## Complete Flutter Implementation

```dart
class ChatImageService {
  static Future<void> sendImage(
    String threadId,
    File imageFile,
    String authToken,
  ) async {
    try {
      // Step 1: Get upload URL
      final uploadUrlResponse = await http.post(
        Uri.parse('$baseUrl/api/chat/threads/$threadId/upload-url'),
        headers: {
          'Authorization': 'Bearer $authToken',
          'Content-Type': 'application/json',
        },
        body: jsonEncode({
          'filename': imageFile.path.split('/').last,
          'contentType': 'image/jpeg', // or image/png, image/webp
        }),
      );

      if (uploadUrlResponse.statusCode != 200) {
        throw Exception('Failed to get upload URL');
      }

      final uploadData = jsonDecode(uploadUrlResponse.body)['data'];
      final signedUrl = uploadData['url'];
      final mediaPath = uploadData['mediaPath'];
      final headers = Map<String, String>.from(uploadData['headers']);

      // Step 2: Upload image to signed URL
      final imageBytes = await imageFile.readAsBytes();

      final uploadResponse = await http.put(
        Uri.parse(signedUrl),
        headers: headers,
        body: imageBytes,
      );

      if (uploadResponse.statusCode != 200) {
        throw Exception('Failed to upload image');
      }

      // Step 3: Post message with mediaPath
      final messageResponse = await http.post(
        Uri.parse('$baseUrl/api/chat/threads/$threadId/messages'),
        headers: {
          'Authorization': 'Bearer $authToken',
          'Content-Type': 'application/json',
        },
        body: jsonEncode({
          'type': 'image',
          'mediaPath': mediaPath,
        }),
      );

      if (messageResponse.statusCode != 200) {
        throw Exception('Failed to post image message');
      }

      print('Image sent successfully!');

    } catch (e) {
      print('Error sending image: $e');
      rethrow;
    }
  }
}
```

## Supported Image Types

-   `image/jpeg`
-   `image/png`
-   `image/webp`

## Important Notes

1. **NOT FormData**: Images are NOT uploaded via multipart form data. You upload the raw bytes directly to the signed URL.

2. **Three-Step Process**: Getting an upload URL, uploading the image, and posting the message are three separate API calls.

3. **Use `mediaPath` from Step 1**: When posting the message in Step 3, use the `mediaPath` returned in Step 1, not the uploaded filename.

4. **Thread Isolation**: Each image must be uploaded to a path that starts with `chats/{threadId}/`. The backend generates this automatically.

5. **Security**: Signed URLs have a TTL (Time To Live) of 900 seconds (15 minutes) by default. Upload your image promptly after receiving the signed URL.

## Troubleshooting

### "Failed to generate upload URL"

-   Check that the thread exists and you have access to it
-   Verify the content type is one of the supported types

### "Invalid media path for this thread"

-   The `mediaPath` must start with `chats/{threadId}/`
-   Use the exact `mediaPath` returned from Step 1

### Upload fails with 403 Forbidden

-   The signed URL may have expired (TTL exceeded)
-   Try getting a new upload URL

### "Feature disabled"

-   Chat feature must be enabled: Set `CHAT_ENABLED=true` in backend `.env`

## Comparison with Text/Location Messages

Text and location messages work differently - they only require a single API call:

**Text Message:**

```json
POST /api/chat/threads/{threadId}/messages
{
  "type": "text",
  "text": "Hello!"
}
```

**Location Message:**

```json
POST /api/chat/threads/{threadId}/messages
{
  "type": "location",
  "lat": 34.0522,
  "lng": -118.2437
}
```

**Image Message:** Requires 3 API calls (get upload URL → upload → post message)
