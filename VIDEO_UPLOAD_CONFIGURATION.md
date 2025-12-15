# Video Upload Configuration

This document explains the setup required to support large video file uploads (up to 5GB) in the Knowledge Center module.

## Features Implemented

1. **Database**: Added `video_path` column to store video files on the server
2. **Admin Panel**: Supports video file uploads up to 5GB
3. **API Endpoints**: Mobile app can fetch knowledge articles and stream videos
4. **Video Streaming**: Supports HTTP range requests for efficient video playback (seeking, progressive download)

## Server Configuration

To support 5GB video uploads, you need to update your PHP and web server configuration:

### PHP Configuration

Update your `php.ini` file (or create a `.user.ini` file in your project root) with the following values:

```ini
upload_max_filesize = 5120M
post_max_size = 5120M
max_execution_time = 300
max_input_time = 300
memory_limit = 512M
```

**Finding your php.ini file:**
- Run `php --ini` to find the location of your php.ini file
- For local development, you may need to modify the php.ini used by PHP-FPM or your development server

### Nginx Configuration (if using Nginx)

Add to your server block:

```nginx
client_max_body_size 5120M;
client_body_timeout 300s;
```

### Apache Configuration (if using Apache)

Add to your `.htaccess` or virtual host configuration:

```apache
php_value upload_max_filesize 5120M
php_value post_max_size 5120M
php_value max_execution_time 300
php_value max_input_time 300
LimitRequestBody 5368709120
```

## API Endpoints

### Get Knowledge Articles (Mobile App)

```http
GET /api/knowledge
Authorization: Bearer {token}
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "title": "Article Title",
      "content": "Article content...",
      "html_content": "<p>Article HTML content...</p>",
      "video_url": "https://youtube.com/watch?v=...",
      "video_stream_url": "http://yourdomain.com/api/knowledge/1/video",
      "is_published": true,
      "published_at": "2025-12-15T10:00:00Z",
      "created_at": "2025-12-15T08:00:00Z",
      "updated_at": "2025-12-15T08:00:00Z"
    }
  ],
  "status": 200,
  "message": "Knowledge retrieved successfully."
}
```

### Get Single Knowledge Article

```http
GET /api/knowledge/{id}
Authorization: Bearer {token}
```

### Stream Video

```http
GET /api/knowledge/{id}/video
Authorization: Bearer {token}
Range: bytes=0-1023 (optional, for seeking)
```

**Features:**
- Supports HTTP Range requests for video seeking
- Progressive download support
- Efficient chunked streaming (1MB chunks)
- Proper MIME type detection
- Cache headers for optimal performance

## Admin Panel Usage

When creating or editing a knowledge article:

1. You can either:
   - Add a **video URL** (e.g., YouTube link) in the `video_url` field, OR
   - Upload a **video file** (up to 5GB) using the file upload field

2. Supported video formats:
   - MP4
   - MOV
   - AVI
   - WMV
   - FLV
   - MKV
   - WebM

3. When a new video is uploaded, the old video file is automatically deleted
4. When deleting a knowledge article, the associated video file is also deleted

## Storage Location

Videos are stored in:
```
storage/app/public/knowledge/videos/
```

Make sure to run `php artisan storage:link` to create the symbolic link for public access.

## Mobile App Integration

### Playing Videos

When displaying knowledge articles in your mobile app:

1. If `video_url` exists: Display as embedded video (YouTube, Vimeo, etc.)
2. If `video_stream_url` exists: Use a native video player with the streaming URL

### Example (React Native)

```jsx
import Video from 'react-native-video';

// For uploaded videos
<Video
  source={{
    uri: knowledge.video_stream_url,
    headers: {
      'Authorization': `Bearer ${token}`
    }
  }}
  controls={true}
  resizeMode="contain"
/>

// For external URLs (YouTube, etc.)
{knowledge.video_url && (
  <WebView source={{ uri: knowledge.video_url }} />
)}
```

## Troubleshooting

### Upload Fails

1. **Check PHP configuration**: Ensure `upload_max_filesize` and `post_max_size` are set correctly
2. **Check web server limits**: Nginx/Apache may have separate upload size limits
3. **Check disk space**: Ensure sufficient storage space is available
4. **Check permissions**: Ensure `storage/app/public` is writable

### Video Won't Stream

1. **Check file exists**: Verify the video file is in `storage/app/public/knowledge/videos/`
2. **Check symbolic link**: Run `php artisan storage:link` if not already done
3. **Check permissions**: Ensure the web server can read the video files

### Slow Upload/Streaming

1. **Increase timeout values**: Adjust `max_execution_time` and `max_input_time` in php.ini
2. **Check network bandwidth**: Large files require stable connections
3. **Consider CDN**: For production, consider using a CDN for video delivery

## Production Considerations

For production environments:

1. **Use cloud storage**: Consider using AWS S3, Google Cloud Storage, or similar services for better scalability
2. **Implement video transcoding**: Convert videos to web-optimized formats
3. **Add video compression**: Reduce file sizes without significant quality loss
4. **Use a CDN**: Deliver videos through a Content Delivery Network for better performance
5. **Monitor storage usage**: Implement alerts for low disk space
6. **Backup strategy**: Ensure video files are included in your backup plan
