# Automatic Upload Cleanup System

## Overview

This system automatically cleans up orphaned and incomplete uploads to save server storage space. It handles two types of cleanup:

1. **Incomplete Chunked Uploads** - Upload sessions that were started but never completed
2. **Orphaned Video Files** - Completed uploads that were never linked to any knowledge records

## How It Works

### What Gets Cleaned Up

#### 1. Incomplete Chunked Uploads
Located in: `storage/app/temp/uploads/`

These are upload sessions where:
- User started uploading a video
- User closed the browser or navigated away
- Upload session was never finalized

**Example:**
```
storage/app/temp/uploads/
├── 550e8400-e29b-41d4-a716-446655440000/  # Abandoned 3 days ago
│   ├── metadata.json
│   ├── chunk_0
│   ├── chunk_1
│   └── chunk_2
```

#### 2. Orphaned Video Files
Located in: `storage/app/public/knowledge/videos/`

These are completed uploads where:
- Video was fully uploaded via chunked upload
- User filled out the form but never clicked Submit
- User clicked Submit but there was a validation error
- File exists on disk but `video_path` is not in any `knowledge` record

**Example:**
```
storage/app/public/knowledge/videos/
├── 550e8400-video.mp4  # Uploaded 2 days ago, not in database
├── 660f9511-video.mp4  # In database ✓ (won't be deleted)
```

### Cleanup Schedule

**Automatic:** Runs daily at 2:00 AM
- Cleans up files older than 24 hours
- Logs results for monitoring

**Manual:** Run anytime via command
```bash
php artisan uploads:cleanup
```

## Artisan Command

### Basic Usage

```bash
# Clean up uploads older than 24 hours (default)
php artisan uploads:cleanup

# Clean up uploads older than 48 hours
php artisan uploads:cleanup --hours=48

# Clean up uploads older than 1 hour (be careful!)
php artisan uploads:cleanup --hours=1
```

### Command Output

```
Cleaning up uploads older than 24 hours (before 2025-12-14 11:00:00)...

🔍 Checking for incomplete chunked uploads...
   ❌ Deleted incomplete upload: 550e8400-e29b-41d4-a716-446655440000
   ❌ Deleted incomplete upload: 660f9511-e29b-41d4-a716-446655440000

🔍 Checking for orphaned video files...
   ❌ Deleted orphaned video: 770f9511-uuid-video.mp4
   ✓ No orphaned videos to clean up.

✅ Cleanup complete!
   - Files deleted: 3
   - Space freed: 4.23 GB
```

### Command Options

| Option | Default | Description |
|--------|---------|-------------|
| `--hours` | 24 | Delete files older than this many hours |

## Configuration

### Changing the Schedule

Edit `routes/console.php`:

```php
// Run every hour
Schedule::command('uploads:cleanup --hours=1')
    ->hourly();

// Run twice daily (2 AM and 2 PM)
Schedule::command('uploads:cleanup --hours=12')
    ->twiceDaily(2, 14);

// Run weekly on Sunday at 3 AM
Schedule::command('uploads:cleanup --hours=168')
    ->weekly()
    ->sundays()
    ->at('03:00');
```

### Adjusting Cleanup Age

**Conservative (Recommended for Production):**
```bash
# Keep files for 48 hours before cleanup
php artisan uploads:cleanup --hours=48
```

**Aggressive (For Storage-Constrained Servers):**
```bash
# Delete files after just 6 hours
php artisan uploads:cleanup --hours=6
```

**Balanced (Default):**
```bash
# Delete files after 24 hours
php artisan uploads:cleanup --hours=24
```

## Setting Up the Scheduler

Laravel's scheduler requires a cron job to run.

### Production Setup

Add this to your server's crontab:

```bash
# Edit crontab
crontab -e

# Add this line (replace /path/to/your/project)
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
```

### Development

For local testing, run the scheduler manually:

```bash
# Run the scheduler once
php artisan schedule:run

# Or keep it running (simulates cron)
php artisan schedule:work
```

### Verify It's Working

```bash
# List scheduled tasks
php artisan schedule:list

# You should see:
# 0 2 * * * uploads:cleanup --hours=24 .... Next Due: 1 day from now
```

## Monitoring & Logs

### Check Cleanup History

Laravel logs command output. Check your logs:

```bash
# View recent logs
tail -f storage/logs/laravel.log

# Search for cleanup results
grep "uploads:cleanup" storage/logs/laravel.log
```

### Add Notifications (Optional)

Edit `routes/console.php` to send notifications:

```php
use Illuminate\Support\Facades\Log;

Schedule::command('uploads:cleanup --hours=24')
    ->daily()
    ->at('02:00')
    ->onSuccess(function () {
        Log::info('Upload cleanup completed successfully');
    })
    ->onFailure(function () {
        Log::error('Upload cleanup failed!');
        // Send email/Slack notification
    });
```

## Storage Space Monitoring

### Check Current Storage Usage

```bash
# Total size of all videos
du -sh storage/app/public/knowledge/videos/

# Count of video files
find storage/app/public/knowledge/videos/ -type f | wc -l

# Total size of temp uploads
du -sh storage/app/temp/uploads/
```

### Create a Storage Report Command

Create `app/Console/Commands/StorageReport.php`:

```php
php artisan make:command StorageReport
```

Then implement:

```php
public function handle()
{
    $videosPath = Storage::disk('public')->path('knowledge/videos');
    $tempPath = Storage::disk('local')->path('temp/uploads');
    
    $videosSize = $this->getDirectorySize($videosPath);
    $tempSize = $this->getDirectorySize($tempPath);
    $totalSize = $videosSize + $tempSize;
    
    $this->info('📊 Storage Report');
    $this->info('   Videos: ' . $this->formatBytes($videosSize));
    $this->info('   Temp: ' . $this->formatBytes($tempSize));
    $this->info('   Total: ' . $this->formatBytes($totalSize));
}
```

Run it:
```bash
php artisan storage:report
```

## Best Practices

### Recommended Settings

**For Production:**
- Run cleanup daily at low-traffic hours (2-4 AM)
- Keep files for 24-48 hours
- Monitor storage usage weekly

**For High-Traffic Sites:**
- Run cleanup twice daily
- Keep files for 12-24 hours
- Set up alerts when storage exceeds threshold

**For Low-Storage Servers:**
- Run cleanup every 6 hours
- Keep files for 6-12 hours
- Consider cloud storage (S3) instead

### When to Run Manually

Run manual cleanup when:
- Storage space is critically low
- After bulk imports/testing
- Before backups
- When investigating storage issues

```bash
# Emergency cleanup - delete everything older than 1 hour
php artisan uploads:cleanup --hours=1
```

## Troubleshooting

### Cleanup Doesn't Run Automatically

**Check scheduler is set up:**
```bash
# Verify cron job exists
crontab -l

# Manually trigger scheduler
php artisan schedule:run
```

**Check Laravel logs:**
```bash
tail -f storage/logs/laravel.log
```

### Files Not Being Deleted

**Verify file timestamps:**
```bash
# Check when files were created
ls -lah storage/app/public/knowledge/videos/
```

**Run with verbose output:**
```bash
php artisan uploads:cleanup -v
```

**Check file permissions:**
```bash
# Ensure Laravel can delete files
ls -la storage/app/public/knowledge/videos/
```

### False Positives (Valid Files Being Deleted)

**Increase the hours threshold:**
```bash
# Be more conservative
php artisan uploads:cleanup --hours=72
```

**Verify database sync:**
```sql
-- Check if video_path matches actual files
SELECT video_path FROM knowledge WHERE video_path IS NOT NULL;
```

## Safety Features

The cleanup command has built-in safety features:

1. ✅ **Age Check** - Only deletes files older than specified hours
2. ✅ **Database Verification** - Checks if video is linked to a record
3. ✅ **Dry Run Capable** - Can be extended to preview deletions
4. ✅ **Detailed Logging** - Shows exactly what was deleted
5. ✅ **Size Reporting** - Shows space freed

### Prevent Accidental Deletions

Never run with very low hours in production:

```bash
# ❌ DANGEROUS - Might delete files user just uploaded
php artisan uploads:cleanup --hours=0.5

# ✅ SAFE - Gives users time to complete forms
php artisan uploads:cleanup --hours=24
```

## Alternative: Cloud Storage

For very large video libraries, consider:

### AWS S3 Lifecycle Policies

Instead of manual cleanup, use S3's built-in lifecycle management:

```json
{
  "Rules": [{
    "Id": "CleanupUnfinishedUploads",
    "Status": "Enabled",
    "Prefix": "temp/",
    "AbortIncompleteMultipartUpload": {
      "DaysAfterInitiation": 1
    },
    "Expiration": {
      "Days": 1
    }
  }]
}
```

### Benefits of Cloud Storage
- No manual cleanup needed
- Unlimited storage
- Automatic redundancy
- CDN integration
- Cost per GB instead of fixed server costs

## Related Commands

```bash
# Clear all caches
php artisan optimize:clear

# Clear compiled views
php artisan view:clear

# Remove old log files
rm storage/logs/laravel-*.log

# Optimize autoloader
composer dump-autoload -o
```

## Summary

- ✅ Automatic daily cleanup at 2 AM
- ✅ Deletes incomplete uploads older than 24 hours
- ✅ Deletes orphaned videos not in database
- ✅ Safe - verifies before deletion
- ✅ Detailed reporting
- ✅ Configurable schedule and age threshold
- ✅ Can run manually anytime

This system ensures your server storage stays clean without manual intervention!
